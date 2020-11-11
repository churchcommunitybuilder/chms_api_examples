<?php

namespace CCB;

use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class CCBProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    private $client;

    public function __construct(array $options = [], array $collaborators = [])
    {
        $requestFactory = new CCBRequestFactory();

        parent::__construct(
            $options,
            array_merge(
                $collaborators,
                ['requestFactory' => $requestFactory]
            )
        );
    }

    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = new Client([
                'base_uri' => 'https://api.ccbchurch.com',
                'headers' => ['Content-Type' => 'application/json'],
            ]);
        }

        return $this->client;
    }

    public function get(string $uri, string $accessToken)
    {
        $request = $this->getAuthenticatedRequest(
            'GET',
            ltrim($uri, '/'),
            $accessToken
        );
        $response = $this->getClient()->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function post(string $uri, array $data, string $accessToken)
    {
        $request = $this->getAuthenticatedRequest(
            'POST',
            ltrim($uri, '/'),
            $accessToken,
            ['json' => $data]
        );
        $response = $this->getClient()->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function put(string $uri, array $data, string $accessToken)
    {
        $request = $this->getAuthenticatedRequest(
            'PUT',
            ltrim($uri, '/'),
            $accessToken,
            ['json' => $data]
        );
        $response = $this->getClient()->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function delete(string $uri, string $accessToken)
    {
        $request = $this->getAuthenticatedRequest(
            'DELETE',
            ltrim($uri, '/'),
            $accessToken
        );
        $response = $this->getClient()->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getBaseAuthorizationUrl()
    {
        return 'https://oauth.ccbchurch.com/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://api.ccbchurch.com/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://api.ccbchurch.com/me';
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $error = $data['error'];
            if (!is_string($error)) {
                $error = var_export($error, true);
            }
            $code  = 0; // $this->responseCode && !empty($data[$this->responseCode])? $data[$this->responseCode] : 0;
            // if (!is_int($code)) {
            //     $code = intval($code);
            // }
            throw new IdentityProviderException($error, $code, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return null;
    }
}
