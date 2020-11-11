<?php

namespace CCB;

use League\OAuth2\Client\Tool\RequestFactory;

class CCBRequestFactory extends RequestFactory
{
    public function getRequest($method, $uri, array $headers = [], $body = null, $version = '1.1')
    {
        return parent::getRequest(
            $method,
            $uri,
            array_merge(
                $headers,
                ['Accept' => 'application/vnd.ccbchurch.v2+json']
            ),
            $body,
            $version
        );
    }
}
