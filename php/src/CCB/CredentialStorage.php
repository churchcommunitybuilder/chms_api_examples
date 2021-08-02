<?php declare(strict_types=1);

namespace CCB;

interface CredentialStorage
{
	public const DEFAULT_ID = 'default';

	public function hasCredentials(string $id): bool;

	public function getCredentials(string $id): ?OAuth2Credentials;

	public function setCredentials(string $id, OAuth2Credentials $credentials): void;

	public function deleteCredentials(string $id): void;
}
