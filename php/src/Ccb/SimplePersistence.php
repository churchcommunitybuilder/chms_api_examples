<?php

namespace Ccb;

class SimplePersistence implements Persistence
{
	public static function newInstance(): self
	{
		$home = getenv('HOME');
		$file = "$home/.ccb.credentials";

		return new self($file);
	}

	private string $file;

	private function __construct(string $file)
	{
		$this->file = $file;
	}

	public function hasCredentials(string $id): bool
	{
		return file_exists($this->file);
	}

	public function getCredentials(string $id): ?OAuth2Credentials
	{
		$contents = file_get_contents($this->file);

		return unserialize($contents);
	}

	public function setCredentials(string $id, OAuth2Credentials $credentials): void
	{
		$contents = serialize($credentials);

		file_put_contents($this->file, $contents);
	}

	public function deleteCredentials(string $id): void
	{
		unset($this->file);
	}
}
