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
        $measurement = $this->readRequiredClassAnnotation($reflectionClass, Annotations\Measurement::class);
        if (empty($measurement->name)) {
            throw new AnnotationException($reflectionClass->name. ': Measurement name must be a non-empty string.');
        }

        $valuePropertyMap = $timestampPropertyMap = $arrayOfMetricsPropertyMap = null;
        $fieldPropertiesMaps = $tagPropertiesMaps = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if ($annotation = $this->reader->getPropertyAnnotation($property, Annotations\Value::class)) {
                if ($valuePropertyMap !== null) {
                    throw new AnnotationException("Few 'Value' annotations found in '{$reflectionClass->name}'. Maximum one property of class can has this annotation.");
                }
                $valuePropertyMap = new PropertyMap($property->name);
            }

            if ($annotation = $this->reader->getPropertyAnnotation($property, Annotations\Field::class)) {
                $fieldPropertiesMaps[$annotation->key] = new PropertyMap($property->name);
            }

            if ($annotation = $this->reader->getPropertyAnnotation($property, Annotations\Tag::class)) {
                $tagPropertiesMaps[$annotation->key] = new PropertyMap($property->name, $annotation->type);
            }

            if ($annotation = $this->reader->getPropertyAnnotation($property, Annotations\ArrayOfMetrics::class)) {
                if ($arrayOfMetricsPropertyMap !== null) {
                    throw new AnnotationException("Few 'ArrayOfMetrics' annotations found in '{$reflectionClass->name}'. Maximum one property of class can has this annotation.");
                }
                $arrayOfMetricsPropertyMap = new PropertyMap($property->name, 'array');
            }

            if ($annotation = $this->reader->getPropertyAnnotation($property, Annotations\Timestamp::class)) {
                if ($timestampPropertyMap !== null) {
                    throw new AnnotationException("Few 'Timestamp' annotations found in '{$reflectionClass->name}'. Maximum one property of class can has this annotation.");
                }
                $timestampPropertyMap = new PropertyMap($property->name);
            }
        }

        return new PointMap(
            $measurement->name,
            $valuePropertyMap,
            $fieldPropertiesMaps,
            $tagPropertiesMaps,
            $arrayOfMetricsPropertyMap,
            $timestampPropertyMap
        );
    }

    /**
     * Reads annotation, applied to class. If wanted annotation was not found in current class, jumps to its parent.
     * Annotation from children classes have higher priority.
     * If wanted annotation already found in child class, the same annotation in parents will be ignored.
     * Throws AnnotationException if annotation is not found.
     *
     * @param \ReflectionClass $reflectionClass
     * @param string $annotationName
     * @throws AnnotationException
     * @return object
     */
    private function readRequiredClassAnnotation(\ReflectionClass $reflectionClass, string $annotationName)
    {
        $annotation = $this->reader->getClassAnnotation($reflectionClass, $annotationName);
        if (null === $annotation) {
            if ($reflectionClassParent = $reflectionClass->getParentClass()) {
                return $this->readRequiredClassAnnotation($reflectionClassParent, $annotationName);
            }
            throw new AnnotationException($reflectionClass->name. ': ' . $annotationName. ' annotation is not specified.');
        }
        return $annotation;
    }

}
