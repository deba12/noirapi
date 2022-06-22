<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use FastRoute\Dispatcher;
use JsonException;
use noirapi\Config;
use noirapi\Exceptions\LoginException;
use noirapi\Exceptions\MessageException;
use noirapi\Exceptions\RestException;
use RuntimeException;
use function call_user_func_array;
use function http_response_code;

class Route {

    public const type_swoole = 'swoole';
    public const type_globals = 'globals';

    private string $type;
    private Request $request;
    private array $server;

    /**
     * route constructor.
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $cookies
     * @param string $type
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function __construct(array $server, array $get, array $post, array $files, array $cookies, string $type) {

        if($type === self::type_globals) {
            $this->request = Request::fromGlobals($server, $get, $post, $files, $cookies);
            $this->server = $server;
        } elseif($type === self::type_swoole) {
            $this->request = Request::fromSwoole($server, $get, $post, $files, $cookies);
            $this->server = Request::swooleUpperCase($server);
        } else {
            throw new RuntimeException('Unable to use request type: ' . $type);
        }

        $this->type = $type;

    }

    /**
     * @throws JsonException
     */
    public function serve(): array|string {

        $route = new \app\Route(Config::get('dev') ?? true);

        $pos = strpos($this->request->uri, '?');

        if($pos !== false) {
            $uri = substr($this->request->uri, 0, $pos);
        } else {
            $uri = $this->request->uri;
        }

        $uri = rawurldecode($uri);

        $this->request->route = $route->process($this->request->method, $uri);

        switch ($this->request->route[0]) {

            case Dispatcher::NOT_FOUND:

                $response = self::handleErrors(404, '404 Not found');
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:

                $response = self::handleErrors(405, '405 Method not allowed');
                break;

            case Dispatcher::FOUND:

                $this->request->controller = $this->findController($this->request->route[1][0]);
                $this->request->function = $this->request->route[1][1];

                /** @var $response response */
                try{

                    $response = call_user_func_array(
                        [
                            new $this->request->route[1][0]($this->request, $this->server),
                            $this->request->route[1][1]
                        ],
                        $this->request->route[2]
                    );

                    if($response === null) {
                        $response = new Response();
                        $response->withStatus(500)
                            ->setBody('Internal server error');
                    }

                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (LoginException $exception) {

                    $response = new Response();

                    if($exception->getCode() === 403) {
                        $response->withStatus(403)
                            ->setContentType(Response::TYPE_JSON)
                            ->setBody(['forward' => $exception->getMessage()]);
                    } else {
                        $response->withStatus($exception->getCode())
                            ->withLocation($exception->getMessage());
                    }

                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (RestException $exception) {
                    $response = new Response();
                    $response->withStatus($exception->getCode())
                        ->setContentType(Response::TYPE_JSON)
                        ->setBody($exception->getMessage());
                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (MessageException $exception) {
                    $response = new Response();
                    $response->withStatus($exception->getCode())
                        ->setBody($exception->getMessage());
                }

                break;

            default:

                $response = self::handleErrors(500, 'Internal server error');

        }

        if($this->type === self::type_globals) {

            http_response_code($response->getStatus());
            foreach($response->getHeaders() as $key => $value) {
                header(ucfirst($key) . ': ' . $value);
            }

            foreach($response->getCookies() as $cookie) {
                $domain = Config::get('domain');
                setcookie(
                    $cookie['key'],
                    $cookie['value'],
                    [
                        'expires'   => $cookie['expire'],
                        'path'      => '/',
                        'domain'    => $domain,
                        'secure'    => $cookie['secure'],
                        'httponly'  => $cookie['httponly'],
                        'samesite'  => $cookie['samesite'],
                    ]);

            }

            $res = $response->getBody();

        } elseif($this->type === self::type_swoole) {

            $res = [
                'status'    => $response->getStatus(),
                'body'      => $response->getBody(),
                'cookies'   => $response->getCookies(),
                'headers'   => $response->getHeaders(),
            ];

        }

        //force calling destructor
        $response = null;

        /** @noinspection PhpUndefinedVariableInspection */
        return $res;

    }

    public static function handleErrors(int $error, string $defaultText): Response {

        $function = 'e' . $error;

        if(class_exists('app\\controllers\\error')) {
            /** @noinspection PhpUndefinedNamespaceInspection */
            /** @noinspection PhpUndefinedClassInspection */
            $response = (new app\controllers\error())->$function();
        } else {
            $response = new Response();
            $response->setBody($defaultText);
        }

        return $response->withStatus($error);

    }

    /**
     * @param string $class
     * @return string
     */
    private function findController(string $class): string {
        $ex = explode('\\', $class);
        return end($ex);
    }

}
