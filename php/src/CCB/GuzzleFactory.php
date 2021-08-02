<?php declare(strict_types=1);

namespace CCB;

use GuzzleHttp\Client as GuzzleClient;

class GuzzleFactory
{
	private const ACCEPT_HEADER = 'application/vnd.ccbchurch.v2+json';

	private function __construct()
	{
	}

	public static function createClient(array $configuration = []): GuzzleClient
	{
		$configuration = array_merge_recursive(
			[
				'headers' => [
					'Accept' => self::ACCEPT_HEADER,
				],
			],
			$configuration
		);

		return new GuzzleClient($configuration);
	}
}
