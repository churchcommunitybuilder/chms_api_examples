<?php declare(strict_types=1);

namespace Ccb;

use GuzzleHttp\Client as GuzzleClient;

class Api
{
	const BASE_URI = 'https://api.ccbchurch.com';

	private OAuth2 $oAuth2;
	private CredentialStorage $storage;
	private string $storageId;

	public function __construct(OAuth2 $oAuth2, CredentialStorage $storage, string $storageId = CredentialStorage::DEFAULT_ID)
	{
		$this->oAuth2 = $oAuth2;
		$this->storage = $storage;
		$this->storageId = $storageId;
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
		if ($this->storage->hasCredentials($this->storageId)) {
			$credentials = $this->storage->getCredentials($this->storageId);
			if ($credentials->isExpired()) {
				$credentials = $this->oAuth2->createRefreshToken($credentials->getRefreshToken());
				$this->storage->setCredentials($this->storageId, $credentials);
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
