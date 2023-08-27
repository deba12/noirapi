<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpMultipleClassDeclarationsInspection
 */
declare(strict_types = 1);

namespace noirapi\lib;

use FastRoute\Dispatcher;
use JsonException;
use noirapi\Config;
use noirapi\Exceptions\FileNotFoundException;
use noirapi\Exceptions\InternalServerError;
use noirapi\Exceptions\LoginException;
use noirapi\Exceptions\MessageException;
use noirapi\Exceptions\NotFoundException;
use noirapi\Exceptions\RestException;
use noirapi\helpers\Utils;
use Swoole\Http\Server;
use function call_user_func_array;
use function http_response_code;

class Route {

    public const type_swoole = 'swoole';
    public const type_globals = 'globals';

    private string $type;
    private Request $request;
    private array $server;

    /**
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $cookies
     * @return void
     */
    public function fromGlobals(array $server, array $get, array $post, array $files, array $cookies): void {
        $this->request = Request::fromGlobals($server, $get, $post, $files, $cookies);
        $this->server = $server;

        $this->type = self::type_globals;
    }

    /**
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $cookies
     * @return void
     */
    public function fromSwoole(array $server, array $get, array $post, array $files, array $cookies): void {
        $this->request = Request::fromSwoole($server, $get, $post, $files, $cookies);
        $this->server = Request::swooleUpperCase($server);
        $this->request->swoole = $this->swoole_server ?? null;

        $this->type = self::type_swoole;
    }

    /**
     * @param Server $server
     * @return void
     * @noinspection PhpUndefinedClassInspection
     */
    public function setSwoole(Server $server): void {
        $this->request->swoole = $server;
    }

    /**
     * @return array|string
     * @throws FileNotFoundException
     * @throws JsonException
     */
    public function serve(): array|string {

        $route = new \app\Route(Config::get('dev') ?? false);

        $pos = strpos($this->request->uri, '?');

        if($pos !== false) {
            $uri = substr($this->request->uri, 0, $pos);
        } else {
            $uri = $this->request->uri;
        }

        $uri = rawurldecode($uri);

        $languages = Config::get('languages') ?? [];

        // Check for language, if found strip it from the uri
        foreach ($languages as $code => $lang) {
            // Condition like /en,
            if($uri === '/' . $code) {
                $this->request->language = $code;
                $uri = '/';
                break;
            }

            // Condition like /en/
            if (str_starts_with($uri, '/' . $code . '/')) {
                $this->request->language = $code;
                $uri = substr($uri, strlen($code) + 1);
                break;
            }

        }

        if(empty($this->request->language) && !empty($languages)) {

            $response = $this->redirect('/' . (Config::get('default_language') ?? 'en') . $uri, 307);

            if($this->type === self::type_globals) {
                http_response_code($response->getStatus());

                foreach($response->getHeaders() as $key => $value) {
                    header(ucfirst($key) . ': ' . $value);
                }

                return $response->getBody();

            }

            if($this->type === self::type_swoole) {

                $res = [
                    'status'    => $response->getStatus(),
                    'body'      => $response->getBody(),
                    'cookies'   => $response->getCookies(),
                    'headers'   => $response->getHeaders(),
                ];

            }

        }

        $this->request->route = $route->process($this->request->method, $uri);

        switch ($this->request->route[0]) {

            case Dispatcher::NOT_FOUND:

                $response = $this->handleErrors(404, '404 Not found');
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:

                $response = $this->handleErrors(405, '405 Method not allowed');
                break;

            case Dispatcher::FOUND:

                $this->request->controller = Utils::getCLassName($this->request->route[1][0]);
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
                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (InternalServerError $exception) {
                    $response = $this->handleErrors(500, $exception->getMessage() ?? 'Internal server error');
                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (NotFoundException $exception) {
                    $response = $this->handleErrors(404, $exception->getMessage() ?? '404 Not found');
                }

                break;

            default:

                $response = $this->handleErrors(500, 'Internal server error');

        }

        if($this->type === self::type_globals) {

            http_response_code($response->getStatus());
            foreach($response->getHeaders() as $key => $value) {
                header(ucfirst($key) . ': ' . $value);
            }

            $domain = Config::get('cookie_domain');

            foreach($response->getCookies() as $cookie) {

                setcookie(
                    $cookie['key'],
                    $cookie['value'],
                    [
                        'expires'   => $cookie['expire'],
                        'path'      => '/',
                        'domain'    => $domain ?? $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'],
                        'secure'    => !$this->request->https ? false : $cookie['secure'],
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

    /**
     * @param int $error
     * @param string $defaultText
     * @return Response
     * @noinspection PhpFullyQualifiedNameUsageInspection
     * @throws FileNotFoundException
     */
    private function handleErrors(int $error, string $defaultText): Response {

        $function = 'e' . $error;

        if(class_exists(\app\controllers\errors::class) && method_exists(\app\controllers\errors::class, $function)) {
            $this->request->controller = 'errors';
            $this->request->function = $function;
            $response = (new \app\controllers\errors($this->request, $this->server))->$function();
        } else {
            $response = new Response();
            $response->setBody($defaultText);
        }

        return $response->withStatus($error);

    }

    /**
     * @param string $location
     * @param int $status
     * @return Response
     */
    private function redirect(string $location, int $status = 302): Response {

        // Attach get to current future location
        if(!empty($this->request->get)) {
            $location .= '?' . http_build_query($this->request->get);
        }

        return (new Response())->withLocation($location)->withStatus($status);

    }

}
