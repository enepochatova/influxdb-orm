<?php
/**
 * Created by PhpStorm.
 * Date: 14.01.19
 * Time: 17:39
 */

namespace InfluxDB\ORM\Mapping\Map;

/**
 * Class PointMap
 * @package InfluxDB\ORM\Mapping\Map
 */
final class PointMap
{
    /**
     * @var string
     */
    private $measurement;

    /**
     * @var null|PropertyMap
     */
    private $valuePropertyMap;

    /**
     * @var PropertyMap[]
     */
    private $fieldPropertiesMap;

    /**
     * @var PropertyMap[]
     */
    private $tagPropertiesMap;

    /**
     * @var null|PropertyMap
     */
    private $timestampPropertyMap;

    /**
     * PointMap constructor.
     * @param string $measurement
     * @param PropertyMap|null $valuePropertyMap
     * @param PropertyMap[] $fieldPropertiesMap
     * @param PropertyMap[] $tagPropertiesMap
     * @param PropertyMap|null $timestampPropertyMap
     */
    public function __construct(
        string $measurement,
        ?PropertyMap $valuePropertyMap,
        array $fieldPropertiesMap,
        array $tagPropertiesMap,
        ?PropertyMap $timestampPropertyMap
    )
    {
        $this->measurement = $measurement;
        $this->valuePropertyMap = $valuePropertyMap;
        $this->fieldPropertiesMap = $fieldPropertiesMap;
        $this->tagPropertiesMap = $tagPropertiesMap;
        $this->timestampPropertyMap = $timestampPropertyMap;
    }

    /**
     * @return string
     */
    public function getMeasurement(): string
    {
        return $this->measurement;
    }

    /**
     * @return PropertyMap|null
     */
    public function getValuePropertyMap(): ?PropertyMap
    {
        return $this->valuePropertyMap;
    }

    /**
     * @return PropertyMap[]
     */
    public function getFieldPropertiesMap(): array
    {
        return $this->fieldPropertiesMap;
    }

    /**
     * @return PropertyMap[]
     */
    public function getTagPropertiesMap(): array
    {
        return $this->tagPropertiesMap;
    }

    /**
     * @return PropertyMap|null
     */
    public function getTimestampPropertyMap(): ?PropertyMap
    {
        return $this->timestampPropertyMap;
    }

}