<?php

use CCB\CCBProvider;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$app = AppFactory::create();
$app->addRoutingMiddleware();

$provider = new CCBProvider([
    'clientId' => $_ENV['CLIENT_ID'],
    'clientSecret' => $_ENV['CLIENT_SECRET'],
    'redirectUri' => 'http://localhost:3000/auth',
]);

$app->get('/', function (Request $request, Response $response) use ($provider) {
    $accessToken = $_SESSION['access_token'];
    if (!$accessToken) {
        $authUrl = $provider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $provider->getState();
        return $response->withHeader('Location', $authUrl)->withStatus(302);
    } else {
        if (time() >= $_SESSION['token_expiration']) {
            $refreshToken = $_SESSION['refresh_token'];
            $newAccessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $refreshToken,
            ]);
            $_SESSION['access_token'] = $newAccessToken->getToken();
            $_SESSION['refresh_token'] = $newAccessToken->getRefreshToken();
            $_SESSION['token_expiration'] = time() + $newAccessToken->getExpires();
        }

        $individuals = $provider->get('/individuals', $accessToken);

        $body = $response->getBody();
        $body->write(json_encode($individuals));

        return $response->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/auth', function (Request $request, Response $response) use ($provider) {
    $code = $request->getQueryParams()['code'];
    $state = $request->getQueryParams()['state'];

    if (empty($state) || (isset($_SESSION['oauth2state']) && $state !== $_SESSION['oauth2state'])) {
        error_log('Invalid state');

        return $response->withStatus(400);
    }

    error_log("Redirect returned with code: {$code}");
    error_log('Attempting to get access token');

    $accessToken = $provider->getAccessToken(
        'authorization_code',
        [
            'code' => $code,
        ]
    );

    if (!$accessToken->hasExpired()) {
        $_SESSION['access_token'] = $accessToken->getToken();
        $_SESSION['refresh_token'] = $accessToken->getRefreshToken();
        $_SESSION['token_expiration'] = time() + $accessToken->getExpires();
        error_log(print_r($accessToken->getValues(), 1));
    }

    error_log('Token creation successful! Redirecting to index...');

    return $response
        ->withHeader('Location', '/')
        ->withStatus(302);
});

session_start();

// Run app
$app->run();

session_write_close();
