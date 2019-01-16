<?php
/**
 * Created by PhpStorm.
 * Date: 14.01.19
 * Time: 16:35
 */

namespace InfluxDB\ORM\Mapping\Annotations;

/**
 * https://docs.influxdata.com/influxdb/v1.7/concepts/glossary/#field
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Field
{
    /**
     * @var string
     * @Required
     */
    public $key;
}