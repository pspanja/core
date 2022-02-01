<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Bundle\IO\DependencyInjection\Compiler;

use ArrayObject;
use Ibexa\Bundle\IO\DependencyInjection\Compiler\IOConfigurationPass;
use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class IOConfigurationPassTest extends AbstractCompilerPassTestCase
{
    /** @var \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $metadataConfigurationFactoryMock;

    /** @var \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $binarydataConfigurationFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->setParameter('ibexa.io.metadata_handlers', []);
        $this->container->setParameter('ibexa.io.binarydata_handlers', []);

        $this->container->setDefinition('ibexa.core.io.binarydata_handler.registry', new Definition());
        $this->container->setDefinition('ibexa.core.io.metadata_handler.registry', new Definition());
        $this->container->setDefinition('ibexa.core.io.binarydata_handler.flysystem.default', new Definition());
        $this->container->setDefinition('ibexa.core.io.metadata_handler.flysystem.default', new Definition());
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $this->metadataConfigurationFactoryMock = $this->createMock(ConfigurationFactory::class);
        $this->binarydataConfigurationFactoryMock = $this->createMock(ConfigurationFactory::class);

        $container->addCompilerPass(
            new IOConfigurationPass(
                new ArrayObject(
                    ['test_handler' => $this->metadataConfigurationFactoryMock]
                ),
                new ArrayObject(
                    ['test_handler' => $this->binarydataConfigurationFactoryMock]
                )
            )
        );
    }

    /**
     * Tests that the default handlers are available when nothing is configured.
     */
    public function testDefaultHandlers()
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'ibexa.core.io.binarydata_handler.registry',
            'setHandlersMap',
            [['default' => 'ibexa.core.io.binarydata_handler.flysystem.default']]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'ibexa.core.io.metadata_handler.registry',
            'setHandlersMap',
            [['default' => 'ibexa.core.io.metadata_handler.flysystem.default']]
        );
    }

    public function testBinarydataHandler()
    {
        $this->container->setParameter(
            'ibexa.io.binarydata_handlers',
            ['my_handler' => ['name' => 'my_handler', 'type' => 'test_handler']]
        );

        $this->binarydataConfigurationFactoryMock
            ->expects($this->once())
            ->method('getParentServiceId')
            ->will($this->returnValue('test.io.binarydata_handler.test_handler'));

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'test.io.binarydata_handler.test_handler.my_handler',
            'test.io.binarydata_handler.test_handler'
        );
    }

    public function testMetadataHandler()
    {
        $this->container->setParameter(
            'ibexa.io.metadata_handlers',
            ['my_handler' => ['name' => 'my_handler', 'type' => 'test_handler']]
        );

        $this->metadataConfigurationFactoryMock
            ->expects($this->once())
            ->method('getParentServiceId')
            ->will($this->returnValue('test.io.metadata_handler.test_handler'));

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'test.io.metadata_handler.test_handler.my_handler',
            'test.io.metadata_handler.test_handler'
        );
    }

    public function testUnknownMetadataHandler()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unknown handler');

        $this->container->setParameter(
            'ibexa.io.metadata_handlers',
            ['test' => ['type' => 'unknown']]
        );

        $this->compile();
    }

    public function testUnknownBinarydataHandler()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unknown handler');

        $this->container->setParameter(
            'ibexa.io.binarydata_handlers',
            ['test' => ['type' => 'unknown']]
        );

        $this->compile();
    }
}

class_alias(IOConfigurationPassTest::class, 'eZ\Bundle\EzPublishIOBundle\Tests\DependencyInjection\Compiler\IOConfigurationPassTest');
