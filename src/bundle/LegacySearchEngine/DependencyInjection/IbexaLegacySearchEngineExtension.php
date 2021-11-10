<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\LegacySearchEngine\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class IbexaLegacySearchEngineExtension extends Extension
{
    public function getAlias()
    {
        return 'ez_search_engine_legacy';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        // Loading configuration from ./src/lib/Resources/settings/policies.yml
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../../lib/Resources/settings')
        );
        $loader->load('search_engines/legacy.yml');

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yml');
    }
}

class_alias(IbexaLegacySearchEngineExtension::class, 'eZ\Bundle\EzPublishLegacySearchEngineBundle\DependencyInjection\EzPublishLegacySearchEngineExtension');
