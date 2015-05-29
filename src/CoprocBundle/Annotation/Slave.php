<?php
namespace IvixLabs\CoprocBundle\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Slave
{

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $useClassName = true;

    /**
     * @var int
     */
    public $size;

    /**
     * @var int
     */
    public $maxMessages;

    /**
     * @var int
     */
    public $maxCycles;
}