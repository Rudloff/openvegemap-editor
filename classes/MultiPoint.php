<?php
/**
 * MultiPoint class.
 */

namespace OpenVegeMap\Editor;

use GeoJson\Geometry\Point;

/**
 * Extended MultiPoint class in order to add some missing functions.
 */
class MultiPoint extends \GeoJson\Geometry\MultiPoint
{
    /**
     * Get the center of the coordinates.
     *
     * @return Point
     */
    public function getCenter()
    {
        $max_lat = $min_lat = $this->coordinates[0][1];
        $max_lon = $min_lon = $this->coordinates[0][0];
        foreach ($this->coordinates as $coord) {
            if ($coord[1] > $max_lat) {
                $max_lat = $coord[1];
            }
            if ($coord[1] < $min_lat) {
                $min_lat = $coord[1];
            }
            if ($coord[0] > $max_lon) {
                $max_lon = $coord[0];
            }
            if ($coord[0] < $min_lon) {
                $min_lon = $coord[0];
            }
        }

        return new Point([($min_lon + $max_lon) / 2, ($min_lat + $max_lat) / 2]);
    }
}
