<?php
/**
 * Created by PhpStorm.
 * Date: 15.01.19
 * Time: 13:48
 */

namespace InfluxDB\ORM\Mapping\Map;

/**
 * Class PropertyMap
 * @package InfluxDB\ORM\Mapping\Map
 */
final class PropertyMap
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $type;

    /**
     * PropertyMap constructor.
     * @param string $name
     * @param string|null $type
     */
    public function __construct(string $name, string $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

}