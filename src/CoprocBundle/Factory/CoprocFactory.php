<?php
namespace IvixLabs\CoprocBundle\Factory;

use IvixLabs\Coproc\CoprocDemux;
use IvixLabs\Coproc\CoprocSlave;
use Symfony\Component\HttpKernel\KernelInterface;

class CoprocFactory
{
    const DEMUX_SIZE = 'size';
    const DEMUX_MAX_CYCLES = 'maxCycles';
    const DEMUX_MAX_MESSAGES = 'maxMessages';

    const SLAVE_SERVICE_ID = 'serviceId';
    const SLAVE_METHOD_NAME = 'methodName';
    const SLAVE_SERVICE = 'service';

    /**
     * @var KernelInterface
     */
    private $kernel;

    private $callbacks;

    private $services;

    /**
     * @var string
     */
    private $consolePath;

    public function __construct(
        $consolePath,
        KernelInterface $kernel,
        array $services,
        array $callbacks
    )
    {
        $this->consolePath = $consolePath;
        $this->kernel = $kernel;
        $this->services = $services;
        $this->callbacks = $callbacks;
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

        $env = $this->kernel->getEnvironment();
        $debug = $this->kernel->isDebug();

        $cmd = "php $this->consolePath ivixlabs:coproc:slave-launcher $name --env=$env";
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
            return call_user_func_array(
                [$callback[self::SLAVE_SERVICE], $callback[self::SLAVE_METHOD_NAME]],
                [$msgs, $coprocSlave]
            );
        });

        return $slave;
    }

    private function getCallback($name)
    {
        if (isset($this->callbacks[$name])) {
            $callback = $this->callbacks[$name];
            $callback[self::SLAVE_SERVICE] = $this->services[$callback[self::SLAVE_SERVICE_ID]];
            return $callback;
        }

        throw new \RuntimeException('Callback with name not found: ' . $name);
    }
}