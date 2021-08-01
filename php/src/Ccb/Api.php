<?php declare(strict_types=1);

namespace Ccb;

use GuzzleHttp\Client as GuzzleClient;

class Api
{
	const BASE_URI = 'https://api.ccbchurch.com';

	private OAuth2 $oAuth2;
	private CredentialStorage $persistence;

	public function __construct(OAuth2 $oAuth2, CredentialStorage $persistence)
	{
		$this->oAuth2 = $oAuth2;
		$this->persistence = $persistence;
	}

	public function getIndividuals(): array
	{
		return $this->get('individuals');
	}

	public function get(string $uri): array
	{
		$response = $this->createGuzzleClient()->get($uri);

		$contents = $response->getBody()->getContents();

		return json_decode($contents, $associative = true);
	}

	private function createGuzzleClient(): GuzzleClient
	{
		$bearerToken = $this->getBearerTokenRefreshIfNecessary();

		return new GuzzleClient([
			'base_uri' => self::BASE_URI,
			'headers' => [
				'Authorization' => "Bearer $bearerToken",
				'Accept' => 'application/vnd.ccbchurch.v2+json',
			],
		]);
	}

	private function getBearerTokenRefreshIfNecessary(): string
	{
		if ($this->persistence->hasCredentials(PERSISTENCE_ID)) {
			$credentials = $this->persistence->getCredentials(PERSISTENCE_ID);
			if ($credentials->isExpired()) {
				$credentials = $this->oAuth2->createRefreshToken($credentials->getRefreshToken());
				$this->persistence->setCredentials(PERSISTENCE_ID, $credentials);
			}

			$tokenType = $credentials->getTokenType();
			if ($tokenType === 'bearer') {
				return $credentials->getAccessToken();
			}

			// TODO: cover with test
			throw new OAuth2Exception("Unsupported token type '$tokenType'");
		}

		throw new OAuth2UnauthorizedException();
	}
}
