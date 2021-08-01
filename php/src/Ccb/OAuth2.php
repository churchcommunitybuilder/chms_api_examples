<?php declare(strict_types=1);

namespace Ccb;

use GuzzleHttp\Client as GuzzleClient;
use Throwable;

class OAuth2
{
	const TOKEN_SERVER_URL = 'https://api.ccbchurch.com/oauth/token';
	const AUTHORIZATION_SERVER_URL = 'https://oauth.ccbchurch.com/oauth/authorize';

	private string $clientId;
	private string $clientSecret;
	private string $subdomain;
	private string $redirectUri;

	public function __construct(array $configuration)
	{
		// TODO: validate configuration
		[
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'subdomain' => $this->subdomain,
			'redirect_uri' => $this->redirectUri,
		] = $configuration;
	}

	public function createAuthorizationUrl(): string
	{
		return self::AUTHORIZATION_SERVER_URL . '?' . http_build_query([
				'client_id' => $this->clientId,
				'redirect_uri' => $this->getRedirectUri(),
				'response_type' => 'code',
				'subdomain' => $this->subdomain,
			]);
	}

	public function createAccessToken(string $authorizationCode): OAuth2Credentials
	{
		try {
			$credentials = $this->postJson(
				self::TOKEN_SERVER_URL,
				[
					'grant_type' => 'authorization_code',
					'code' => $authorizationCode,
					'subdomain' => $this->subdomain,
					'redirect_uri' => $this->getRedirectUri(),
				]
			);

			return OAuth2Credentials::createFromArray($credentials);
		} catch (Throwable $t) {
			throw new OAuth2Exception($t->getMessage(), 0, $t);
		}
	}

	private function getRedirectUri(): string
	{
		return $this->redirectUri ?: Server::getInstance()->getUrlToSelf();
	}

	private function postJson(string $uri, array $json): array
	{
		$response = $this
			->createGuzzleClient()
			->post($uri, ['json' => $json]);

		$contents = $response->getBody()->getContents();

		return json_decode($contents, $associative = true);
	}

	private function createGuzzleClient(): GuzzleClient
	{
		return new GuzzleClient([
			'auth' => [$this->clientId, $this->clientSecret],
			'headers' => [
				'Accept' => 'application/vnd.ccbchurch.v2+json',
			],
		]);
	}

	public function createRefreshToken(string $refreshToken): OAuth2Credentials
	{
		try {
			$credentials = $this->postJson(
				self::TOKEN_SERVER_URL,
				[
					'grant_type' => 'refresh_token',
					'refresh_token' => $refreshToken,
				]
			);

			return OAuth2Credentials::createFromArray($credentials);
		} catch (Throwable $t) {
			throw new OAuth2Exception($t->getMessage(), 0, $t);
		}
	}

}
