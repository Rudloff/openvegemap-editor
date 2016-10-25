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
     * Iteratively reduce the coordinates to their center.
     *
     * @param  array $carry Value of the previous iteration
     * @param  array $item  Value of the current iteration
     * @return array
     */
    private function reduceCenter($carry, $item)
    {
        $len = count($this->coordinates);

        return [$carry[0] + $item[0]/$len, $carry[1] + $item[1]/$len];
    }

    /**
     * Get the center of the coordinates.
     *
     * @return Point
     */
    public function getCenter()
    {
        $centroid = array_reduce($this->coordinates, [$this, 'reduceCenter']);

        return new Point($centroid);
    }
}
