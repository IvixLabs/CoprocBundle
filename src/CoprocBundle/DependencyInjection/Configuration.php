<?php

namespace IvixLabs\CoprocBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ivixlabs_coproc');

        $rootNode
            ->children()
                ->scalarNode('console_path')->end()
            ->end();

        return $treeBuilder;
    }
}
