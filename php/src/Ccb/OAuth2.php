<?php declare(strict_types=1);

namespace Ccb;

use GuzzleHttp\Client as GuzzleClient;

class OAuth2
{
	const TOKEN_SERVER_URL = 'https://api.ccbchurch.com/oauth/token';
	const AUTHORIZATION_SERVER_URL = 'https://oauth.ccbchurch.com/oauth/authorize';

	private string $clientId;
	private string $clientSecret;
	private string $subdomain;

	public function __construct(array $configuration)
	{
		// TODO: validate configuration
		[
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'subdomain' => $this->subdomain,
		] = $configuration;
	}

	public function createAuthorizationUrl(string $redirectUri): string
	{
		return self::AUTHORIZATION_SERVER_URL . '?' . http_build_query([
				'client_id' => $this->clientId,
				'redirect_uri' => $redirectUri,
				'response_type' => 'code',
				'subdomain' => $this->subdomain,
			]);
	}

	public function createAccessToken(
		string $authorizationCode,
		string $redirectUri
	): array {
		return $this->postJson(
			self::TOKEN_SERVER_URL,
			[
				'grant_type' => 'authorization_code',
				'code' => $authorizationCode,
				'subdomain' => $this->subdomain,
				'redirect_uri' => $redirectUri,
			]
		);
	}

	public function createRefreshToken(string $refreshToken): array
	{
		return $this->postJson(
			self::TOKEN_SERVER_URL,
			[
				'grant_type' => 'refresh_token',
				'refresh_token' => $refreshToken,
			]
		);
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

}
