<?php
namespace IvixLabs\CoprocBundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use IvixLabs\CoprocBundle\Annotation\Slave;
use IvixLabs\CoprocBundle\Factory\DemuxFactory;
use IvixLabs\CoprocBundle\Factory\SlaveFactory;
use IvixLabs\CoprocBundle\Manager\CallbackConfigManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;


class CoprocSlaveCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {

        $callbackConfigManager = $container->getDefinition('ivixlabs.coproc.manager.callback_config');
        $slaveFactory = $container->getDefinition('ivixlabs.coproc.factory.slave');

        $tag = 'ivixlabs.coproc.slave';

        $callbacks = [];
        $coprocServices = [];
        $services = $container->findTaggedServiceIds($tag);
        foreach ($services as $id => $tagAttributes) {
            $slaveDef = $container->getDefinition($id);

            $this->initCallbacks($id, $slaveDef->getClass(), $callbacks);
            $coprocServices[$id] = new Reference($id);
        }

        $slaveFactory->addArgument($coprocServices);
        $callbackConfigManager->addArgument($callbacks);
    }

    private function initCallbacks($id, $className, array &$callbacks)
    {
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

                    $callback = array(CallbackConfigManager::SLAVE_SERVICE_ID => $id, CallbackConfigManager::SLAVE_METHOD_NAME => $method->name);

                    if ($annotation->size !== null) {
                        $callback[CallbackConfigManager::DEMUX_SIZE] = $annotation->size;
                    }

                    if ($annotation->maxCycles !== null) {
                        $callback[CallbackConfigManager::DEMUX_MAX_CYCLES] = $annotation->maxCycles;
                    }

                    if ($annotation->maxMessages !== null) {
                        $callback[CallbackConfigManager::DEMUX_MAX_MESSAGES] = $annotation->maxMessages;
                    }

                    $callbacks[$slaveName] = $callback;
                }
            }
        }

        return $callbacks;
    }


}