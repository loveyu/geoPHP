<?php

// Uncomment to test
use Loveyu\GeoPHP\GeoPHP;

if (1 == getenv('GEOPHP_RUN_TESTS')) {
    run_test();
} else {
    echo "Skipping tests. Please set GEOPHP_RUN_TESTS=1 environment variable if you wish to run tests\n";
}

function run_test()
{
    set_time_limit(0);

    set_error_handler('FailOnError');

    header('Content-type: text');

    if (GeoPHP::geosInstalled()) {
        echo "GEOS is installed.\n";
    } else {
        echo "GEOS is not installed.\n";
    }

    foreach (scandir(__DIR__.'/../input') as $file) {
        $parts = explode('.', $file);
        if ($parts[0]) {
            $format = $parts[1];
            $value = file_get_contents(__DIR__.'/../input/'.$file);
            echo '---- Testing '.$file."\n";
            $geometry = GeoPHP::load($value, $format);
            test_adapters($geometry, $format, $value);
            test_methods($geometry);
            test_geometry($geometry);
            test_detection($value, $format, $file);
        }
    }
    echo "\e[32m".'PASS'."\e[39m\n";
}

function test_geometry($geometry)
{
    // Test common functions
    $geometry->area();
    $geometry->boundary();
    $geometry->envelope();
    $geometry->getBBox();
    $geometry->centroid();
    $geometry->length();
    $geometry->greatCircleLength();
    $geometry->haversineLength();
    $geometry->y();
    $geometry->x();
    $geometry->numGeometries();
    $geometry->geometryN(1);
    $geometry->startPoint();
    $geometry->endPoint();
    $geometry->isRing();
    $geometry->isClosed();
    $geometry->numPoints();
    $geometry->pointN(1);
    $geometry->exteriorRing();
    $geometry->numInteriorRings();
    $geometry->interiorRingN(1);
    $geometry->dimension();
    $geometry->geometryType();
    $geometry->SRID();
    $geometry->setSRID(4326);

    // Aliases
    $geometry->getCentroid();
    $geometry->getArea();
    $geometry->getX();
    $geometry->getY();
    $geometry->getGeos();
    $geometry->getGeomType();
    $geometry->getSRID();
    $geometry->asText();
    $geometry->asBinary();

    // GEOS only functions
    $geometry->geos();
    $geometry->setGeos($geometry->geos());
    $geometry->pointOnSurface();
    $geometry->equals($geometry);
    $geometry->equalsExact($geometry);
    $geometry->relate($geometry);
    $geometry->checkValidity();
    $geometry->isSimple();
    $geometry->buffer(10);
    $geometry->intersection($geometry);
    $geometry->convexHull();
    $geometry->difference($geometry);
    $geometry->symDifference($geometry);
    $geometry->union($geometry);
    $geometry->simplify(0); // @@TODO: Adjust this once we can deal with empty geometries
    $geometry->disjoint($geometry);
    $geometry->touches($geometry);
    $geometry->intersects($geometry);
    $geometry->crosses($geometry);
    $geometry->within($geometry);
    $geometry->contains($geometry);
    $geometry->overlaps($geometry);
    $geometry->covers($geometry);
    $geometry->coveredBy($geometry);
    $geometry->distance($geometry);
    $geometry->hausdorffDistance($geometry);

    // Place holders
    $geometry->hasZ();
    $geometry->is3D();
    $geometry->isMeasured();
    $geometry->isEmpty();
    $geometry->coordinateDimension();
    $geometry->z();
    $geometry->m();
}

