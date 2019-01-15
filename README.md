# InfluxDB ORM
The PHP library which helps to convert entities to InfluxDB points and back. 
Uses annotation for entities configuration. 
For better understanding of concepts please see official InfluxDB documentation.

# Installation

```bash
composer require enepochatova/influxdb-orm
```

# Getting started

Create entity to be stored in DB. Give it the <b>Measurement</b> annotation. The <i>"name"</i> property of annotation is required.

```php
<?php

namespace SomeNamespace;

use InfluxDB\ORM\Mapping\Annotations as InfluxDB;

/**
 * Class Cpu
 * @InfluxDB\Measurement(name="cpu")
 */
class Cpu
{
}
```

Then add some properties to class and annotations for them. 
The annotations for properties are:
 - <b>Value</b>: Must be given to numeric property and be unique among class hierarchy. 
 If this annotation is not given to any property of class hierarchy, 
 it will be equal to null and the field 'value' will not be created.
 - <b>Field</b>: Has required "key" property, which is very similar to column name in relational DB.
 - <b>Tag</b>: Has required "key" and optional "type" properties. By default "type" is "string". 
  The other valid values are: "int", "float", "bool". 
  Be attended that <b>tags</b> are always stored as strings in DB. 
  That's why we need to specify the original type of class property for convert it back 
  and build entity correctly after we got data from DB.
 - <b>Timestamp</b>: Must be given to numeric property and be unique among class hierarchy. 
 If this annotation is not given to any property of class hierarchy, 
 it will be equal to null and the point will be stored with current server timestamp.
 
 Example:
 
```php
<?php

namespace SomeNamespace;

use InfluxDB\ORM\Mapping\Annotations as InfluxDB;

/**
 * Class Cpu
 * @InfluxDB\Measurement(name="cpu")
 */
class Cpu
{
    /**
     * @var float
     * @InfluxDB\Value
     */
    private $value;

    /**
     * @var int
     * @InfluxDB\Field(key="tasks")
     */
    private $tasks;

    /**
     * @var int
     * @InfluxDB\Field(key="services")
     */
    private $activeServices;

    /**
     * @var string
     * @InfluxDB\Field(key="host")
     */
    private $host;

    /**
     * @var int
     * @InfluxDB\Tag(key="category", type="int")
     */
    private $categoryId;

    /**
     * Cpu constructor.
     * @param float $value
     * @param int $tasks
     * @param int $activeServices
     * @param string $host
     * @param int $categoryId
     */
    public function __construct($value, $tasks, $activeServices, $host, $categoryId)
    {
        $this->value = $value;
        $this->tasks = $tasks;
        $this->activeServices = $activeServices;
        $this->host = $host;
        $this->categoryId = $categoryId;
    }

}
```

Also it is possible to map <b>inherited</b> classes. 
The reading of annotations starts from the target class and goes to its parents.
Annotations from children classes have higher priority.

After the entity is done, we need to create the Repository class which extends BaseRepository from this library: 

```php
<?php

namespace SomeNamespace;


use InfluxDB\Database;
use InfluxDB\ORM\Mapping\AnnotationDriver;
use InfluxDB\ORM\Repository\BaseRepository;

class CpuRepository extends BaseRepository
{
    /**
     * CpuRepository constructor.
     * @param AnnotationDriver $annotationDriver
     * @param Database $database
     * @param string|null $precision
     * @throws \ReflectionException
     */
    public function __construct(AnnotationDriver $annotationDriver, Database $database, string $precision = null)
    {
        parent::__construct($annotationDriver, $database, Cpu::class, $precision);
    }
}
```

That's it!

Now you are ready to use it like: 

```php
<?php

...

$cpuRepositoryInstance->write($cpuInstance);

$cpuMetrics = $cpuRepositoryInstance->findAll();
... 
```


Enjoy!





