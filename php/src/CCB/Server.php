<?php declare(strict_types=1);

namespace CCB;

// TODO: cover with unit tests
class Server
{
	private array $server;

	private function __construct(array $server)
	{
		$this->server = $server;
	}

	public static function getInstance(): self
	{
		static $singleton = null;
		if ($singleton === null) {
			$singleton = new self($_SERVER);
		}

		return $singleton;
	}

	public function getUrlToSelf(): string
	{
		$protocol = $this->isSecure() ? 'https' : 'http';

		return sprintf('%s://%s%s', $protocol, $this->server['HTTP_HOST'], $this->server['PHP_SELF']);
	}

	public function isSecure(): bool
	{
		$https = $this->server['HTTPS'] ?? 'off';
		return $https !== 'off' || $this->server['SERVER_PORT'] == 443;
	}

}
