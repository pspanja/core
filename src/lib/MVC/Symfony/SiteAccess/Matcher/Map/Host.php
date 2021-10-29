<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;

class Host extends Map
{
    public function getName()
    {
        return 'host:map';
    }

    /**
     * Injects the request object to match against.
     *
     * @param \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest $request
     */
    public function setRequest(SimplifiedRequest $request)
    {
        if (!$this->key) {
            $this->setMapKey($request->host);
        }

        parent::setRequest($request);
    }

    public function reverseMatch($siteAccessName)
    {
        $matcher = parent::reverseMatch($siteAccessName);
        if ($matcher instanceof self) {
            $matcher->getRequest()->setHost($matcher->getMapKey());
        }

        return $matcher;
    }
}

class_alias(Host::class, 'eZ\Publish\Core\MVC\Symfony\SiteAccess\Matcher\Map\Host');
