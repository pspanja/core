<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Bundle\Core;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;
use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigResolverTest extends TestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess */
    private $siteAccess;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $containerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteAccess = new SiteAccess('test');
        $this->containerMock = $this->createMock(ContainerInterface::class);
    }

    /**
     * @param string $defaultNS
     * @param int $undefinedStrategy
     * @param array $groupsBySiteAccess
     *
     * @return \Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver
     */
    private function getResolver($defaultNS = 'ibexa.site_access.config', $undefinedStrategy = ConfigResolver::UNDEFINED_STRATEGY_EXCEPTION, array $groupsBySiteAccess = [])
    {
        $configResolver = new ConfigResolver(
            null,
            $groupsBySiteAccess,
            $defaultNS,
            $undefinedStrategy
        );
        $configResolver->setSiteAccess($this->siteAccess);
        $configResolver->setContainer($this->containerMock);

        return $configResolver;
    }

    public function testGetSetUndefinedStrategy()
    {
        $strategy = ConfigResolver::UNDEFINED_STRATEGY_NULL;
        $defaultNS = 'ibexa.site_access.config';
        $resolver = $this->getResolver($defaultNS, $strategy);

        $this->assertSame($strategy, $resolver->getUndefinedStrategy());
        $resolver->setUndefinedStrategy(ConfigResolver::UNDEFINED_STRATEGY_EXCEPTION);
        $this->assertSame(ConfigResolver::UNDEFINED_STRATEGY_EXCEPTION, $resolver->getUndefinedStrategy());

        $this->assertSame($defaultNS, $resolver->getDefaultNamespace());
        $resolver->setDefaultNamespace('anotherNamespace');
        $this->assertSame('anotherNamespace', $resolver->getDefaultNamespace());
    }

    public function testGetParameterFailedWithException()
    {
        $this->expectException(ParameterNotFoundException::class);

        $resolver = $this->getResolver('ibexa.site_access.config', ConfigResolver::UNDEFINED_STRATEGY_EXCEPTION);
        $resolver->getParameter('foo');
    }

    public function testGetParameterFailedNull()
    {
        $resolver = $this->getResolver('ibexa.site_access.config', ConfigResolver::UNDEFINED_STRATEGY_NULL);
        $this->assertNull($resolver->getParameter('foo'));
    }

    public function parameterProvider()
    {
        return [
            ['foo', 'bar'],
            ['some.parameter', true],
            ['some.other.parameter', ['foo', 'bar', 'baz']],
            ['a.hash.parameter', ['foo' => 'bar', 'tata' => 'toto']],
            [
                'a.deep.hash', [
                    'foo' => 'bar',
                    'tata' => 'toto',
                    'deeper_hash' => [
                        'likeStarWars' => true,
                        'jedi' => ['Obi-Wan Kenobi', 'Mace Windu', 'Luke Skywalker', 'Leïa Skywalker (yes! Read episodes 7-8-9!)'],
                        'sith' => ['Darth Vader', 'Darth Maul', 'Palpatine'],
                        'roles' => [
                            'Amidala' => ['Queen'],
                            'Palpatine' => ['Senator', 'Emperor', 'Villain'],
                            'C3PO' => ['Droid', 'Annoying guy'],
                            'Jar-Jar' => ['Still wondering his role', 'Annoying guy'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testGetParameterGlobalScope($paramName, $expectedValue)
    {
        $globalScopeParameter = "ibexa.site_access.config.global.$paramName";
        $this->containerMock
            ->expects($this->once())
            ->method('hasParameter')
            ->with($globalScopeParameter)
            ->will($this->returnValue(true));
        $this->containerMock
            ->expects($this->once())
            ->method('getParameter')
            ->with($globalScopeParameter)
            ->will($this->returnValue($expectedValue));

        $this->assertSame($expectedValue, $this->getResolver()->getParameter($paramName));
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testGetParameterRelativeScope($paramName, $expectedValue)
    {
        $relativeScopeParameter = "ibexa.site_access.config.{$this->siteAccess->name}.$paramName";
        $this->containerMock
            ->expects($this->exactly(2))
            ->method('hasParameter')
            ->with(
                $this->logicalOr(
                    "ibexa.site_access.config.global.$paramName",
                    $relativeScopeParameter
                )
            )
            // First call is for "global" scope, second is the right one
            ->will($this->onConsecutiveCalls(false, true));
        $this->containerMock
            ->expects($this->once())
            ->method('getParameter')
            ->with($relativeScopeParameter)
            ->will($this->returnValue($expectedValue));

        $this->assertSame($expectedValue, $this->getResolver()->getParameter($paramName));
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testGetParameterSpecificScope($paramName, $expectedValue)
    {
        $scope = 'some_siteaccess';
        $relativeScopeParameter = "ibexa.site_access.config.$scope.$paramName";
        $this->containerMock
            ->expects($this->exactly(2))
            ->method('hasParameter')
            ->with(
                $this->logicalOr(
                    "ibexa.site_access.config.global.$paramName",
                    $relativeScopeParameter
                )
            )
        // First call is for "global" scope, second is the right one
            ->will($this->onConsecutiveCalls(false, true));
        $this->containerMock
            ->expects($this->once())
            ->method('getParameter')
            ->with($relativeScopeParameter)
            ->will($this->returnValue($expectedValue));

        $this->assertSame(
            $expectedValue,
            $this->getResolver()->getParameter($paramName, 'ibexa.site_access.config', $scope)
        );
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testGetParameterDefaultScope($paramName, $expectedValue)
    {
        $defaultScopeParameter = "ibexa.site_access.config.default.$paramName";
        $relativeScopeParameter = "ibexa.site_access.config.{$this->siteAccess->name}.$paramName";
        $this->containerMock
            ->expects($this->exactly(3))
            ->method('hasParameter')
            ->with(
                $this->logicalOr(
                    "ibexa.site_access.config.global.$paramName",
                    $relativeScopeParameter,
                    $defaultScopeParameter
                )
            )
            // First call is for "global" scope, second is the right one
            ->will($this->onConsecutiveCalls(false, false, true));
        $this->containerMock
            ->expects($this->once())
            ->method('getParameter')
            ->with($defaultScopeParameter)
            ->will($this->returnValue($expectedValue));

        $this->assertSame($expectedValue, $this->getResolver()->getParameter($paramName));
    }

    public function hasParameterProvider()
    {
        return [
            [true, true, true, true, true],
            [true, true, true, false, true],
            [true, true, false, false, true],
            [false, false, false, false, false],
            [false, false, true, false, true],
            [false, false, false, true, true],
            [false, false, true, true, true],
            [false, true, false, false, true],
        ];
    }

    /**
     * @dataProvider hasParameterProvider
     */
    public function testHasParameterNoNamespace($defaultMatch, $groupMatch, $scopeMatch, $globalMatch, $expectedResult)
    {
        $paramName = 'foo.bar';
        $groupName = 'my_group';
        $configResolver = $this->getResolver(
            'ibexa.site_access.config',
            ConfigResolver::UNDEFINED_STRATEGY_EXCEPTION,
            [$this->siteAccess->name => [$groupName]]
        );

        $this->containerMock->expects($this->atLeastOnce())
            ->method('hasParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ["ibexa.site_access.config.default.$paramName", $defaultMatch],
                        ["ibexa.site_access.config.$groupName.$paramName", $groupMatch],
                        ["ibexa.site_access.config.{$this->siteAccess->name}.$paramName", $scopeMatch],
                        ["ibexa.site_access.config.global.$paramName", $globalMatch],
                    ]
                )
            );

        $this->assertSame($expectedResult, $configResolver->hasParameter($paramName));
    }

    /**
     * @dataProvider hasParameterProvider
     */
    public function testHasParameterWithNamespaceAndScope($defaultMatch, $groupMatch, $scopeMatch, $globalMatch, $expectedResult)
    {
        $paramName = 'foo.bar';
        $namespace = 'my.namespace';
        $scope = 'another_siteaccess';
        $groupName = 'my_group';
        $configResolver = $this->getResolver(
            'ibexa.site_access.config',
            ConfigResolver::UNDEFINED_STRATEGY_EXCEPTION,
            [
                $this->siteAccess->name => ['some_group'],
                $scope => [$groupName],
            ]
        );

        $this->containerMock->expects($this->atLeastOnce())
            ->method('hasParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ["$namespace.default.$paramName", $defaultMatch],
                        ["$namespace.$groupName.$paramName", $groupMatch],
                        ["$namespace.$scope.$paramName", $scopeMatch],
                        ["$namespace.global.$paramName", $globalMatch],
                    ]
                )
            );

        $this->assertSame($expectedResult, $configResolver->hasParameter($paramName, $namespace, $scope));
    }

    public function testGetSetDefaultScope()
    {
        $newDefaultScope = 'bar';
        $configResolver = $this->getResolver();
        $this->assertSame($this->siteAccess->name, $configResolver->getDefaultScope());
        $configResolver->setDefaultScope($newDefaultScope);
        $this->assertSame($newDefaultScope, $configResolver->getDefaultScope());
    }
}

class_alias(ConfigResolverTest::class, 'eZ\Bundle\EzPublishCoreBundle\Tests\ConfigResolverTest');
