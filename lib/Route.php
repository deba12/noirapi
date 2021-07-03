<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use FastRoute\Dispatcher;
use JsonException;
use noirapi\Exceptions\LoginException;
use noirapi\Exceptions\RestException;
use stdClass;
use function http_response_code;

class Route {

    /**
     * route constructor.
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $cookies
     * @throws JsonException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function __construct(array $server, array $get, array $post, array $files, array $cookies) {

        $request = new stdClass();
        $request->headers   = $this->requestHeaders($server);
        $request->method    = $server['REQUEST_METHOD'];
        $request->uri       = $server['REQUEST_URI'];
        $request->get       = $get;
        $request->post      = $post;
        $request->files     = $files;
        $request->cookies   = $cookies;

        $route = new \app\Route();

        $pos = strpos($request->uri, '?');

        if($pos !== false) {
            $uri = substr($request->uri, 0, $pos);
        } else {
            $uri = $request->uri;
        }

        $uri = rawurldecode($uri);

        $request->route = $route->process($request->method, $uri);

        switch ($request->route[0]) {

            case Dispatcher::NOT_FOUND:

                $response = self::handleErrors(404, '404 Not found');
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:

                $response = self::handleErrors(405, '405 Method not allowed');
                break;

            case Dispatcher::FOUND:

                $request->controller = $this->findController($request->route[1][0]);
                $request->function = $request->route[1][1];

                /** @var $response response */
                try{

                    $response = call_user_func_array([new $request->route[1][0]($request, $server), $request->route[1][1]], $request->route[2]);

                    if($response === null) {
                        $response = new Response();
                        $response->withStatus(500)
                            ->setBody('Internal server error');
                    }

                } catch (LoginException $exception) {
                    $response = new Response();

                    if($exception->getCode() === 403) {
                        $response->withStatus(403)
                            ->setContentType(response::TYPE_JSON)
                            ->setBody(['forward' => $exception->getMessage()]);
                    } else {
                        $response->withStatus($exception->getCode())
                            ->withLocation($exception->getMessage());
                    }

                } catch (RestException $exception) {
                    $response = new Response();
                    $response->withStatus($exception->getCode())
                        ->setContentType(Response::TYPE_JSON)
                        ->setBody($exception->getMessage());
                }

                break;

            default:

                $response = self::handleErrors(500, 'Internal server error');

        }

        http_response_code($response->getStatus());
        foreach($response->getHeaders() as $key => $value) {
            header(ucfirst($key) . ': ' . $value);
        }

        foreach($response->getCookies() as $cookie) {
            setcookie(
                $cookie['key'],
                $cookie['value'],
                [
                    'expires'   => $cookie['expire'],
                    'path'      => '/',
                    'domain'    => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : BASE_DOMAIN,
                    'secure'    => $cookie['secure'],
                    'httponly'  => $cookie['httponly'],
                    'samesite'  => $cookie['samesite'],
                ]);

        }

        $body = $response->getBody();
        if(is_callable($body)) {
            $body();
        } else {
            echo $body;
        }

        //force calling destructor
        $response = null;

    }

    /**
     * @param array $server
     * @return stdClass
     */
    private function requestHeaders(array $server): stdClass {

        $headers = new stdClass();

        foreach ($server as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                //get Header key w/o HTTP_
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers->{$key} = $value;
            }
        }

        return $headers;

    }

    public static function handleErrors(int $error, string $defaultText): response {

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
