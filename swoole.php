<?php
declare(strict_types=1);

use noirapi\Config;
use noirapi\lib\Route;

if(!extension_loaded('swoole')) {
    throw new RuntimeException('Swoole extension is mandatory');
}

include(__DIR__ . '/include.php');

$listen_ip = Config::get('listen_ip') ?? '127.0.0.1';
$listen_port = Config::get('listen_port') ?? 9400;

$server = new Swoole\HTTP\Server($listen_ip, $listen_port);

$server->on('start', function (Swoole\Http\Server $server) use($listen_ip, $listen_port) {
    /** @noinspection HttpUrlsUsage */
    echo "Swoole http server is started at http://$listen_ip:$listen_port\n";
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
