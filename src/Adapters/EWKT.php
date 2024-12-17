<?php

namespace Loveyu\GeoPHP\Adapters;

use Loveyu\GeoPHP\Geometry\Geometry;

/**
 * EWKT (Extended Well Known Text) Adapter.
 */
class EWKT extends WKT
{
    /**
     * Serialize geometries into an EWKT string.
     *
     * @return string The Extended-WKT string representation of the input geometries
     */
    public function write(Geometry $geometry)
    {
        $srid = $geometry->SRID();
        $wkt = '';
        if ($srid) {
            $wkt = 'SRID='.$srid.';';
            $wkt .= $geometry->out('wkt');

            return $wkt;
        }

        return $geometry->out('wkt');
    }
}
