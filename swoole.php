<?php
declare(strict_types=1);

use noirapi\Config;
use noirapi\lib\Route;
use Swoole\Http\Server;

if(!extension_loaded('swoole')) {
    throw new RuntimeException('Swoole extension is mandatory');
}

include(__DIR__ . '/include.php');

$listen_ip = Config::get('swoole.listen_ip') ?? '127.0.0.1';
$listen_port = Config::get('swoole.listen_port') ?? 9400;

$server = new Swoole\HTTP\Server($listen_ip, $listen_port);
$static_files = Config::get('swoole.static_files');
$server->set([
    'worker_num'        => Config::get('swoole.workers') ?? 1,
    'task_worker_num'   => Config::get('swoole.task_workers') ?? 1,
]);

if(!empty($static_files)) {
    $server->set([
        'document_root'         => ROOT . '/htdocs',
        'enable_static_handler' => true,
    ]);
}

$server->on('start', function (Swoole\Http\Server $server) use($listen_ip, $listen_port) {
    /** @noinspection HttpUrlsUsage */
    echo "Swoole http server is started at http://$listen_ip:$listen_port\n";
});

$server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use($server) {

    $route = new Route();

    $request->server['headers'] = $request->header;
    $route->fromSwoole($request->server, $request->get ?? [], $request->post ?? [], $request->files ?? [], $request->cookie ?? []);
    $route->setSwoole($server);
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

$server->on('Task', static function(Server $server, $task_id, $reactorId, $data) {

    if(isset($data[ 'class' ], $data[ 'params' ])) {
        echo "Begin task: \t" . $task_id . "\t" . $data['class'] . "\n";
        $class = new $data['class'];
        $res = $class($data['params']);
        $server->finish($res);
        echo "End task: \t" . $task_id . "\t" . $data['class'] . "\n";
    } else {
        echo 'No class found in task' . PHP_EOL;
    }

});

$server->start();
