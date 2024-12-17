<?php

namespace Loveyu\GeoPHPTest\tests;

use Loveyu\GeoPHP\GeoPHP;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class PlaceholdersTest extends TestCase
{
    public function testPlaceholders()
    {
        foreach (scandir(__DIR__.'/../input') as $file) {
            $parts = explode('.', $file);
            if ($parts[0]) {
                $format = $parts[1];
                $value = file_get_contents(__DIR__.'/../input/'.$file);
                echo "\nloading: ".$file.' for format: '.$format;
                $geometry = GeoPHP::load($value, $format);

                $placeholders = [
                    ['name' => 'hasZ'],
                    ['name' => 'is3D'],
                    ['name' => 'isMeasured'],
                    ['name' => 'isEmpty'],
                    ['name' => 'coordinateDimension'],
                    ['name' => 'z'],
                    ['name' => 'm'],
                ];

                foreach ($placeholders as $method) {
                    $argument = null;
                    $method_name = $method['name'];
                    if (isset($method['argument'])) {
                        $argument = $method['argument'];
                    }

                    switch ($method_name) {
                        case 'hasZ':
                            if ('Point' == $geometry->geometryType()) {
                                $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name.' (test file: '.$file.')');
                            }
                            if ('LineString' == $geometry->geometryType()) {
                                $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name.' (test file: '.$file.')');
                            }
                            if ('MultiLineString' == $geometry->geometryType()) {
                                $this->assertNotNull($geometry->{$method_name}($argument), 'Failed on '.$method_name.' (test file: '.$file.')');
                            }

                            break;

                        case 'm':
                        case 'z':
                        case 'coordinateDimension':
                        case 'isEmpty':
                        case 'isMeasured':
                        case 'is3D':
                    }
                }
            }
        }
    }
}
