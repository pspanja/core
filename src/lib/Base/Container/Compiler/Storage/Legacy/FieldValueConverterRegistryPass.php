<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Base\Container\Compiler\Storage\Legacy;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass will register Legacy Storage field value converters.
 */
class FieldValueConverterRegistryPass implements CompilerPassInterface
{
    public const CONVERTER_REGISTRY_SERVICE_ID = 'ezpublish.persistence.legacy.field_value_converter.registry';

    public const CONVERTER_SERVICE_TAG = 'ezplatform.field_type.legacy_storage.converter';

    public const CONVERTER_SERVICE_TAGS = [
        self::CONVERTER_SERVICE_TAG,
    ];

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::CONVERTER_REGISTRY_SERVICE_ID)) {
            return;
        }

        $registry = $container->getDefinition(self::CONVERTER_REGISTRY_SERVICE_ID);

        $serviceTags = $container->findTaggedServiceIds(self::CONVERTER_SERVICE_TAG);

        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::CONVERTER_SERVICE_TAG
                        )
                    );
                }

                $registry->addMethodCall(
                    'register',
                    [
                        $attribute['alias'],
                        new Reference($serviceId),
                    ]
                );
            }
        }
    }
}

class_alias(FieldValueConverterRegistryPass::class, 'eZ\Publish\Core\Base\Container\Compiler\Storage\Legacy\FieldValueConverterRegistryPass');
