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

                // revisions table
                ->scalarNode('revision_table_name')->defaultValue('revisions')->end()
                ->scalarNode('revision_id_field_name')->defaultValue('id')->end()
                ->scalarNode('revision_id_field_type')->defaultValue('integer')->end()
                ->scalarNode('revision_timestamp_field_name')->defaultValue('timestamp')->end()
                ->scalarNode('revision_username_field_name')->defaultValue('username')->end()

                // History table
                ->scalarNode('hist_revision_field_name')->defaultValue('rev')->end()
                ->scalarNode('hist_type_field_name')->defaultValue('revtype')->end()

                // sequence name
                ->scalarNode('revision_sequence_name')->defaultValue('revisions_seq')->end()
            ->end();
        return $builder;
    }
}
