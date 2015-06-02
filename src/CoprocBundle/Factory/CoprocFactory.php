<?php
namespace IvixLabs\CoprocBundle\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\Cache;
use IvixLabs\Coproc\CoprocDemux;
use IvixLabs\Coproc\CoprocSlave;
use IvixLabs\CoprocBundle\Annotation\Slave;
use Symfony\Component\HttpKernel\Kernel;

class CoprocFactory
{
    const DEMUX_SIZE = 'size';
    const DEMUX_MAX_CYCLES = 'maxCycles';
    const DEMUX_MAX_MESSAGES = 'maxMessages';

    const SLAVE_SERVICE_ID = 'serviceId';
    const SLAVE_METHOD_NAME = 'methodName';
    const SLAVE_SERVICE = 'service';
    /**
     * @var Kernel
     */
    private $kernel;

    private $callbacks;

    private $services = array();

    /**
     * @var Cache
     */
    private $cache;

    public function addCoprocSlaveService($service, $id)
    {
        $this->services[$id] = $service;
    }

    /**
     * @param $groupName
     * @param $name
     * @return CoprocDemux
     */
    public function getDemux($groupName, $name = null)
    {
        if ($name !== null) {
            if (is_object($groupName)) {
                $name = get_class($groupName) . $name;
            } else {
                $name = $groupName . $name;
            }
        } else {
            $name = $groupName;
        }
        $name = preg_replace('/[^[:alnum:]]/', '', $name);

        $callback = $this->getCallback($name);

        $demux = new CoprocDemux();
        if (isset($callback[self::DEMUX_SIZE])) {
            $demux->setSize($callback[self::DEMUX_SIZE]);
        }

        if (isset($callback[self::DEMUX_MAX_CYCLES])) {
            $demux->setMaxCycles($callback[self::DEMUX_MAX_CYCLES]);
        }

        if (isset($callback[self::DEMUX_MAX_MESSAGES])) {
            $demux->setMaxMessages($callback[self::DEMUX_MAX_MESSAGES]);
        }


        $dir = $this->kernel->getRootDir();
        $env = $this->kernel->getEnvironment();
        $debug = $this->kernel->isDebug();

        $cmd = "php $dir/console ivixlabs:coproc:slave-launcher $name --env=$env";
        if (!$debug) {
            $cmd .= ' --no-debug';
        }
        $demux->setCliCommand($cmd);
        return $demux;
    }

    /**
     * @param $name
     * @return CoprocSlave
     */
    public function getSlave($name)
    {
        $callback = $this->getCallback($name);
        if ($callback === null) {
            throw new \RuntimeException('Slave with name: ' . $name . ' is not found');
        }

        $slave = new CoprocSlave();
        $slave->setCallback(function ($msgs, $coprocSlave) use (&$callback) {
            return call_user_func_array(array($callback[self::SLAVE_SERVICE], $callback[self::SLAVE_METHOD_NAME]), [$msgs, $coprocSlave]);
        });

        return $slave;
    }

    private function getCallback($name)
    {
        $cacheKey = 'callbacks';
        if ($this->callbacks === null) {
            $callbacks = $this->cache->fetch($cacheKey);
            if ($callbacks === false || $this->kernel->isDebug()) {
                $callbacks = array();
                foreach ($this->services as $id => $service) {
                    $className = get_class($service);
                    $reflectionClass = new \ReflectionClass($className);
                    $reader = new AnnotationReader();

                    $methods = $reflectionClass->getMethods();
                    foreach ($methods as $method) {
                        $classAnnotations = $reader->getMethodAnnotations($method);
                        foreach ($classAnnotations AS $annotation) {
                            if ($annotation instanceof Slave) {
                                if ($annotation->useClassName) {
                                    $slaveName = $className . $annotation->name;
                                } else {
                                    $slaveName = $annotation->name;
                                }

                                $slaveName = preg_replace('/[^[:alnum:]]/', '', $slaveName);

                                if (isset($callbacks[$slaveName])) {
                                    throw new \RuntimeException('Duplication slave name: ' . $slaveName);
                                }

                                $callback = array(self::SLAVE_SERVICE_ID => $id, self::SLAVE_METHOD_NAME => $method->name);

                                if ($annotation->size !== null) {
                                    $callback[self::DEMUX_SIZE] = $annotation->size;
                                }

                                if ($annotation->maxCycles !== null) {
                                    $callback[self::DEMUX_MAX_CYCLES] = $annotation->maxCycles;
                                }

                                if ($annotation->maxMessages !== null) {
                                    $callback[self::DEMUX_MAX_MESSAGES] = $annotation->maxMessages;
                                }

                                $callbacks[$slaveName] = $callback;
                            }
                        }
                    }
                }
                $this->cache->save($cacheKey, $callbacks);
            }
            $this->callbacks = $callbacks;
        }

        if (isset($this->callbacks[$name])) {
            $callback = $this->callbacks[$name];
            $callback[self::SLAVE_SERVICE] = $this->services[$callback[self::SLAVE_SERVICE_ID]];
            return $callback;
        }

        throw new \RuntimeException('Callback with name not found: ' . $name);
    }

    public function setKernel(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }
}