<?php
/**
 * Created by PhpStorm.
 * Date: 14.01.19
 * Time: 18:06
 */

namespace InfluxDB\ORM\Repository;


use InfluxDB\ORM\Mapping\AnnotationDriver;
use InfluxDB\ORM\Mapping\Exception\AnnotationException;
use InfluxDB\ORM\Mapping\Map\PointMap;
use InfluxDB\ORM\Mapping\Map\PropertyMap;
use InfluxDB\Database;
use InfluxDB\Point;

/**
 * Class BaseRepository
 * @package InfluxDB\ORM\Repository
 */
class BaseRepository
{
    /**
     * @var AnnotationDriver
     */
    protected $annotationDriver;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var string
     */
    protected $precision;

    /**
     * @var PointMap
     */
    protected $pointMap;

    /**
     * BaseRepository constructor.
     * @param AnnotationDriver $annotationDriver
     * @param Database $database
     * @param string $entityClass
     * @param string|null $precision
     * @throws \ReflectionException
     */
    public function __construct(AnnotationDriver $annotationDriver, Database $database, string $entityClass, string $precision = null)
    {
        $this->annotationDriver = $annotationDriver;
        $this->database = $database;
        $this->entityClass = $entityClass;
        $this->precision = $precision ?? Database::PRECISION_SECONDS;
        $this->pointMap = $this->annotationDriver->buildPointMap(new \ReflectionClass($this->entityClass));
    }

    /**
     * @param $entity
     * @return bool
     * @throws Database\Exception
     * @throws \InfluxDB\Exception
     */
    public function write($entity): bool
    {
        $valuePropertyMap = $this->pointMap->getValuePropertyMap();
        $value = null;
        if ($valuePropertyMap) {
            $getter = $this->buildGetterName($valuePropertyMap->getName());
            $value = $entity->$getter();
        }

        $tags = $this->prepareKeyValues($this->pointMap->getTagPropertiesMap(), $entity);

        $fields = $this->prepareKeyValues($this->pointMap->getFieldPropertiesMap(), $entity);

        $arrayPropertyMap = $this->pointMap->getArrayOfMetricsPropertyMap();

        if (null !== $arrayPropertyMap) {
            $fields[] = $this->prepareKeyValues([$arrayPropertyMap], $entity);
        }

        $timestampPropertyMap = $this->pointMap->getTimestampPropertyMap();
        $timestamp = null;
        if ($timestampPropertyMap) {
            $getter = $this->buildGetterName($timestampPropertyMap->getName());
            $timestamp = $entity->$getter();
        }

        $point = new Point(
            $this->pointMap->getMeasurement(),
            $value,
            $tags,
            $fields,
            $timestamp
        );

        return $this->database->writePoints([$point], $this->precision);
    }

    /**
     * @param array $conditions
     * @return object[]
     * @throws \Exception
     */
    public function findAll(array $conditions = []): array
    {
        $qb = $this->database
            ->getQueryBuilder()
            ->select('*')
            ->from($this->pointMap->getMeasurement());

        if ($conditions) {
            $qb->where($conditions);
        }

        $points = $qb->getResultSet()->getPoints();

        return $this->hydrateAll($points);
    }

    /**
     * @param array $row
     * @return object|null
     * @throws \ReflectionException
     */
    protected function hydrate(array $row)
    {
        $reflectionClass = new \ReflectionClass($this->entityClass);

        $args = [];

        $propertiesMap = array_merge(
            ['value' => $this->pointMap->getValuePropertyMap()],
            ['time' => $this->pointMap->getTimestampPropertyMap()],
            $this->pointMap->getTagPropertiesMap(),
            $this->pointMap->getFieldPropertiesMap()
        );

        $arrayPropertyMap = $this->pointMap->getArrayOfMetricsPropertyMap();

        $row['time'] = strtotime($row['time']);

        foreach ($row as $key => $value) {
            if (isset($propertiesMap[$key])) {
                /**
                 * @var PropertyMap $property
                 */
                $property = $propertiesMap[$key];
                $propertyName = $property->getName();
                $propertyType = $property->getType();
                if ($propertyType !== 'string' && $propertyType !== null) {
                    $convertFunction = $propertyType . 'val';
                    $value = $convertFunction($value);
                }
                $args[$propertyName] = $value;
            } elseif ($arrayPropertyMap !== null) {
                $args[$arrayPropertyMap->getName()][] = $value;
            }
        }

        if (method_exists($this->entityClass,  '__construct') === false)
        {
            throw new \LogicException("Constructor for the class <strong>$this->entityClass</strong> does not exist, you should not pass arguments to the constructor of this class!");
        }

        $refMethod = new \ReflectionMethod($this->entityClass,  '__construct');
        $params = $refMethod->getParameters();

        $sortedArgs = [];

        foreach($params as $key => $param)
        {
            if ($param->isPassedByReference()) {
                $sortedArgs[$key] = &$args[$param->getName()];
            } else {
                $sortedArgs[$key] = $args[$param->getName()];
            }
        }

        if ($reflectionClass->isInstantiable()) {
            return $reflectionClass->newInstanceArgs($sortedArgs);
        }

        return null;
    }

    /**
     * @param array $rows
     * @return array|object[]
     * @throws \ReflectionException
     */
    protected function hydrateAll(array $rows): array
    {
        $entities = [];
        foreach ($rows as $row) {
            $entity = $this->hydrate($row);
            if ($entity) {
                $entities[] = $entity;
            }
        }
        return $entities;
    }

    /**
     * @param PropertyMap[] $propertiesMap
     * @param object $entity
     * @return array
     */
    protected function prepareKeyValues(array $propertiesMap, $entity): array
    {
        $keyValues = [];
        foreach ($propertiesMap as $key => $propertyMap) {
            $propertyName = $propertyMap->getName();
            try {
                $value = $entity->$propertyName;
            } catch (\Throwable $exception) {
                try {
                    $getValue = $this->buildGetterName($propertyMap->getName());
                    $value = $entity->$getValue();
                } catch (\Throwable $exception) {
                    throw new AnnotationException("Can not get property value. Property {$propertyName} is not public and has no getter.");
                }
            }

            if ($propertyMap->getType() === 'array') {
                foreach ($value as $index => $item) {
                    $keyValues[$index] = $item;
                }
            } else {
                $keyValues[$key] = $value;
            }
        }
        return $keyValues;
    }

    /**
     * @param string $propertyName
     * @return string
     */
    protected function buildGetterName(string $propertyName): string
    {
        return 'get' . ucfirst($propertyName);
    }
}
