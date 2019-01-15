<?php
/**
 * Created by PhpStorm.
 * Date: 14.01.19
 * Time: 17:18
 */

namespace InfluxDB\ORM\Mapping;

use InfluxDB\ORM\Mapping\Exception\AnnotationException;
use InfluxDB\ORM\Mapping\Map\PointMap;
use InfluxDB\ORM\Mapping\Map\PropertyMap;
use Doctrine\Common\Annotations\Reader;
use InfluxDB\ORM\Mapping\Annotations;

/**
 * Class AnnotationDriver
 * @package InfluxDB\ORM\Mapping
 */
class AnnotationDriver
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var string
     */
    private $measurementName;

    /**
     * @var PropertyMap
     */
    private $valuePropertyMap;

    /**
     * @var PropertyMap[]
     */
    private $fieldPropertiesMaps = [];

    /**
     * @var PropertyMap[]
     */
    private $tagPropertiesMaps = [];

    /**
     * @var PropertyMap
     */
    private $timestampPropertyMap;

    /**
     * AnnotationDriver constructor.
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return PointMap
     * @throws AnnotationException
     */
    public function buildPointMap(\ReflectionClass $reflectionClass): PointMap
    {
        $this->initFromClassAnnotations($reflectionClass);
        $this->initFromPropertiesAnnotations($reflectionClass);

        return new PointMap(
            $this->measurementName,
            $this->valuePropertyMap,
            $this->fieldPropertiesMaps,
            $this->tagPropertiesMaps,
            $this->timestampPropertyMap
        );
    }

    /**
     * Reads annotations, applied to class. If wanted annotation was not found in current class, jumps to its parent.
     * Annotation from children classes have higher priority.
     * If wanted annotation already found in child class, the same annotation in parents will be ignored.
     *
     * @param \ReflectionClass $reflectionClass
     */
    private function initFromClassAnnotations(\ReflectionClass $reflectionClass): void
    {
        $measurement = $this->readClassAnnotation($reflectionClass, Annotations\Measurement::class);
        if (empty($measurement->name)) {
            throw new AnnotationException($reflectionClass->name. ': Measurement name must be a non-empty string.');
        }
        $this->measurementName = $measurement->name;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param string $annotationName
     * @return object
     */
    private function readClassAnnotation(\ReflectionClass $reflectionClass, string $annotationName)
    {
        $annotation = $this->reader->getClassAnnotation($reflectionClass, $annotationName);
        if (null === $annotation) {
            if ($reflectionClassParent = $reflectionClass->getParentClass()) {
                return $this->readClassAnnotation($reflectionClassParent, $annotationName);
            }
            throw new AnnotationException($reflectionClass->name. ': ' . $annotationName. ' annotation is not specified.');
        }
        return $annotation;
    }

    /**
     * Reads properties annotation from class.
     *
     * @param \ReflectionClass $reflectionClass
     */
    private function initFromPropertiesAnnotations(\ReflectionClass $reflectionClass): void
    {
        foreach ($reflectionClass->getProperties() as $property) {
            if ($annotation = $this->reader->getPropertyAnnotation($property, Annotations\Value::class)) {
                if ($this->valuePropertyMap !== null) {
                    throw new AnnotationException("Few 'Value' annotations of  found in '{$reflectionClass->name}'. Maximum one property of class can has this annotation.");
                }
                $this->valuePropertyMap = new PropertyMap($property->name);
            }

            if ($annotation = $this->reader->getPropertyAnnotation($property, Annotations\Field::class)) {
                $this->fieldPropertiesMaps[$annotation->key] = new PropertyMap($property->name);
            }

            if ($annotation = $this->reader->getPropertyAnnotation($property, Annotations\Tag::class)) {
                $this->tagPropertiesMaps[$annotation->key] = new PropertyMap($property->name, $annotation->type);
            }

            if ($annotation = $this->reader->getPropertyAnnotation($property, Annotations\Timestamp::class)) {
                if ($this->timestampPropertyMap !== null) {
                    throw new AnnotationException("Few 'Timestamp' annotations of  found in '{$reflectionClass->name}'. Maximum one property of class can has this annotation.");
                }
                $this->timestampPropertyMap = new PropertyMap($property->name);
            }
        }
    }

}