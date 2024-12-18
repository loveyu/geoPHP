<?php

namespace Loveyu\GeoPHP\Adapters;

use Loveyu\GeoPHP\Geometry\Geometry;
use Loveyu\GeoPHP\Geometry\LineString;
use Loveyu\GeoPHP\Geometry\Point;
use Loveyu\GeoPHP\Geometry\Polygon;

/**
 * PHP Geometry GeoHash encoder/decoder.
 *
 * @author prinsmc
 *
 * @see http://en.wikipedia.org/wiki/Geohash
 */
class GeoHash extends GeoAdapter
{
    /**
     * base32 encoding character map.
     */
    private $table = '0123456789bcdefghjkmnpqrstuvwxyz';

    /**
     * array of neighbouring hash character maps.
     */
    private $neighbours = [
        // north
        'top' => [
            'even' => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy',
            'odd' => 'bc01fg45238967deuvhjyznpkmstqrwx',
        ],
        // east
        'right' => [
            'even' => 'bc01fg45238967deuvhjyznpkmstqrwx',
            'odd' => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy',
        ],
        // west
        'left' => [
            'even' => '238967debc01fg45kmstqrwxuvhjyznp',
            'odd' => '14365h7k9dcfesgujnmqp0r2twvyx8zb',
        ],
        // south
        'bottom' => [
            'even' => '14365h7k9dcfesgujnmqp0r2twvyx8zb',
            'odd' => '238967debc01fg45kmstqrwxuvhjyznp',
        ],
    ];

    /**
     * array of bordering hash character maps.
     */
    private $borders = [
        // north
        'top' => [
            'even' => 'prxz',
            'odd' => 'bcfguvyz',
        ],
        // east
        'right' => [
            'even' => 'bcfguvyz',
            'odd' => 'prxz',
        ],
        // west
        'left' => [
            'even' => '0145hjnp',
            'odd' => '028b',
        ],
        // south
        'bottom' => [
            'even' => '028b',
            'odd' => '0145hjnp',
        ],
    ];

    /**
     * Convert the geohash to a Point. The point is 2-dimensional.
     *
     * @param string $hash    a geohash
     * @param mixed  $as_grid
     *
     * @return Point the converted geohash
     *
     * @see GeoAdapter::read()
     */
    public function read($hash, $as_grid = false)
    {
        $ll = $this->decode($hash);
        if (!$as_grid) {
            return new Point($ll['medlon'], $ll['medlat']);
        }

        return new Polygon([
            new LineString([
                new Point($ll['minlon'], $ll['maxlat']),
                new Point($ll['maxlon'], $ll['maxlat']),
                new Point($ll['maxlon'], $ll['minlat']),
                new Point($ll['minlon'], $ll['minlat']),
                new Point($ll['minlon'], $ll['maxlat']),
            ]),
        ]);
    }

    /**
     * Convert the geometry to geohash.
     *
     * @param Point      $geometry
     * @param null|mixed $precision
     *
     * @return string the geohash or null when the $geometry is not a Point
     *
     * @see GeoAdapter::write()
     */
    public function write(Geometry $geometry, $precision = null)
    {
        if ($geometry->isEmpty()) {
            return '';
        }

        if ('Point' === $geometry->geometryType()) {
            return $this->encodePoint($geometry, $precision);
        }
        // The geohash is the hash grid ID that fits the envelope
        $envelope = $geometry->envelope();
        $geohashes = [];
        $geohash = '';
        foreach ($envelope->getPoints() as $point) {
            $geohashes[] = $this->encodePoint($point, 0.0000001);
        }
        $i = 0;
        while ($i < strlen($geohashes[0])) {
            $char = $geohashes[0][$i];
            foreach ($geohashes as $hash) {
                if ($hash[$i] != $char) {
                    return $geohash;
                }
            }
            $geohash .= $char;
            ++$i;
        }

        return $geohash;
    }

