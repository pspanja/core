<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Bundle\Core\Routing;

use Ibexa\Bundle\Core\Routing\DefaultRouter;
use Ibexa\Bundle\Core\SiteAccess\Matcher;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class DefaultRouterTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\DependencyInjection\ContainerInterface */
    protected $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    protected $configResolver;

    /** @var \Symfony\Component\Routing\RequestContext */
    protected $requestContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->requestContext = new RequestContext();
    }

    /**
     * @return class-string<\Ibexa\Bundle\Core\Routing\DefaultRouter>
     */
    protected function getRouterClass()
    {
        return DefaultRouter::class;
    }

    /**
     * @param array<string> $mockedMethods
     *
     * @return \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Bundle\Core\Routing\DefaultRouter
     */
    protected function generateRouter(array $mockedMethods = [])
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Bundle\Core\Routing\DefaultRouter $router */
        $router = $this
            ->getMockBuilder($this->getRouterClass())
            ->setConstructorArgs([$this->container, 'foo', [], $this->requestContext])
            ->setMethods(array_merge($mockedMethods))
            ->getMock();
        $router->setConfigResolver($this->configResolver);

        return $router;
    }

    public function testMatchRequestWithSemanticPathinfo()
    {
        $pathinfo = '/siteaccess/foo/bar';
        $semanticPathinfo = '/foo/bar';
        $request = Request::create($pathinfo);
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);

        /** @var \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Bundle\Core\Routing\DefaultRouter $router */
        $router = $this->generateRouter(['getMatcher']);
        $matchedParameters = ['_controller' => 'AcmeBundle:myAction'];

        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->expects(self::once())
            ->method('match')
            ->with($semanticPathinfo)
            ->willReturn($matchedParameters);

        $router
            ->expects(self::once())
            ->method('getMatcher')
            ->willReturn($matcher);

        $this->assertSame($matchedParameters, $router->matchRequest($request));
    }

    public function testMatchRequestRegularPathinfo()
    {
        $matchedParameters = ['_controller' => 'AcmeBundle:myAction'];
        $pathinfo = '/siteaccess/foo/bar';

        $request = Request::create($pathinfo);

        $this->configResolver->expects($this->never())->method('getParameter');

        /** @var \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Bundle\Core\Routing\DefaultRouter $router */
        $router = $this->generateRouter(['getMatcher']);

        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->expects(self::once())
            ->method('match')
            ->with($pathinfo)
            ->willReturn($matchedParameters);

        $router
            ->expects(self::once())
            ->method('getMatcher')
            ->willReturn($matcher);

        $this->assertSame($matchedParameters, $router->matchRequest($request));
    }

    /**
     * @dataProvider providerGenerateNoSiteAccess
     */
    public function testGenerateNoSiteAccess($url)
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator
            ->expects(self::once())
            ->method('generate')
            ->with(__METHOD__)
            ->willReturn($url);

        /** @var \Ibexa\Bundle\Core\Routing\DefaultRouter&\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->generateRouter(['getGenerator']);
        $router
            ->expects(self::any())
            ->method('getGenerator')
            ->willReturn($generator);

        $this->assertSame($url, $router->generate(__METHOD__));
    }

    public function providerGenerateNoSiteAccess()
    {
        return [
            ['/foo/bar'],
            ['/foo/bar/baz?truc=muche&tata=toto'],
            ['http://ibexa.co/Products/Ibexa-CMS'],
            ['http://www.metalfrance.net/decouvertes/edge-caress-inverse-ep'],
        ];
    }

    /**
     * @dataProvider providerGenerateWithSiteAccess
     *
     * @param string $urlGenerated The URL generated by the standard UrLGenerator
     * @param string $relevantUri The relevant URI part of the generated URL (without host and basepath)
     * @param string $expectedUrl The URL we're expecting to be finally generated, with siteaccess
     * @param string $saName The SiteAccess name
     * @param bool $isMatcherLexer True if the siteaccess matcher is URILexer
     * @param int $referenceType The type of reference to be generated (one of the constants)
     * @param string $routeName
     */
    public function testGenerateWithSiteAccess($urlGenerated, $relevantUri, $expectedUrl, $saName, $isMatcherLexer, $referenceType, $routeName)
    {
        $routeName = $routeName ?: __METHOD__;
        $nonSiteAccessAwareRoutes = ['_dontwantsiteaccess'];
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator
            ->expects(self::once())
            ->method('generate')
            ->with($routeName)
            ->willReturn($urlGenerated);

        /** @var \Ibexa\Bundle\Core\Routing\DefaultRouter&\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->generateRouter(['getGenerator']);
        $router
            ->expects(self::any())
            ->method('getGenerator')
            ->willReturn($generator);

        // If matcher is URILexer, we make it act as it's supposed to, prepending the siteaccess.
        if ($isMatcherLexer) {
            $matcher = $this->createMock(SiteAccess\URILexer::class);
            // Route is siteaccess aware, we're expecting analyseLink() to be called
            if (!in_array($routeName, $nonSiteAccessAwareRoutes)) {
                $matcher
                    ->expects(self::once())
                    ->method('analyseLink')
                    ->with($relevantUri)
                    ->willReturn("/$saName$relevantUri");
            } else {
                // Non-siteaccess aware route, it's not supposed to be analysed
                $matcher
                    ->expects($this->never())
                    ->method('analyseLink');
            }
        } else {
            $matcher = $this->createMock(Matcher::class);
        }

        $sa = new SiteAccess($saName, 'test', $matcher);
        $router->setSiteAccess($sa);

        $requestContext = new RequestContext();
        $urlComponents = parse_url($urlGenerated);
        if (isset($urlComponents['host'])) {
            $requestContext->setHost($urlComponents['host']);
            $requestContext->setScheme($urlComponents['scheme']);
            if (isset($urlComponents['port']) && $urlComponents['scheme'] === 'http') {
                $requestContext->setHttpPort($urlComponents['port']);
            } elseif (isset($urlComponents['port']) && $urlComponents['scheme'] === 'https') {
                $requestContext->setHttpsPort($urlComponents['port']);
            }
        }
        $requestContext->setBaseUrl(
            substr($urlComponents['path'], 0, strpos($urlComponents['path'], $relevantUri))
        );
        $router->setContext($requestContext);
        $router->setNonSiteAccessAwareRoutes($nonSiteAccessAwareRoutes);

        $this->assertSame($expectedUrl, $router->generate($routeName, [], $referenceType));
    }

    public function providerGenerateWithSiteAccess()
    {
        return [
            ['/foo/bar', '/foo/bar', '/foo/bar', 'test_siteaccess', false, UrlGeneratorInterface::ABSOLUTE_PATH, null],
            ['http://ezpublish.dev/foo/bar', '/foo/bar', 'http://ezpublish.dev/foo/bar', 'test_siteaccess', false, UrlGeneratorInterface::ABSOLUTE_URL, null],
            ['http://ezpublish.dev/foo/bar', '/foo/bar', 'http://ezpublish.dev/test_siteaccess/foo/bar', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_URL, null],
            ['http://ezpublish.dev/foo/bar', '/foo/bar', 'http://ezpublish.dev/foo/bar', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_URL, '_dontwantsiteaccess'],
            ['http://ezpublish.dev:8080/foo/bar', '/foo/bar', 'http://ezpublish.dev:8080/test_siteaccess/foo/bar', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_URL, null],
            ['http://ezpublish.dev:8080/foo/bar', '/foo/bar', 'http://ezpublish.dev:8080/foo/bar', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_URL, '_dontwantsiteaccess'],
            ['https://ezpublish.dev/secured', '/secured', 'https://ezpublish.dev/test_siteaccess/secured', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_URL, null],
            ['https://ezpublish.dev:445/secured', '/secured', 'https://ezpublish.dev:445/test_siteaccess/secured', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_URL, null],
            ['http://ezpublish.dev:8080/foo/root_folder/bar/baz', '/bar/baz', 'http://ezpublish.dev:8080/foo/root_folder/test_siteaccess/bar/baz', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_URL, null],
            ['/foo/bar/baz', '/foo/bar/baz', '/test_siteaccess/foo/bar/baz', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_PATH, null],
            ['/foo/root_folder/bar/baz', '/bar/baz', '/foo/root_folder/test_siteaccess/bar/baz', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_PATH, null],
            ['/foo/bar/baz', '/foo/bar/baz', '/foo/bar/baz', 'test_siteaccess', true, UrlGeneratorInterface::ABSOLUTE_PATH, '_dontwantsiteaccess'],
        ];
    }

    public function testGenerateReverseSiteAccessMatch()
    {
        $routeName = 'some_route_name';
        $urlGenerated = 'http://phoenix-rises.fm/foo/bar';

        $siteAccessName = 'foo_test';
        $siteAccessRouter = $this->createMock(SiteAccess\SiteAccessRouterInterface::class);
        $versatileMatcher = $this->createMock(SiteAccess\VersatileMatcher::class);
        $simplifiedRequest = new SimplifiedRequest(
            [
                'host' => 'phoenix-rises.fm',
                'scheme' => 'http',
            ]
        );
        $versatileMatcher
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($simplifiedRequest);
        $siteAccessRouter
            ->expects(self::once())
            ->method('matchByName')
            ->with($siteAccessName)
            ->willReturn(new SiteAccess($siteAccessName, 'foo', $versatileMatcher));

        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator
            ->expects(self::at(0))
            ->method('setContext')
            ->with(self::isInstanceOf(RequestContext::class));
        $generator
            ->expects(self::at(1))
            ->method('generate')
            ->with($routeName)
            ->willReturn($urlGenerated);
        $generator
            ->expects(self::at(2))
            ->method('setContext')
            ->with($this->requestContext);

        $router = new DefaultRouter($this->container, 'foo', [], $this->requestContext);
        $router->setConfigResolver($this->configResolver);
        $router->setSiteAccess(new SiteAccess('test', 'test', $this->createMock(Matcher::class)));
        $router->setSiteAccessRouter($siteAccessRouter);
        $refRouter = new ReflectionObject($router);
        $refGenerator = $refRouter->getProperty('generator');
        $refGenerator->setAccessible(true);
        $refGenerator->setValue($router, $generator);

        $this->assertSame(
            $urlGenerated,
            $router->generate($routeName, ['siteaccess' => $siteAccessName], DefaultRouter::ABSOLUTE_PATH)
        );
    }

    /**
     * @dataProvider providerGetContextBySimplifiedRequest
     *
     * @param string $uri
     */
    public function testGetContextBySimplifiedRequest($uri)
    {
        $this->getExpectedRequestContext($uri);

        $router = new DefaultRouter($this->container, 'foo', [], $this->requestContext);

        self::assertEquals(
            $this->getExpectedRequestContext($uri),
            $router->getContextBySimplifiedRequest(SimplifiedRequest::fromUrl($uri))
        );
    }

    /**
     * Data provider for testGetContextBySimplifiedRequest.
     *
     * @see testGetContextBySimplifiedRequest
     *
     * @phpstan-return array<array{string}>
     */
    public function providerGetContextBySimplifiedRequest()
    {
        return [
            ['/foo/bar'],
            ['http://ezpublish.dev/foo/bar'],
            ['http://ezpublish.dev:8080/foo/bar'],
            ['https://ezpublish.dev/secured'],
            ['https://ezpublish.dev:445/secured'],
            ['http://ezpublish.dev:8080/foo/root_folder/bar/baz'],
        ];
    }

    private function getExpectedRequestContext($uri)
    {
        $requestContext = new RequestContext();
        $uriComponents = parse_url($uri);
        if (isset($uriComponents['host'])) {
            $requestContext->setHost($uriComponents['host']);
            $requestContext->setScheme($uriComponents['scheme']);
            if (isset($uriComponents['port']) && $uriComponents['scheme'] === 'http') {
                $requestContext->setHttpPort($uriComponents['port']);
            } elseif (isset($uriComponents['port']) && $uriComponents['scheme'] === 'https') {
                $requestContext->setHttpsPort($uriComponents['port']);
            }
        }
        if (isset($uriComponents['path'])) {
            $requestContext->setPathInfo($uriComponents['path']);
        }

        return $requestContext;
    }
}

class_alias(DefaultRouterTest::class, 'eZ\Bundle\EzPublishCoreBundle\Tests\Routing\DefaultRouterTest');
