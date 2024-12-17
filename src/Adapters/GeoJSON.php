<?php

namespace Loveyu\GeoPHP\Adapters;

use Loveyu\GeoPHP\Geometry\Geometry;
use Loveyu\GeoPHP\Geometry\GeometryCollection;
use Loveyu\GeoPHP\Geometry\LineString;
use Loveyu\GeoPHP\Geometry\MultiLineString;
use Loveyu\GeoPHP\Geometry\MultiPoint;
use Loveyu\GeoPHP\Geometry\MultiPolygon;
use Loveyu\GeoPHP\Geometry\Point;
use Loveyu\GeoPHP\Geometry\Polygon;
use Loveyu\GeoPHP\GeoPHP;

/**
 * GeoJSON class : a geojson reader/writer.
 *
 * Note that it will always return a GeoJSON geometry. This
 * means that if you pass it a feature, it will return the
 * geometry of that feature strip everything else.
 */
class GeoJSON extends GeoAdapter
{
    /**
     * Given an object or a string, return a Geometry.
     *
     * @param mixed $input The GeoJSON string or object
     *
     * @return object Geometry
     */
    public function read($input)
    {
        if (is_string($input)) {
            $input = json_decode($input);
        }
        if (!is_object($input)) {
            throw new \Exception('Invalid JSON');
        }
        if (!is_string($input->type)) {
            throw new \Exception('Invalid JSON');
        }

        // Check to see if it's a FeatureCollection
        if ('FeatureCollection' == $input->type) {
            $geoms = [];
            foreach ($input->features as $feature) {
                $geoms[] = $this->read($feature);
            }

            return GeoPHP::geometryReduce($geoms);
        }

        // Check to see if it's a Feature
        if ('Feature' == $input->type) {
            return $this->read($input->geometry);
        }

        // It's a geometry - process it
        return $this->objToGeom($input);
    }

    /**
     * Serializes an object into a geojson string.
     *
     * @param mixed $return_array
     *
     * @return string The GeoJSON string
     */
    public function write(Geometry $geometry, $return_array = false)
    {
        if ($return_array) {
            return $this->getArray($geometry);
        }

        return json_encode($this->getArray($geometry));
    }

    public function getArray($geometry)
    {
        if ('GeometryCollection' == $geometry->getGeomType()) {
            $component_array = [];
            foreach ($geometry->components as $component) {
                $component_array[] = [
                    'type' => $component->geometryType(),
                    'coordinates' => $component->asArray(),
                ];
            }

            return [
                'type' => 'GeometryCollection',
                'geometries' => $component_array,
            ];
        }

        return [
            'type' => $geometry->getGeomType(),
            'coordinates' => $geometry->asArray(),
        ];
    }

    private function objToGeom($obj)
    {
        $type = $obj->type;

        if ('GeometryCollection' == $type) {
            return $this->objToGeometryCollection($obj);
        }
        $method = 'arrayTo'.$type;

        return $this->{$method}($obj->coordinates);
    }

    private function arrayToPoint($array)
    {
        if (!empty($array)) {
            return new Point($array[0], $array[1]);
        }

        return new Point();
    }

    private function arrayToLineString($array)
    {
        $points = [];
        foreach ($array as $comp_array) {
            $points[] = $this->arrayToPoint($comp_array);
        }

        return new LineString($points);
    }

    private function arrayToPolygon($array)
    {
        $lines = [];
        foreach ($array as $comp_array) {
            $lines[] = $this->arrayToLineString($comp_array);
        }

        return new Polygon($lines);
    }

    private function arrayToMultiPoint($array)
    {
        $points = [];
        foreach ($array as $comp_array) {
            $points[] = $this->arrayToPoint($comp_array);
        }

        return new MultiPoint($points);
    }

    private function arrayToMultiLineString($array)
    {
        $lines = [];
        foreach ($array as $comp_array) {
            $lines[] = $this->arrayToLineString($comp_array);
        }

        return new MultiLineString($lines);
    }

    private function arrayToMultiPolygon($array)
    {
        $polys = [];
        foreach ($array as $comp_array) {
            $polys[] = $this->arrayToPolygon($comp_array);
        }

        return new MultiPolygon($polys);
    }

    private function objToGeometryCollection($obj)
    {
        $geoms = [];
        if (empty($obj->geometries)) {
            throw new \Exception('Invalid GeoJSON: GeometryCollection with no component geometries');
        }
        foreach ($obj->geometries as $comp_object) {
            $geoms[] = $this->objToGeom($comp_object);
        }

        return new GeometryCollection($geoms);
    }
}
