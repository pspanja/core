<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\MVC\Symfony\SiteAccess;

use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\MVC\Symfony\Routing\SimplifiedRequest;
use eZ\Publish\Core\MVC\Exception\InvalidSiteAccessException;
use Psr\Log\LoggerInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess\Matcher\CompoundInterface;
use InvalidArgumentException;

class Router implements SiteAccessRouterInterface, SiteAccessAware
{
    public const HEADER_SA_MATCHING_TYPE = 'header';
    public const ENV_SA_MATCHING_TYPE = 'env';

    /**
     * Name of the default siteaccess.
     *
     * @var string
     */
    protected $defaultSiteAccess;

    /**
     * The configuration for siteaccess matching.
     * Consists in an hash indexed by matcher type class.
     * Value is a hash where index is what to match against and value is the corresponding siteaccess name.
     *
     * Example:
     * <code>
     * array(
     *     // Using built-in URI matcher. Key is the prefix that matches the siteaccess, in the value
     *     "Map\\URI" => array(
     *         "ezdemo_site" => "ezdemo_site",
     *         "ezdemo_site_admin" => "ezdemo_site_admin",
     *     ),
     *     // Using built-in HOST matcher. Key is the hostname, value is the siteaccess name
     *     "Map\\Host" => array(
     *         "ezpublish.dev" => "ezdemo_site",
     *         "ezpublish.admin.dev" => "ezdemo_site_admin",
     *     ),
     *     // Using a custom matcher (class must begin with a '\', as a full qualified class name).
     *     // The custom matcher must implement eZ\Publish\Core\MVC\Symfony\SiteAccess\Matcher interface.
     *     "\\My\\Custom\\Matcher" => array(
     *         "something_to_match_against" => "siteaccess_name"
     *     )
     * )
     * </code>
     *
     * @var array
     */
    protected $siteAccessesConfiguration;

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface */
    protected $siteAccessProvider;

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess */
    protected $siteAccess;

    /** @var string */
    protected $siteAccessClass;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess\MatcherBuilderInterface */
    protected $matcherBuilder;

    /** @var \eZ\Publish\Core\MVC\Symfony\Routing\SimplifiedRequest */
    protected $request;

    /** @var bool */
    protected $debug;

    /**
     * @param \eZ\Publish\Core\MVC\Symfony\SiteAccess\MatcherBuilderInterface $matcherBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $defaultSiteAccess
     * @param array $siteAccessesConfiguration
     * @param \eZ\Publish\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface $siteAccessProvider
     * @param string|null $siteAccessClass
     * @param bool $debug
     */
    public function __construct(
        MatcherBuilderInterface $matcherBuilder,
        LoggerInterface $logger,
        $defaultSiteAccess,
        array $siteAccessesConfiguration,
        SiteAccessProviderInterface $siteAccessProvider,
        $siteAccessClass = null,
        $debug = false
    ) {
        $this->matcherBuilder = $matcherBuilder;
        $this->logger = $logger;
        $this->defaultSiteAccess = $defaultSiteAccess;
        $this->siteAccessesConfiguration = $siteAccessesConfiguration;
        $this->siteAccessProvider = $siteAccessProvider;
        $this->siteAccessClass = $siteAccessClass ?: 'eZ\\Publish\\Core\\MVC\\Symfony\\SiteAccess';
        $this->request = new SimplifiedRequest();
        $this->debug = $debug;
    }

    /**
     * @return \eZ\Publish\Core\MVC\Symfony\Routing\SimplifiedRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Performs SiteAccess matching given the $request.
     *
     * @param \eZ\Publish\Core\MVC\Symfony\Routing\SimplifiedRequest $request
     *
     * @throws \eZ\Publish\Core\MVC\Exception\InvalidSiteAccessException
     *
     * @return \eZ\Publish\Core\MVC\Symfony\SiteAccess
     */
    public function match(SimplifiedRequest $request)
    {
        $this->request = $request;

        if (isset($this->siteAccess)) {
            return $this->siteAccess;
        }

        // Request header always have precedence
        // Note: request headers are always in lower cased.
        if (!empty($request->headers['x-siteaccess'])) {
            $siteaccessName = $request->headers['x-siteaccess'][0];
            if (!$this->siteAccessProvider->isDefined($siteaccessName)) {
                throw new InvalidSiteAccessException(
                    $siteaccessName,
                    $this->siteAccessProvider,
                    'X-Siteaccess request header',
                    $this->debug
                );
            }

            $this->siteAccess = $this->siteAccessProvider->getSiteAccess($siteaccessName);
            $this->siteAccess->matchingType = self::HEADER_SA_MATCHING_TYPE;

            return $this->siteAccess;
        }

        // Then check environment variable
        $siteaccessEnvName = getenv('EZPUBLISH_SITEACCESS');
        if ($siteaccessEnvName !== false) {
            if (!$this->siteAccessProvider->isDefined($siteaccessEnvName)) {
                throw new InvalidSiteAccessException(
                    $siteaccessEnvName,
                    $this->siteAccessProvider,
                    'EZPUBLISH_SITEACCESS environment variable',
                    $this->debug
                );
            }

            $this->siteAccess = $this->siteAccessProvider->getSiteAccess($siteaccessEnvName);
            $this->siteAccess->matchingType = self::ENV_SA_MATCHING_TYPE;

            return $this->siteAccess;
        }

        return $this->doMatch($request);
    }

