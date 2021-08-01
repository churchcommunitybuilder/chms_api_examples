<?php declare(strict_types=1);

namespace Ccb;

use GuzzleHttp\Client as GuzzleClient;

class Api
{
	const BASE_URI = 'https://api.ccbchurch.com';

	private OAuth2 $oAuth2;
	private CredentialStorage $storage;

	public function __construct(OAuth2 $oAuth2, CredentialStorage $storage)
	{
		$this->oAuth2 = $oAuth2;
		$this->storage = $storage;
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

		return GuzzleFactory::createClient([
			'base_uri' => self::BASE_URI,
			'headers' => [
				'Authorization' => "Bearer $bearerToken",
			],
		]);
	}

	private function getBearerTokenRefreshIfNecessary(): string
	{
		if ($this->storage->hasCredentials(STORAGE_ID)) {
			$credentials = $this->storage->getCredentials(STORAGE_ID);
			if ($credentials->isExpired()) {
				$credentials = $this->oAuth2->createRefreshToken($credentials->getRefreshToken());
				$this->storage->setCredentials(STORAGE_ID, $credentials);
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
