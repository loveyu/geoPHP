<?php

namespace Loveyu\GeoPHPTest\tests;

use Loveyu\GeoPHP\GeoPHP;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class Tests20120702 extends TestCase
{
    public function testMethods()
    {
        $format = 'gpx';
        $value = file_get_contents(__DIR__.'/../input/20120702.gpx');
        $geometry = GeoPHP::load($value, $format);

        $methods = [
            ['name' => 'area'],
            ['name' => 'boundary'],
            ['name' => 'getBBox'],
            ['name' => 'centroid'],
            ['name' => 'length'],
            ['name' => 'greatCircleLength', 'argument' => 6378137],
            ['name' => 'haversineLength'],
            ['name' => 'y'],
            ['name' => 'x'],
            ['name' => 'numGeometries'],
            ['name' => 'geometryN', 'argument' => '1'],
            ['name' => 'startPoint'],
            ['name' => 'endPoint'],
            ['name' => 'isRing'],
            ['name' => 'isClosed'],
            ['name' => 'numPoints'],
            ['name' => 'pointN', 'argument' => '1'],
            ['name' => 'exteriorRing'],
            ['name' => 'numInteriorRings'],
            ['name' => 'interiorRingN', 'argument' => '1'],
            ['name' => 'dimension'],
            ['name' => 'geometryType'],
            ['name' => 'SRID'],
            ['name' => 'setSRID', 'argument' => '4326'],
        ];

        foreach ($methods as $method) {
            $argument = null;
            $method_name = $method['name'];
            if (isset($method['argument'])) {
                $argument = $method['argument'];
            }
            $this->_methods_tester($geometry, $method_name, $argument);
        }
    }

    public function _methods_tester($geometry, $method_name, $argument)
    {
        if (!method_exists($geometry, $method_name)) {
            $this->fail('Method '.$method_name.'() doesn\'t exists.');

            return;
        }

        switch ($method_name) {
            case 'y':
            case 'x':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'geometryN':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'startPoint':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    // TODO: Add a method startPoint() to MultiLineString.
                    // $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }

                break;

            case 'endPoint':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    // TODO: Add a method endPoint() to MultiLineString.
                    // $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }

                break;

            case 'isRing':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'isClosed':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'pointN':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    // TODO: Add a method pointN() to MultiLineString.
                    // $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }

                break;

            case 'exteriorRing':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'numInteriorRings':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'interiorRingN':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'setSRID':
                // TODO: The method setSRID() should return TRUE.
                break;

            case 'SRID':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'getBBox':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'centroid':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'length':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertEquals((string) $geometry->{$method_name}($argument), '0.11624637315233', 'Failed on '.$method_name);
                }

                break;

            case 'numGeometries':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'numPoints':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'dimension':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'boundary':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }

                break;

            case 'greatCircleLength':
                if ('Point' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('LineString' == $geometry->geometryType()) {
                    $this->assertNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);
                }
                if ('MultiLineString' == $geometry->geometryType()) {
                    $this->assertNotEquals($geometry->{$method_name}($argument), '9500.9359867418', 'Failed on '.$method_name);
                }

                break;

            case 'haversineLength':
            case 'area':
                $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);

                break;

            case 'geometryType':
                $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name);

                break;

            default:
                $this->assertTrue($geometry->{$method_name}($argument), 'Failed on '.$method_name);
        }
    }
}
