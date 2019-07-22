<?php

namespace IvixLabs\CoprocBundle\Manager;

class CallbackConfigManager
{
    const DEMUX_SIZE = 'size';
    const DEMUX_MAX_CYCLES = 'maxCycles';
    const DEMUX_MAX_MESSAGES = 'maxMessages';
    const SLAVE_SERVICE_ID = 'serviceId';
    const SLAVE_METHOD_NAME = 'methodName';

    /**
     * @var array
     */
    private $callbackConfigs;

    public function __construct(array $callbackConfigs = [])
    {
        $this->callbackConfigs = $callbackConfigs;
    }

    public function getCallbackConfig($name)
    {
        if (isset($this->callbackConfigs[$name])) {
            return $this->callbackConfigs[$name];
        }

        throw new \RuntimeException('Callback with name not found: '.$name);
    }
}