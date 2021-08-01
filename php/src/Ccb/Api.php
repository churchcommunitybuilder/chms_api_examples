<?php declare(strict_types=1);

namespace Ccb;

use GuzzleHttp\Client as GuzzleClient;

class Api
{
	const BASE_URI = 'https://api.ccbchurch.com';

	private GuzzleClient $client;

	public function __construct(string $bearerToken)
	{
		$this->client = new GuzzleClient([
			'base_uri' => self::BASE_URI,
			'headers' => [
				'Authorization' => "Bearer $bearerToken",
				'Accept' => 'application/vnd.ccbchurch.v2+json',
			],
		]);
	}

	function get(string $uri): array
	{
		$response = $this->client->get($uri);

		$contents = $response->getBody()->getContents();

		return json_decode($contents, $associative = true);
	}
}
