<?php declare(strict_types=1);

namespace Ccb;

/**
 * This interface is designed in such a way that multiple
 */
interface CredentialStorage
{
	public function hasCredentials(string $id): bool;

	public function getCredentials(string $id): ?OAuth2Credentials;

	public function setCredentials(string $id, OAuth2Credentials $credentials): void;

	public function deleteCredentials(string $id): void;
}
