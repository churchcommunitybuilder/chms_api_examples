<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

if (php_sapi_name() !== 'cli-server') {
	$message = <<<MESSAGE
    This example is designed to be run using PHP\'s built-in web server.
    Please run `make start` and open http://localhost:8888 in your browser.
    MESSAGE;

	trigger_error($message, E_USER_ERROR);
}

const TOKEN_SERVER_URL = 'https://api.ccbchurch.com/oauth/token';
const AUTHORIZATION_SERVER_URL = 'https://oauth.ccbchurch.com/oauth/authorize';

[
	'client_id' => $clientId,
	'client_secret' => $clientSecret,
	'subdomain' => $subdomain,
	'port' => $port,
] = getConfiguration();

$redirectUri = createRedirectUri();
if (isset($_GET['code'])) {
	[
		'access_token' => $accessToken,
		'token_type' => $tokenType,
		'expires_in' => $expiresInSeconds,
		'scope' => $scope,
		'refresh_token' => $refreshToken,
	] = createAccessToken(
		$clientId,
		$clientSecret,
		$_GET['code'],
		$subdomain,
		$redirectUri
	);

//	$individuals = get($accessToken, 'individuals');
//	var_dump($individuals);
//	exit;

	$refreshToken = createRefreshToken($clientId, $clientSecret, $refreshToken);
	var_dump($refreshToken);
} else {
	$authorizationUrl = createAuthorizationUrl($clientId, $redirectUri, $subdomain);
	redirectTo($authorizationUrl);
}

function getConfiguration(): array
{
	$path = __DIR__ . '/config.properties';
	if (!is_readable($path)) {
		trigger_error("$path could not be read!", E_USER_ERROR);
	}

	return parse_ini_file($path);
}

function createRedirectUri(): string
{
	$protocol = isSecure() ? 'https' : 'http';

	return sprintf('%s://%s%s', $protocol, $_SERVER['HTTP_HOST'], $_SERVER['PHP_SELF']);
}

function isSecure(): bool
{
	$https = $_SERVER['HTTPS'] ?? 'off';
	return $https !== 'off' || $_SERVER['SERVER_PORT'] == 443;
}

function createAuthorizationUrl(string $clientId, string $redirectUri, string $subdomain): string
{
	return AUTHORIZATION_SERVER_URL . '?' . http_build_query([
			'client_id' => $clientId,
			'redirect_uri' => $redirectUri,
			'response_type' => 'code',
			'subdomain' => $subdomain,
		]);
}

function redirectTo(string $authorizationUrl): void
{
	header("Location: $authorizationUrl");

	print <<<HTML
    <html>
    <head>
    <meta http-equiv="refresh" content="0; URL=$authorizationUrl" />
    </head>
    <body>
    Your browser should redirect you momentarily. If that doesn't happen please consider upgrading to a browser that works...
    or <a href="$authorizationUrl">CLICK HERE NOW!</a>
    </body>
    </html>
    HTML;
}

function createAccessToken(
	string $clientId,
	string $clientSecret,
	string $authorizationCode,
	string $subdomain,
	string $redirectUri
): array {
	$client = new GuzzleHttp\Client();

	$response = $client->post(
		TOKEN_SERVER_URL,
		[
			'auth' => [$clientId, $clientSecret],
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/vnd.ccbchurch.v2+json',
			],
			'json' => [
				'grant_type' => 'authorization_code',
				'code' => $authorizationCode,
				'subdomain' => $subdomain,
				'redirect_uri' => $redirectUri,
			],
		],
	);

	$contents = $response->getBody()->getContents();

	return json_decode($contents, $associative = true);
}

function createRefreshToken(string $clientId, string $clientSecret, string $refreshToken): array {
	$client = new GuzzleHttp\Client();

	$response = $client->post(
		TOKEN_SERVER_URL,
		[
			'auth' => [$clientId, $clientSecret],
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/vnd.ccbchurch.v2+json',
			],
			'json' => [
				'grant_type' => 'refresh_token',
				'refresh_token' => $refreshToken,
			],
		],
	);

	$contents = $response->getBody()->getContents();

	return json_decode($contents, $associative = true);
}

function get(string $accessToken, string $uri): array
{
	$client = new GuzzleHttp\Client([
		'base_uri' => 'https://api.ccbchurch.com',
	]);

	$response = $client->get(
		$uri,
		[
			'headers' => [
				'Authorization' => "Bearer $accessToken",
				'Accept' => 'application/vnd.ccbchurch.v2+json',
			]
		],
	);

	$contents = $response->getBody()->getContents();

	return json_decode($contents, $associative = true);
}
