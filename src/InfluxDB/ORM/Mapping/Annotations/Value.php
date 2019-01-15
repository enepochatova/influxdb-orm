<?php
/**
 * Created by PhpStorm.
 * Date: 14.01.19
 * Time: 16:36
 */

namespace InfluxDB\ORM\Mapping\Annotations;

/**
 * The special property which will be stored as Field with field-key = 'value'.
 * https://docs.influxdata.com/influxdb/v1.7/concepts/glossary/#field
 * Property which has this annotation can be only of numeric type.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Value
{
}