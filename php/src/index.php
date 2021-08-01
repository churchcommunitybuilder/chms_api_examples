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
$storage = \Ccb\SimpleCredentialStorage::newInstance();
$client = new \Ccb\Api($oAuth2, $storage);
$businessLogic = function () use ($client) {
	header('Content-Type: text/plain');
	var_export($client->getIndividuals());
};

try {
	$businessLogic();
} catch (\Ccb\OAuth2UnauthorizedException $e) {
	// this is likely caused by a lack of stored credentials
	if (!isset($_GET['code'])) {
		// in this contrived example let's redirect to the auth URL and then loop back
		$authorizationUrl = $oAuth2->createAuthorizationUrl();
		redirectTo($authorizationUrl);
	} else {
		// on the last leg of this auth detour we should receive a code that we can use to retrieve the credentials
		$credentials = $oAuth2->createAccessToken($_GET['code']);
		// which we then store for later use
		$storage->setCredentials(\Ccb\CredentialStorage::DEFAULT_ID, $credentials);

		// finally we retry our business logic
		$businessLogic();
	}
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

	exit;
}
