<?php

/*
 * (c) Camptocamp <info@camptocamp.com>
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Loveyu\GeoPHP\Adapters;

use bounds;
use Loveyu\GeoPHP\Geometry\Geometry;
use Loveyu\GeoPHP\Geometry\GeometryCollection;
use Loveyu\GeoPHP\Geometry\LineString;
use Loveyu\GeoPHP\Geometry\MultiPoint;
use Loveyu\GeoPHP\Geometry\MultiPolygon;
use Loveyu\GeoPHP\Geometry\Point;
use Loveyu\GeoPHP\Geometry\Polygon;

/**
 * PHP Google Geocoder Adapter.
 *
 * @author     Patrick Hayes <patrick.d.hayes@gmail.com>
 */
class GoogleGeocode extends GeoAdapter
{
    /**
     * Read an address string or array geometry objects.
     *
     * @param  string - Address to geocode
     * @param  string - Type of Geometry to return. Can either be 'points' or 'bounds' (polygon)
     * @param  \bounds-array|Geometry - Limit the search area to within this region. For example
     *                                by default geocoding "Cairo" will return the location of Cairo Egypt.
     *                                If you pass a polygon of illinois, it will return Cairo IL.
     * @param  \return_multiple - Return all results in a multipoint or multipolygon
     * @param mixed $address
     * @param mixed $return_type
     * @param mixed $bounds
     * @param mixed $return_multiple
     *
     * @return Geometry|GeometryCollection
     */
    public function read($address, $return_type = 'point', $bounds = false, $return_multiple = false)
    {
        if (is_array($address)) {
            $address = join(',', $address);
        }

        if ('object' == gettype($bounds)) {
            $bounds = $bounds->getBBox();
        }
        if ('array' == gettype($bounds)) {
            $bounds_string = '&bounds='.$bounds['miny'].','.$bounds['minx'].'|'.$bounds['maxy'].','.$bounds['maxx'];
        } else {
            $bounds_string = '';
        }

        $url = 'http://maps.googleapis.com/maps/api/geocode/json';
        $url .= '?address='.urlencode($address);
        $url .= $bounds_string;
        $url .= '&sensor=false';
        $this->result = json_decode(@file_get_contents($url));

        if ('OK' == $this->result->status) {
            if (false == $return_multiple) {
                if ('point' == $return_type) {
                    return $this->getPoint();
                }
                if ('bounds' == $return_type || 'polygon' == $return_type) {
                    return $this->getPolygon();
                }
            }
            if (true == $return_multiple) {
                if ('point' == $return_type) {
                    $points = [];
                    foreach ($this->result->results as $delta => $item) {
                        $points[] = $this->getPoint($delta);
                    }

                    return new MultiPoint($points);
                }
                if ('bounds' == $return_type || 'polygon' == $return_type) {
                    $polygons = [];
                    foreach ($this->result->results as $delta => $item) {
                        $polygons[] = $this->getPolygon($delta);
                    }

                    return new MultiPolygon($polygons);
                }
            }
        } else {
            if ($this->result->status) {
                throw new Exception('Error in Google Geocoder: '.$this->result->status);
            }

            throw new Exception('Unknown error in Google Geocoder');

            return false;
        }
    }

    /**
     * Serialize geometries into a WKT string.
     *
     * @param string $return_type Should be either 'string' or 'array'
     *
     * @return string Does a reverse geocode of the geometry
     */
    public function write(Geometry $geometry, $return_type = 'string')
    {
        $centroid = $geometry->getCentroid();
        $lat = $centroid->getY();
        $lon = $centroid->getX();

        $url = 'http://maps.googleapis.com/maps/api/geocode/json';
        $url .= '?latlng='.$lat.','.$lon;
        $url .= '&sensor=false';
        $this->result = json_decode(@file_get_contents($url));

        if ('OK' == $this->result->status) {
            if ('string' == $return_type) {
                return $this->result->results[0]->formatted_address;
            }
            if ('array' == $return_type) {
                return $this->result->results[0]->address_components;
            }
        } elseif ('ZERO_RESULTS' == $this->result->status) {
            if ('string' == $return_type) {
                return '';
            }
            if ('array' == $return_type) {
                return $this->result->results;
            }
        } else {
            if ($this->result->status) {
                throw new Exception('Error in Google Reverse Geocoder: '.$this->result->status);
            }

            throw new Exception('Unknown error in Google Reverse Geocoder');

            return false;
        }
    }

    private function getPoint($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->location->lat;
        $lon = $this->result->results[$delta]->geometry->location->lng;

        return new Point($lon, $lat);
    }

    private function getPolygon($delta = 0)
    {
        $points = [
            $this->getTopLeft($delta),
            $this->getTopRight($delta),
            $this->getBottomRight($delta),
            $this->getBottomLeft($delta),
            $this->getTopLeft($delta),
        ];
        $outer_ring = new LineString($points);

        return new Polygon([$outer_ring]);
    }

    private function getTopLeft($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;

        return new Point($lon, $lat);
    }

    private function getTopRight($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;

        return new Point($lon, $lat);
    }

    private function getBottomLeft($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;

        return new Point($lon, $lat);
    }

    private function getBottomRight($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;

        return new Point($lon, $lat);
    }
}
