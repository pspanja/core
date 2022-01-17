<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\IO\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class MigrationFileListerPass implements CompilerPassInterface
{
    /**
     * Registers the FileListerInterface into the file lister registry.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('ezpublish.core.io.migration.file_lister_registry')) {
            return;
        }

        $fileListersTagged = $container->findTaggedServiceIds('ibexa.io.migration.file_lister');

        $fileListers = [];
        foreach ($fileListersTagged as $id => $tags) {
            foreach ($tags as $attributes) {
                $fileListers[$attributes['identifier']] = new Reference($id);
            }
        }

        $fileListerRegistryDef = $container->findDefinition('ezpublish.core.io.migration.file_lister_registry');
        $fileListerRegistryDef->setArguments([$fileListers]);
    }
}

class_alias(MigrationFileListerPass::class, 'eZ\Bundle\EzPublishIOBundle\DependencyInjection\Compiler\MigrationFileListerPass');
