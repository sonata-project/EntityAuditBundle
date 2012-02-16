<?php

namespace SimpleThings\EntityAudit\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {
    public function getConfigTreeBuilder() {
        $builder = new TreeBuilder();
        $builder->root('simple_things_entity_audit')
            ->children()
            ->arrayNode('audited_entities')
            ->prototype('scalar')->end()
            ->end()
            ->scalarNode('table_prefix')->end()
            ->scalarNode('table_suffix')->end()
            ->scalarNode('revision_field_name')->end()
            ->scalarNode('revision_type_field_name')->end()
            ->scalarNode('revision_table_name')->end()
            ->scalarNode('revision_id_field_type')->end()
            ->end();
        return $builder;
    }
}
