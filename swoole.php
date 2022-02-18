<?php
declare(strict_types=1);

use noirapi\Config;
use noirapi\lib\Route;

if(!extension_loaded('swoole')) {
    throw new RuntimeException('Swoole extension is mandatory');
}

include(__DIR__ . '/include.php');

$server = new Swoole\HTTP\Server('127.0.0.1', 9501);

$server->on('start', function (Swoole\Http\Server $server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

$server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) {

    $request->server['headers'] = $request->header;
    $route = new Route($request->server, $request->get ?? [], $request->post ?? [], $request->files ?? [], $request->cookie ?? [], Route::type_swoole);
    $res = $route->serve();

    $response->setStatusCode($res['status']);

    foreach ($res['headers'] as $key => $value) {
        $response->header($key, $value);
    }

    foreach ($res['cookies'] as $cookie) {

        $response->setCookie(
            name: $cookie['key'],
            value: $cookie['value'],
            expires: $cookie['expire'],
            path: '/',
            domain: Config::$config,
            secure: $cookie['secure'],
            httponly: $cookie['httponly'],
            samesite: $cookie['samesite']
        );

    }

    $response->end($res['body']);

});

$server->start();
