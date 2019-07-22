<?php

namespace IvixLabs\CoprocBundle\Factory;

use IvixLabs\Coproc\CoprocDemux;
use IvixLabs\Coproc\CoprocSlave;
use IvixLabs\CoprocBundle\Manager\CallbackConfigManager;
use Symfony\Component\HttpKernel\KernelInterface;

class DemuxFactory
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var string
     */
    private $consolePath;

    /**
     * @var CallbackConfigManager
     */
    private $callbackConfigManager;

    public function __construct(
        CallbackConfigManager $callbackConfigManager,
        $consolePath,
        KernelInterface $kernel
    ) {
        $this->callbackConfigManager = $callbackConfigManager;
        $this->consolePath = $consolePath;
        $this->kernel = $kernel;
    }

    /**
     * @param $groupName
     * @param $name
     *
     * @return CoprocDemux
     */
    public function createDemux($groupName, $name = null)
    {
        if ($name !== null) {
            if (is_object($groupName)) {
                $name = get_class($groupName).$name;
            } else {
                $name = $groupName.$name;
            }
        } else {
            $name = $groupName;
        }
        $name = preg_replace('/[^[:alnum:]]/', '', $name);

        $callbackConfig = $this->callbackConfigManager->getCallbackConfig($name);

        $demux = new CoprocDemux();
        if (isset($callbackConfig[CallbackConfigManager::DEMUX_SIZE])) {
            $demux->setSize($callbackConfig[CallbackConfigManager::DEMUX_SIZE]);
        }

        if (isset($callbackConfig[CallbackConfigManager::DEMUX_MAX_CYCLES])) {
            $demux->setMaxCycles($callbackConfig[CallbackConfigManager::DEMUX_MAX_CYCLES]);
        }

        if (isset($callbackConfig[CallbackConfigManager::DEMUX_MAX_MESSAGES])) {
            $demux->setMaxMessages($callbackConfig[CallbackConfigManager::DEMUX_MAX_MESSAGES]);
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
}