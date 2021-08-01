<?php declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (php_sapi_name() !== 'cli-server') {
	$message = <<<MESSAGE
	This example is designed to be run using PHP\'s built-in web server.
	Please run `make start` and open http://localhost:8888 in your browser.
	MESSAGE;

	trigger_error($message, E_USER_ERROR);
}

const PERSISTENCE_ID = 'default';

$configuration = getConfiguration();
$oAuth2 = new \Ccb\OAuth2($configuration);
$persistence = \Ccb\SimpleCredentialStorage::newInstance();
$client = new \Ccb\Api($oAuth2, $persistence);
$businessLogic = function () use ($client) {
	header('Content-Type: text/plain');
	var_export($client->getIndividuals());
};

try {
	$businessLogic();
} catch (\Ccb\OAuth2UnauthorizedException $e) {
	if (!isset($_GET['code'])) {
		$authorizationUrl = $oAuth2->createAuthorizationUrl();
		redirectTo($authorizationUrl);
	} else {
		$credentials = $oAuth2->createAccessToken($_GET['code']);
		$persistence->setCredentials(PERSISTENCE_ID, $credentials);

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