function test_adapters($geometry, $format, $input)
{
    // Test adapter output and input. Do a round-trip and re-test
    foreach (GeoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
        if ('google_geocode' != $adapter_key) { // Don't test google geocoder regularily. Uncomment to test
            $output = $geometry->out($adapter_key);
            if ($output) {
                $adapter_loader = new $adapter_class();
                $test_geom_1 = $adapter_loader->read($output);
                $test_geom_2 = $adapter_loader->read($test_geom_1->out($adapter_key));

                if ($test_geom_1->out('wkt') != $test_geom_2->out('wkt')) {
                    echo 'Mismatched adapter output in '.$adapter_class."\n";
                }
            } else {
                echo 'Empty output on '.$adapter_key."\n";
            }
        }
    }

    // Test to make sure adapter work the same wether GEOS is ON or OFF
    // Cannot test methods if GEOS is not intstalled
    if (!GeoPHP::geosInstalled()) {
        return;
    }

    foreach (GeoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
        if ('google_geocode' != $adapter_key) { // Don't test google geocoder regularily. Uncomment to test
            // Turn GEOS on
            GeoPHP::geosInstalled(true);

            $output = $geometry->out($adapter_key);
            if ($output) {
                $adapter_loader = new $adapter_class();

                $test_geom_1 = $adapter_loader->read($output);

                // Turn GEOS off
                GeoPHP::geosInstalled(false);

                $test_geom_2 = $adapter_loader->read($output);

                // Turn GEOS back On
                GeoPHP::geosInstalled(true);

                // Check to make sure a both are the same with geos and without
                if ($test_geom_1->out('wkt') != $test_geom_2->out('wkt')) {
                    echo 'Mismatched adapter output between GEOS and NORM in '.$adapter_class."\n";
                }
            }
        }
    }
}

function test_methods($geometry)
{
    // Cannot test methods if GEOS is not intstalled
    if (!GeoPHP::geosInstalled()) {
        return;
    }

    $methods = [
        // 'boundary', //@@TODO: Uncomment this and fix errors
        'envelope',   // @@TODO: Testing reveales errors in this method -- POINT vs. POLYGON
        'getBBox',
        'x',
        'y',
        'startPoint',
        'endPoint',
        'isRing',
        'isClosed',
        'numPoints',
    ];

    foreach ($methods as $method) {
        // Turn GEOS on
        GeoPHP::geosInstalled(true);
        $geos_result = $geometry->{$method}();

        // Turn GEOS off
        GeoPHP::geosInstalled(false);
        $norm_result = $geometry->{$method}();

        // Turn GEOS back On
        GeoPHP::geosInstalled(true);

        $geos_type = gettype($geos_result);
        $norm_type = gettype($norm_result);

        if ($geos_type != $norm_type) {
            echo 'Type mismatch on '.$method."\n";

            continue;
        }

        // Now check base on type
        if ('object' == $geos_type) {
            $haus_dist = $geos_result->hausdorffDistance(GeoPHP::load($norm_result->out('wkt'), 'wkt'));

            // Get the length of the diagonal of the bbox - this is used to scale the haustorff distance
            // Using Pythagorean theorem
            $bb = $geos_result->getBBox();
            $scale = sqrt((($bb['maxy'] - $bb['miny']) ^ 2) + (($bb['maxx'] - $bb['minx']) ^ 2));

            // The difference in the output of GEOS and native-PHP methods should be less than 0.5 scaled haustorff units
            if ($haus_dist / $scale > 0.5) {
                echo 'Output mismatch on '.$method.":\n";
                echo 'GEOS : '.$geos_result->out('wkt')."\n";
                echo 'NORM : '.$norm_result->out('wkt')."\n";

                continue;
            }
        }

        if ('boolean' == $geos_type || 'string' == $geos_type) {
            if ($geos_result !== $norm_result) {
                echo 'Output mismatch on '.$method.":\n";
                echo 'GEOS : '.(string) $geos_result."\n";
                echo 'NORM : '.(string) $norm_result."\n";

                continue;
            }
        }

        // @@TODO: Run tests for output of types arrays and float
        // @@TODO: centroid function is non-compliant for collections and strings
    }
}

function test_detection($value, $format, $file)
{
    $detected = GeoPHP::detectFormat($value);
    if ($detected != $format) {
        if ($detected) {
            echo 'detected as '.$detected."\n";
        } else {
            echo "format not detected\n";
        }
    }
    // Make sure it loads using auto-detect
    GeoPHP::load($value);
}

function FailOnError($error_level, $error_message, $error_file, $error_line, $error_context)
{
    echo "{$error_level}: {$error_message} in {$error_file} on line {$error_line}\n";
    echo "\e[31m".'FAIL'."\e[39m\n";

    exit(1);
}
