<?php

namespace Loveyu\GeoPHPTest\tests;

use Loveyu\GeoPHP\Adapters\GeoHash;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class GeoHashTest extends TestCase
{
    /**
     * test cases for adjacent geohashes.
     */
    public function testAdjacent()
    {
        $geohash = new GeoHash();
        $this->assertEquals('xne', $geohash->adjacent('xn7', 'top'), 'Did not find correct top adjacent geohash for xn7');
        $this->assertEquals('xnk', $geohash->adjacent('xn7', 'right'), 'Did not find correct right adjacent geohash for xn7');
        $this->assertEquals('xn5', $geohash->adjacent('xn7', 'bottom'), 'Did not find correct bottom adjacent geohash for xn7');
        $this->assertEquals('xn6', $geohash->adjacent('xn7', 'left'), 'Did not find correct left adjacent geohash for xn7');
        $this->assertEquals('xnd', $geohash->adjacent($geohash->adjacent('xn7', 'left'), 'top'), 'Did not find correct top-left adjacent geohash for xn7');
    }
}