    /**
     * Calculates the adjacent geohash of the geohash in the specified direction.
     * This algorithm is available in various ports that seem to point back to
     * geohash-js by David Troy under MIT notice.
     *
     * @see https://github.com/davetroy/geohash-js
     * @see https://github.com/lyokato/objc-geohash
     * @see https://github.com/lyokato/libgeohash
     * @see https://github.com/masuidrive/pr_geohash
     * @see https://github.com/sunng87/node-geohash
     * @see https://github.com/davidmoten/geo
     *
     * @param string $hash      the geohash (lowercase)
     * @param string $direction the direction of the neighbor (top, bottom, left or right)
     *
     * @return string the geohash of the adjacent cell
     */
    public function adjacent($hash, $direction)
    {
        $last = substr($hash, -1);
        $type = (strlen($hash) % 2) ? 'odd' : 'even';
        $base = substr($hash, 0, strlen($hash) - 1);
        if (false !== strpos($this->borders[$direction][$type], $last)) {
            $base = $this->adjacent($base, $direction);
        }

        return $base.$this->table[strpos($this->neighbours[$direction][$type], $last)];
    }

    /**
     * @param Point      $point
     * @param null|mixed $precision
     *
     * @return string geohash
     *
     * @author algorithm based on code by Alexander Songe <a@songe.me>
     *
     * @see https://github.com/asonge/php-geohash/issues/1
     */
    private function encodePoint($point, $precision = null)
    {
        if (null === $precision) {
            $lap = strlen($point->y()) - strpos($point->y(), '.');
            $lop = strlen($point->x()) - strpos($point->x(), '.');
            $precision = pow(10, -max($lap - 1, $lop - 1, 0)) / 2;
        }

        $minlat = -90;
        $maxlat = 90;
        $minlon = -180;
        $maxlon = 180;
        $latE = 90;
        $lonE = 180;
        $i = 0;
        $error = 180;
        $hash = '';
        while ($error >= $precision) {
            $chr = 0;
            for ($b = 4; $b >= 0; --$b) {
                if ((1 & $b) == (1 & $i)) {
                    // even char, even bit OR odd char, odd bit...a lon
                    $next = ($minlon + $maxlon) / 2;
                    if ($point->x() > $next) {
                        $chr |= pow(2, $b);
                        $minlon = $next;
                    } else {
                        $maxlon = $next;
                    }
                    $lonE /= 2;
                } else {
                    // odd char, even bit OR even char, odd bit...a lat
                    $next = ($minlat + $maxlat) / 2;
                    if ($point->y() > $next) {
                        $chr |= pow(2, $b);
                        $minlat = $next;
                    } else {
                        $maxlat = $next;
                    }
                    $latE /= 2;
                }
            }
            $hash .= $this->table[$chr];
            ++$i;
            $error = min($latE, $lonE);
        }

        return $hash;
    }

    /**
     * @param string $hash a geohash
     *
     * @author algorithm based on code by Alexander Songe <a@songe.me>
     *
     * @see https://github.com/asonge/php-geohash/issues/1
     */
    private function decode($hash)
    {
        $ll = [];
        $minlat = -90;
        $maxlat = 90;
        $minlon = -180;
        $maxlon = 180;
        $latE = 90;
        $lonE = 180;
        for ($i = 0, $c = strlen($hash); $i < $c; ++$i) {
            $v = strpos($this->table, $hash[$i]);
            if (1 & $i) {
                if (16 & $v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (8 & $v) {
                    $minlon = ($minlon + $maxlon) / 2;
                } else {
                    $maxlon = ($minlon + $maxlon) / 2;
                }
                if (4 & $v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (2 & $v) {
                    $minlon = ($minlon + $maxlon) / 2;
                } else {
                    $maxlon = ($minlon + $maxlon) / 2;
                }
                if (1 & $v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                $latE /= 8;
                $lonE /= 4;
            } else {
                if (16 & $v) {
                    $minlon = ($minlon + $maxlon) / 2;
                } else {
                    $maxlon = ($minlon + $maxlon) / 2;
                }
                if (8 & $v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (4 & $v) {
                    $minlon = ($minlon + $maxlon) / 2;
                } else {
                    $maxlon = ($minlon + $maxlon) / 2;
                }
                if (2 & $v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (1 & $v) {
                    $minlon = ($minlon + $maxlon) / 2;
                } else {
                    $maxlon = ($minlon + $maxlon) / 2;
                }
                $latE /= 4;
                $lonE /= 8;
            }
        }
        $ll['minlat'] = $minlat;
        $ll['minlon'] = $minlon;
        $ll['maxlat'] = $maxlat;
        $ll['maxlon'] = $maxlon;
        $ll['medlat'] = round(($minlat + $maxlat) / 2, max(1, -round(log10($latE))) - 1);
        $ll['medlon'] = round(($minlon + $maxlon) / 2, max(1, -round(log10($lonE))) - 1);

        return $ll;
    }
}
