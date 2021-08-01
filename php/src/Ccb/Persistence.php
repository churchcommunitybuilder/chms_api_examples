<?php

namespace Ccb;

interface Persistence
{
	public function hasCredentials(string $id): bool;

	public function getCredentials(string $id): ?OAuth2Credentials;

	public function setCredentials(string $id, OAuth2Credentials $credentials): void;

	public function deleteCredentials(string $id): void;
}
