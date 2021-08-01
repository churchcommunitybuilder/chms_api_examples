<?php

namespace Ccb;

/**
 * An immutable representation of OAuth2 credentials.
 */
class OAuth2Credentials
{
	private string $accessToken;
	private string $tokenType;
	private int $expiresAt;
	private string $scope;
	private string $refreshToken;

	/**
	 * Credentials constructor.
	 * @param string $accessToken
	 * @param string $tokenType
	 * @param int $expiresIn
	 * @param string $scope
	 * @param string $refreshToken
	 */
	public function __construct(string $accessToken, string $tokenType, int $expiresIn, string $scope, string $refreshToken)
	{
		$this->accessToken = $accessToken;
		$this->tokenType = $tokenType;
		$this->expiresAt = time() + $expiresIn;
		$this->scope = $scope;
		$this->refreshToken = $refreshToken;
	}

	public static function createFromArray(array $credentials): self
	{
		[
			'access_token' => $accessToken,
			'token_type' => $tokenType,
			'expires_in' => $expiresIn,
			'scope' => $scope,
			'refresh_token' => $refreshToken,
		] = $credentials;

		return new self($accessToken, $tokenType, $expiresIn, $scope, $refreshToken);
	}

	/**
	 * @return string
	 */
	public function getAccessToken(): string
	{
		return $this->accessToken;
	}

	/**
	 * @return string
	 */
	public function getTokenType(): string
	{
		return $this->tokenType;
	}

	/**
	 * @return int
	 */
	public function getExpiresAt(): int
	{
		return $this->expiresAt;
	}

	public function isExpired(int $time = null): bool
	{
		$time ??= time();

		return ($this->expiresAt - $time) <= 0;
	}

	/**
	 * @return string
	 */
	public function getScope(): string
	{
		return $this->scope;
	}

	/**
	 * @return string
	 */
	public function getRefreshToken(): string
	{
		return $this->refreshToken;
	}

	public function __serialize(): array
	{
		return [
			'access_token' => $this->accessToken,
			'token_type' => $this->tokenType,
			'expires_at' => $this->expiresAt,
			'scope' => $this->scope,
			'refresh_token' => $this->refreshToken,
		];
	}

	public function __unserialize(array $data): void
	{
		[
			'access_token' => $this->accessToken,
			'token_type' => $this->tokenType,
			'expires_at' => $this->expiresAt,
			'scope' => $this->scope,
			'refresh_token' => $this->refreshToken,
		] = $data;
	}
}
