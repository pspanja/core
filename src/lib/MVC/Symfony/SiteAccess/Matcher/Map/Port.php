<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;

class Port extends Map
{
    public function getName()
    {
        return 'port';
    }

    /**
     * Injects the request object to match against.
     *
     * @param \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest $request
     */
    public function setRequest(SimplifiedRequest $request)
    {
        if (!$this->key) {
            if (!empty($request->port)) {
                $key = $request->port;
            } else {
                switch ($request->scheme) {
                    case 'https':
                        $key = 443;
                        break;

                    case 'http':
                    default:
                        $key = 80;
                }
            }

            $this->setMapKey($key);
        }

        parent::setRequest($request);
    }

    public function reverseMatch($siteAccessName)
    {
        $matcher = parent::reverseMatch($siteAccessName);
        if ($matcher instanceof self) {
            $matcher->getRequest()->setPort($matcher->getMapKey());
        }

        return $matcher;
    }
}

class_alias(Port::class, 'eZ\Publish\Core\MVC\Symfony\SiteAccess\Matcher\Map\Port');
