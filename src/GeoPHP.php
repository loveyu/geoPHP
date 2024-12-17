<?php

/*
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Adapters

namespace Loveyu\GeoPHP;

use Loveyu\GeoPHP\Adapters\EWKB;
use Loveyu\GeoPHP\Adapters\EWKT;
use Loveyu\GeoPHP\Adapters\GeoHash;
use Loveyu\GeoPHP\Adapters\GeoJSON;
use Loveyu\GeoPHP\Adapters\GeoRSS;
use Loveyu\GeoPHP\Adapters\GoogleGeocode;
use Loveyu\GeoPHP\Adapters\GPX;
use Loveyu\GeoPHP\Adapters\KML;
use Loveyu\GeoPHP\Adapters\WKB;
use Loveyu\GeoPHP\Adapters\WKT;
use Loveyu\GeoPHP\Geometry\Collection;
use Loveyu\GeoPHP\Geometry\Geometry;
use Loveyu\GeoPHP\Geometry\GeometryCollection;
use Loveyu\GeoPHP\Geometry\MultiLineString;
use Loveyu\GeoPHP\Geometry\MultiPoint;
use Loveyu\GeoPHP\Geometry\MultiPolygon;

class GeoPHP
{
    public static function version()
    {
        return '1.2';
    }

    // geoPHP::load($data, $type, $other_args);
    // if $data is an array, all passed in values will be combined into a single geometry
    public static function load()
    {
        $args = func_get_args();

        $data = array_shift($args);
        $type = array_shift($args);

        $type_map = GeoPHP::getAdapterMap();

        // Auto-detect type if needed
        if (!$type) {
            // If the user is trying to load a Geometry from a Geometry... Just pass it back
            if (is_object($data)) {
                if ($data instanceof Geometry) {
                    return $data;
                }
            }

            $detected = GeoPHP::detectFormat($data);
            if (!$detected) {
                return false;
            }

            $format = explode(':', $detected);
            $type = array_shift($format);
            $args = $format;
        }

        $processor_type = $type_map[$type];

        if (!$processor_type) {
            throw new \Exception('geoPHP could not find an adapter of type '.htmlentities($type));
        }

        $processor = new $processor_type();

        // Data is not an array, just pass it normally
        if (!is_array($data)) {
            $result = call_user_func_array([$processor, 'read'], array_merge([$data], $args));
        } // Data is an array, combine all passed in items into a single geometry
        else {
            $geoms = [];
            foreach ($data as $item) {
                $geoms[] = call_user_func_array([$processor, 'read'], array_merge([$item], $args));
            }
            $result = GeoPHP::geometryReduce($geoms);
        }

        return $result;
    }

    public static function getAdapterMap()
    {
        return [
            'wkt' => WKT::class,
            'ewkt' => EWKT::class,
            'wkb' => WKB::class,
            'ewkb' => EWKB::class,
            'json' => GeoJSON::class,
            'geojson' => GeoJSON::class,
            'kml' => KML::class,
            'gpx' => GPX::class,
            'georss' => GeoRSS::class,
            'google_geocode' => GoogleGeocode::class,
            'geohash' => GeoHash::class,
        ];
    }

    public static function geometryList()
    {
        return [
            'point' => 'Point',
            'linestring' => 'LineString',
            'polygon' => 'Polygon',
            'multipoint' => 'MultiPoint',
            'multilinestring' => 'MultiLineString',
            'multipolygon' => 'MultiPolygon',
            'geometrycollection' => 'GeometryCollection',
        ];
    }

    public static function geosInstalled($force = null)
    {
        static $geos_installed = null;
        if (null !== $force) {
            $geos_installed = $force;
        }
        if (null !== $geos_installed) {
            return $geos_installed;
        }
        $geos_installed = class_exists('GEOSGeometry');

        return $geos_installed;
    }

    public static function geosToGeometry($geos)
    {
        if (!GeoPHP::geosInstalled()) {
            return null;
        }
        $wkb_writer = new \GEOSWKBWriter();
        $wkb = $wkb_writer->writeHEX($geos);
        $geometry = GeoPHP::load($wkb, 'wkb', true);
        if ($geometry) {
            $geometry->setGeos($geos);

            return $geometry;
        }
    }

    // Reduce a geometry, or an array of geometries, into their 'lowest' available common geometry.
    // For example a GeometryCollection of only points will become a MultiPoint
    // A multi-point containing a single point will return a point.
    // An array of geometries can be passed and they will be compiled into a single geometry
    public static function geometryReduce($geometry)
    {
        // If it's an array of one, then just parse the one
        if (is_array($geometry)) {
            if (empty($geometry)) {
                return false;
            }
            if (1 == count($geometry)) {
                return GeoPHP::geometryReduce(array_shift($geometry));
            }
        }

        // If the geometry cannot even theoretically be reduced more, then pass it back
        if ('object' == gettype($geometry)) {
            $passbacks = ['Point', 'LineString', 'Polygon'];

            /** @var Geometry $geometry */
            if (in_array($geometry->geometryType(), $passbacks)) {
                return $geometry;
            }
        }

        // If it is a mutlti-geometry, check to see if it just has one member
        // If it does, then pass the member, if not, then just pass back the geometry
        if ('object' == gettype($geometry)) {
            $simple_collections = [MultiPoint::class, MultiLineString::class, MultiPolygon::class];

            /** @var Collection $geometry */
            if (in_array(get_class($geometry), $simple_collections)) {
                $components = $geometry->getComponents();
                if (1 == count($components)) {
                    return $components[0];
                }

                return $geometry;
            }
        }

        // So now we either have an array of geometries, a GeometryCollection, or an array of GeometryCollections
        if (!is_array($geometry)) {
            $geometry = [$geometry];
        }

        $geometries = [];
        $geom_types = [];

        $collections = [MultiPoint::class, MultiLineString::class, MultiPolygon::class, GeometryCollection::class];

        foreach ($geometry as $item) {
            if ($item) {
                if (in_array(get_class($item), $collections)) {
                    foreach ($item->getComponents() as $component) {
                        $geometries[] = $component;
                        $geom_types[] = $component->geometryType();
                    }
                } else {
                    $geometries[] = $item;
                    $geom_types[] = $item->geometryType();
                }
            }
        }

        $geom_types = array_unique($geom_types);

        if (empty($geom_types)) {
            return false;
        }

        if (1 == count($geom_types)) {
            if (1 == count($geometries)) {
                return $geometries[0];
            }
            $class = [
                'LineString' => MultiLineString::class,
                'Point' => MultiPoint::class,
                'Polygon' => MultiPolygon::class,
            ][$geom_types[0]];

            return new $class($geometries);
        }

        return new GeometryCollection($geometries);
    }

    // Detect a format given a value. This function is meant to be SPEEDY.
    // It could make a mistake in XML detection if you are mixing or using namespaces in weird ways (ie, KML inside an RSS feed)
    public static function detectFormat(&$input)
    {
        $mem = fopen('php://memory', 'r+');
        fwrite($mem, $input, 11); // Write 11 bytes - we can detect the vast majority of formats in the first 11 bytes
        fseek($mem, 0);

        $bytes = unpack('c*', fread($mem, 11));

        // If bytes is empty, then we were passed empty input
        if (empty($bytes)) {
            return false;
        }

        // First char is a tab, space or carriage-return. trim it and try again
        if (9 == $bytes[1] || 10 == $bytes[1] || 32 == $bytes[1]) {
            $ltinput = ltrim($input);

            return GeoPHP::detectFormat($ltinput);
        }

        // Detect WKB or EWKB -- first byte is 1 (little endian indicator)
        if (1 == $bytes[1]) {
            // If SRID byte is TRUE (1), it's EWKB
            if ($bytes[5]) {
                return 'ewkb';
            }

            return 'wkb';
        }

        // Detect HEX encoded WKB or EWKB (PostGIS format) -- first byte is 48, second byte is 49 (hex '01' => first-byte = 1)
        if (48 == $bytes[1] && 49 == $bytes[2]) {
            // The shortest possible WKB string (LINESTRING EMPTY) is 18 hex-chars (9 encoded bytes) long
            // This differentiates it from a geohash, which is always shorter than 18 characters.
            if (strlen($input) >= 18) {
                // @@TODO: Differentiate between EWKB and WKB -- check hex-char 10 or 11 (SRID bool indicator at encoded byte 5)
                return 'ewkb:1';
            }
        }

        // Detect GeoJSON - first char starts with {
        if (123 == $bytes[1]) {
            return 'json';
        }

        // Detect EWKT - first char is S
        if (83 == $bytes[1]) {
            return 'ewkt';
        }

        // Detect WKT - first char starts with P (80), L (76), M (77), or G (71)
        $wkt_chars = [80, 76, 77, 71];
        if (in_array($bytes[1], $wkt_chars)) {
            return 'wkt';
        }

        // Detect XML -- first char is <
        if (60 == $bytes[1]) {
            // grab the first 256 characters
            $string = substr($input, 0, 256);
            if (false !== strpos($string, '<kml')) {
                return 'kml';
            }
            if (false !== strpos($string, '<coordinate')) {
                return 'kml';
            }
            if (false !== strpos($string, '<gpx')) {
                return 'gpx';
            }
            if (false !== strpos($string, '<georss')) {
                return 'georss';
            }
            if (false !== strpos($string, '<rss')) {
                return 'georss';
            }
            if (false !== strpos($string, '<feed')) {
                return 'georss';
            }
        }

        // We need an 8 byte string for geohash and unpacked WKB / WKT
        fseek($mem, 0);
        $string = trim(fread($mem, 8));

        // Detect geohash - geohash ONLY contains lowercase chars and numerics
        preg_match('/[a-z0-9]+/', $string, $matches);
        if ($matches[0] == $string) {
            return 'geohash';
        }

        // What do you get when you cross an elephant with a rhino?
        // http://youtu.be/RCBn5J83Poc
        return false;
    }
}
