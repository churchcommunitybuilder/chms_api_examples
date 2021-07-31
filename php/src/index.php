<?php declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (php_sapi_name() !== 'cli-server') {
	$message = <<<MESSAGE
    This example is designed to be run using PHP\'s built-in web server.
    Please run `make start` and open http://localhost:8888 in your browser.
    MESSAGE;

	trigger_error($message, E_USER_ERROR);
}

$configuration = getConfiguration();
$oAuth2 = new \Ccb\OAuth2($configuration);

$redirectUri = \Ccb\Server::getInstance()->getUrlToSelf();
if (isset($_GET['code'])) {
	[
		'access_token' => $accessToken,
		'token_type' => $tokenType,
		'expires_in' => $expiresInSeconds,
		'scope' => $scope,
		'refresh_token' => $refreshToken,
	] = $oAuth2->createAccessToken($_GET['code'], $redirectUri);

//	$individuals = get($accessToken, 'individuals');
//	var_dump($individuals);
//	exit;

	$refreshToken = $oAuth2->createRefreshToken($refreshToken);
	var_dump($refreshToken);
} else {
	$authorizationUrl = $oAuth2->createAuthorizationUrl($redirectUri);
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

function get(string $bearerToken, string $uri): array
{
	$client = new GuzzleHttp\Client([
		'base_uri' => 'https://api.ccbchurch.com',
	]);

	$response = $client->get(
		$uri,
		[
			'headers' => [
				'Authorization' => "Bearer $bearerToken",
				'Accept' => 'application/vnd.ccbchurch.v2+json',
			]
		],
	);

	$contents = $response->getBody()->getContents();

	return json_decode($contents, $associative = true);
}
