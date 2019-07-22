<?php

namespace IvixLabs\CoprocBundle\Factory;

use IvixLabs\Coproc\CoprocSlave;
use IvixLabs\CoprocBundle\Manager\CallbackConfigManager;

class SlaveFactory
{
    /**
     * @var CallbackConfigManager
     */
    private $callbackConfigManager;

    private $services;

    public function __construct(
        CallbackConfigManager $callbackConfigManager,
        array $services = []
    ) {
        $this->callbackConfigManager = $callbackConfigManager;
        $this->services = $services;
    }

    public function createSlave($name)
    {
        $callbackConfig = $this->callbackConfigManager->getCallbackConfig($name);

        $service = $this->services[$callbackConfig[CallbackConfigManager::SLAVE_SERVICE_ID]];

        $slave = new CoprocSlave();
        $slave->setCallback(
            function ($msgs, $coprocSlave) use (&$callbackConfig, $service) {
                return call_user_func_array(
                    [$service, $callbackConfig[CallbackConfigManager::SLAVE_METHOD_NAME]],
                    [$msgs, $coprocSlave]
                );
            });

        return $slave;
    }
}