    /**
     * Returns the SiteAccess object matched against $request and the siteaccess configuration.
     * If nothing could be matched, the default siteaccess is returned, with "default" as matching type.
     *
     * @param \eZ\Publish\Core\MVC\Symfony\Routing\SimplifiedRequest $request
     *
     * @return \eZ\Publish\Core\MVC\Symfony\SiteAccess
     */
    private function doMatch(SimplifiedRequest $request)
    {
        foreach ($this->siteAccessesConfiguration as $matchingClass => $matchingConfiguration) {
            $matcher = $this->matcherBuilder->buildMatcher($matchingClass, $matchingConfiguration, $request);
            if ($matcher instanceof CompoundInterface) {
                $matcher->setMatcherBuilder($this->matcherBuilder);
            }

            $siteAccessName = $matcher->match();
            if ($siteAccessName !== false && $this->siteAccessProvider->isDefined($siteAccessName)) {
                $this->siteAccess = $this->siteAccessProvider->getSiteAccess($siteAccessName);
                $this->siteAccess->matchingType = $matcher->getName();
                $this->siteAccess->matcher = $matcher;

                return $this->siteAccess;
            }
        }

        $this->logger->notice('Siteaccess not matched against configuration, returning default siteaccess.');
        $this->siteAccess = new $this->siteAccessClass($this->defaultSiteAccess);
        $this->siteAccess->matchingType = SiteAccess::DEFAULT_MATCHING_TYPE;

        return $this->siteAccess;
    }

    /**
     * Matches a SiteAccess by name.
     * Returns corresponding SiteAccess object, according to configuration, with corresponding matcher.
     * Returns null if no matcher can be found (e.g. non versatile).
     *
     * @param string $siteAccessName
     *
     * @throws \InvalidArgumentException If $siteAccessName is invalid (i.e. not present in configured list).
     *
     * @return \eZ\Publish\Core\MVC\Symfony\SiteAccess|null
     */
    public function matchByName($siteAccessName)
    {
        if (!$this->siteAccessProvider->isDefined($siteAccessName)) {
            throw new InvalidArgumentException("Invalid SiteAccess name provided for reverse matching: $siteAccessName");
        }

        $request = clone $this->request;
        // Be sure to have a clean pathinfo, without SiteAccess part in it.
        if ($this->siteAccess && $this->siteAccess->matcher instanceof URILexer) {
            $request->setPathinfo($this->siteAccess->matcher->analyseURI($request->pathinfo));
        }

        $siteAccessClass = $this->siteAccessClass;
        foreach ($this->siteAccessesConfiguration as $matchingClass => $matchingConfiguration) {
            $matcher = $this->matcherBuilder->buildMatcher($matchingClass, $matchingConfiguration, $request);
            if (!$matcher instanceof VersatileMatcher) {
                continue;
            }

            if ($matcher instanceof CompoundInterface) {
                $matcher->setMatcherBuilder($this->matcherBuilder);
            }

            $reverseMatcher = $matcher->reverseMatch($siteAccessName);
            if (!$reverseMatcher instanceof Matcher) {
                continue;
            }

            /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess $siteAccess */
            $siteAccess = new $siteAccessClass($siteAccessName);
            $siteAccess->matcher = $reverseMatcher;
            $siteAccess->matchingType = $reverseMatcher->getName();

            return $siteAccess;
        }

        // No VersatileMatcher configured for $siteAccessName.
        $this->logger->notice("Siteaccess '$siteAccessName' could not be reverse-matched against configuration. No VersatileMatcher found. Returning default SiteAccess.");

        return new $siteAccessClass($this->defaultSiteAccess, 'default');
    }

    /**
     * @return \eZ\Publish\Core\MVC\Symfony\SiteAccess|null
     */
    public function getSiteAccess()
    {
        return $this->siteAccess;
    }

    /**
     * @param \eZ\Publish\Core\MVC\Symfony\SiteAccess $siteAccess
     */
    public function setSiteAccess(SiteAccess $siteAccess = null)
    {
        $this->siteAccess = $siteAccess;
    }
}
