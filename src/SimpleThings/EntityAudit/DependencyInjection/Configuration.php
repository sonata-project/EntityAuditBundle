<?php

namespace SimpleThings\EntityAudit\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $builder->root('simple_things_entity_audit')
            ->children()
                ->arrayNode('audited_entities')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('table_prefix')->defaultValue('')->end()
                ->scalarNode('table_suffix')->defaultValue('_audit')->end()
                ->scalarNode('revision_field_name')->defaultValue('rev')->end()
                ->scalarNode('revision_type_field_name')->defaultValue('revtype')->end()
                ->scalarNode('revision_diff_field_name')->defaultValue('diff')->end()
                ->scalarNode('revision_table_name')->defaultValue('revisions')->end()
                ->scalarNode('revision_id_field_type')->defaultValue('integer')->end()
                ->scalarNode('revision_description_field_name')->defaultValue('description')->end()
                ->scalarNode('revision_description_field_type')->defaultValue('text')->end()
                ->scalarNode('revision_processed_field_name')->defaultValue('processed_at')->end()
                ->scalarNode('revision_processed_field_type')->defaultValue('datetime')->end()
            ->end();
        return $builder;
    }
}
