<?php
/**
 * Created by PhpStorm.
 * Date: 14.01.19
 * Time: 16:29
 */

namespace InfluxDB\ORM\Mapping\Annotations;

/**
 * https://docs.influxdata.com/influxdb/v1.7/concepts/glossary/#tag
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Tag
{
    /**
     * @var string
     * @Required
     */
    public $key;

    /**
     * Tag values are always stored as strings in DB.
     * So we need to specify the original type of class property
     * for convert it back and build entity correctly
     * after we got data from DB.
     *
     * @var string
     * @Enum({"string", "int", "float", "bool"})
     */
    public $type = 'string';
}