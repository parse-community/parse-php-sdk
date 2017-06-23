<?php

namespace Parse;

use Parse\Internal\Encodable;
use Parse\ParseGeoPoint;

/**
 * ParsePolygon - Representation of a Parse Polygon object.
 *
 * @author Diamond Lewis <findlewis@gmail.com>
 */
class ParsePolygon implements Encodable
{
    /**
     * The coordinates.
     *
     * @var array
     */
    private $coordinates;

    /**
     * Create a Parse Polygon object.
     *
     * @param array $coords GeoPoints or Coordinates.
     */
    public function __construct($coords)
    {
        $this->setCoordinates($coords);
    }

    /**
     * Set the Coordinates value for this Polygon.
     *
     * @param array $coords GeoPoints or Coordinates.
     *
     * @throws ParseException
     */
    public function setCoordinates($coords)
    {
        if (count($coords) < 3 || !is_array($coords)) {
            throw new ParseException('Polygon must have at least 3 points');
        }
        $points = [];
        foreach ($coords as $coord) {
            $geoPoint = $coord;
            if (is_array($coord)) {
                $geoPoint = new ParseGeoPoint($coord[0], $coord[1]);
            }
            $points[] = [$geoPoint->getLatitude(), $geoPoint->getLongitude()];
        }
        $this->coordinates = $points;
    }

    /**
     * Returns the Coordinates value for this Polygon.
     *
     * @return array
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * Encode to associative array representation.
     *
     * @return array
     */
    public function _encode()
    {
        return [
            '__type'    => 'Polygon',
            'coordinates'  => $this->coordinates,
        ];
    }
}
