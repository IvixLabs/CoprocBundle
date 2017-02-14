<?php
namespace IvixLabs\CoprocBundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use IvixLabs\CoprocBundle\Annotation\Slave;
use IvixLabs\CoprocBundle\Factory\CoprocFactory;
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
        $serviceId = 'ivixlabs.coproc.factory';


        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $definition = $container->getDefinition($serviceId);

        $tag = 'ivixlabs.coproc.slave';

        $callbacks = [];
        $coprocServices = [];
        $services = $container->findTaggedServiceIds($tag);
        foreach ($services as $id => $tagAttributes) {
            $slaveDef = $container->getDefinition($id);

            $this->initCallbacks($id, $slaveDef->getClass(), $callbacks);
            $coprocServices[$id] = new Reference($id);
        }


        $definition->addArgument($coprocServices);
        $definition->addArgument($callbacks);
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

                    $callback = array(CoprocFactory::SLAVE_SERVICE_ID => $id, CoprocFactory::SLAVE_METHOD_NAME => $method->name);

                    if ($annotation->size !== null) {
                        $callback[CoprocFactory::DEMUX_SIZE] = $annotation->size;
                    }

                    if ($annotation->maxCycles !== null) {
                        $callback[CoprocFactory::DEMUX_MAX_CYCLES] = $annotation->maxCycles;
                    }

                    if ($annotation->maxMessages !== null) {
                        $callback[CoprocFactory::DEMUX_MAX_MESSAGES] = $annotation->maxMessages;
                    }

                    $callbacks[$slaveName] = $callback;
                }
            }
        }

        return $callbacks;
    }


}