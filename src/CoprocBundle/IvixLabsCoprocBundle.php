<?php
namespace IvixLabs\CoprocBundle;

use IvixLabs\CoprocBundle\DependencyInjection\CoprocSlaveCompilerPass;
use IvixLabs\CoprocBundle\Manager\SlaveAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class IvixLabsCoprocBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container
            ->registerForAutoconfiguration(SlaveAwareInterface::class)
            ->addTag('ivixlabs.coproc.slave');
        $container->addCompilerPass(new CoprocSlaveCompilerPass());
    }

}
