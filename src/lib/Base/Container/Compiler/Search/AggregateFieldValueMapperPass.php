<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Base\Container\Compiler\Search;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass will register Search Engine field value mappers.
 */
class AggregateFieldValueMapperPass implements CompilerPassInterface
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ezpublish.search.common.field_value_mapper.aggregate')) {
            return;
        }

        $aggregateFieldValueMapperDefinition = $container->getDefinition(
            'ezpublish.search.common.field_value_mapper.aggregate'
        );

        $taggedServiceIds = $container->findTaggedServiceIds(
            'ibexa.search.common.field_value.mapper'
        );
        foreach ($taggedServiceIds as $id => $attributes) {
            $aggregateFieldValueMapperDefinition->addMethodCall(
                'addMapper',
                [new Reference($id)]
            );
        }
    }
}

class_alias(AggregateFieldValueMapperPass::class, 'eZ\Publish\Core\Base\Container\Compiler\Search\AggregateFieldValueMapperPass');
