<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\View;

use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

abstract class BaseView implements View
{
    /**
     * @phpstan-var string|(\Closure(array<string, mixed>):string)
     *
     * @var string|\Closure
     */
    protected $templateIdentifier;

    /** @var array */
    protected $parameters = [];

    /** @var array */
    protected $configHash = [];

    /** @var string */
    private $viewType = 'full';

    /** @var \Symfony\Component\HttpKernel\Controller\ControllerReference */
    private $controllerReference;

    /** @var \Symfony\Component\HttpFoundation\Response */
    private $response;

    /** @var bool */
    private $isCacheEnabled = true;

    /**
     * @phpstan-param string|(\Closure(array<string, mixed>):string) $templateIdentifier
     *
     * @param string|\Closure $templateIdentifier Valid path to the template. Can also be a closure.
     * @param string $viewType
     * @param array $parameters Hash of parameters to pass to the template/closure.
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
     */
    public function __construct($templateIdentifier = null, array $parameters = [], $viewType = 'full')
    {
        if (isset($templateIdentifier)) {
            $this->setTemplateIdentifier($templateIdentifier);
        }

        $this->viewType = $viewType;
        $this->parameters = $parameters;
    }

    /**
     * @param array $parameters Hash of parameters to pass to the template/closure
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Adds a hash of parameters to the existing parameters.
     *
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->getInternalParameters() + $this->parameters;
    }

    /**
     * Checks if $parameterName exists.
     *
     * @param string $parameterName
     *
     * @return bool
     */
    public function hasParameter($parameterName)
    {
        return isset($this->parameters[$parameterName]);
    }

    /**
     * Returns parameter value by $parameterName.
     * Throws an \InvalidArgumentException if $parameterName is not set.
     *
     * @param string $parameterName
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function getParameter($parameterName)
    {
        if ($this->hasParameter($parameterName)) {
            return $this->parameters[$parameterName];
        }

        throw new \InvalidArgumentException("Parameter '$parameterName' is not set.");
    }

    /**
     * @phpstan-param string|(\Closure(array<string, mixed>):string) $templateIdentifier
     *
     * @param string|\Closure $templateIdentifier
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
     */
    public function setTemplateIdentifier($templateIdentifier)
    {
        if (!is_string($templateIdentifier) && !$templateIdentifier instanceof \Closure) {
            throw new InvalidArgumentType('templateIdentifier', 'string or \Closure', $templateIdentifier);
        }

        $this->templateIdentifier = $templateIdentifier;
    }

    /**
     * @return string|\Closure
     *
     * @phpstan-return string|(\Closure(array<string, mixed>):string)
     */
    public function getTemplateIdentifier()
    {
        return $this->templateIdentifier;
    }

    /**
     * Injects the config hash that was used to match and generate the current view.
     * Typically, the hash would have as keys:
     *  - template : The template that has been matched
     *  - match : The matching configuration, including the matcher "identifier" and what has been passed to it.
     *  - matcher : The matcher object.
     *
     * @param array $config
     */
    public function setConfigHash(array $config)
    {
        $this->configHash = $config;
    }

    /**
     * Returns the config hash.
     *
     * @return array|null
     */
    public function getConfigHash()
    {
        return $this->configHash;
    }

    public function setViewType($viewType)
    {
        $this->viewType = $viewType;
    }

    public function getViewType()
    {
        return $this->viewType;
    }

    public function setControllerReference(ControllerReference $controllerReference)
    {
        $this->controllerReference = $controllerReference;
    }

    /**
     * @return \Symfony\Component\HttpKernel\Controller\ControllerReference
     */
    public function getControllerReference()
    {
        return $this->controllerReference;
    }

    /**
     * Override to return internal parameters that will be added to the ones returned by getParameter().
     *
     * @return array
     */
    protected function getInternalParameters()
    {
        return [];
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setCacheEnabled($cacheEnabled)
    {
        $this->isCacheEnabled = (bool)$cacheEnabled;
    }

    public function isCacheEnabled()
    {
        return $this->isCacheEnabled;
    }
}

class_alias(BaseView::class, 'eZ\Publish\Core\MVC\Symfony\View\BaseView');
