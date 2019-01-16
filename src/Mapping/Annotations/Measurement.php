<?php
/**
 * Created by PhpStorm.
 * Date: 14.01.19
 * Time: 16:19
 */

namespace InfluxDB\ORM\Mapping\Annotations;

/**
 * https://docs.influxdata.com/influxdb/v1.7/concepts/glossary/#measurement
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Measurement
{
    /**
     * @var string
     * @Required
     */
    public $name;
}