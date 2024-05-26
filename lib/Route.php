<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpMultipleClassDeclarationsInspection
 */
declare(strict_types = 1);

namespace noirapi\lib;

use FastRoute\Dispatcher;
use Nette\Neon\Exception;
use noirapi\Config;
use noirapi\Exceptions\FileNotFoundException;
use noirapi\Exceptions\InternalServerError;
use noirapi\Exceptions\LoginException;
use noirapi\Exceptions\MessageException;
use noirapi\Exceptions\NotFoundException;
use noirapi\Exceptions\RestException;
use noirapi\helpers\Utils;
use noirapi\Tracy\GenericPanel;
use Swoole\Http\Server;
use Tracy\Debugger;
use function call_user_func_array;
use function in_array;
use function strlen;

/** @psalm-suppress PropertyNotSetInConstructor */
class Route {

    private Request $request;
    private Response $response;
    private array $server;

    /**
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $cookies
     * @return self
     */
    public static function fromGlobals(array $server, array $get, array $post, array $files, array $cookies): self {
        $self = new self();

        $self->request = Request::fromGlobals($server, $get, $post, $files, $cookies);
        $self->server = $server;

        return $self;
    }

    /**
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $cookies
     * @return self
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function fromSwoole(array $server, array $get, array $post, array $files, array $cookies): self {
        $self = new self();
        $self->request = Request::fromSwoole($server, $get, $post, $files, $cookies);
        $self->server = Request::swooleUpperCase($server);

        return $self;
    }

    /**
     * @param Server $server
     * @return void
     * @noinspection PhpUndefinedClassInspection
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setSwoole(Server $server): void {
        $this->request->swoole = $server;
    }

    /**
     * @return Response
     * @throws Exception
     * @throws FileNotFoundException
     */
    public function serve(): Response {

        $dev = Config::get('dev') || (Config::get('dev_ips') && in_array($this->server[ 'REMOTE_ADDR' ], Config::get('dev_ips'), true));

        $this->response = new Response();

        $route = new \app\Route($dev);

        $pos = strpos($this->request->uri, '?');

        if($pos !== false) {
            $uri = substr($this->request->uri, 0, $pos);
        } else {
            $uri = $this->request->uri;
        }

        $uri = rawurldecode($uri);

        $languages = Config::get('languages') ?? [];

        // Check for language, if found strip it from the uri
        foreach ($languages as $code => $_) {
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
            $this->redirect('/' . (Config::get('default_language') ?? 'en') . $uri, 307);
            if($dev) {
                self::handleRouteUrlDebugBar($this->request,$this->response, $this->server);
            }
            return $this->response;
        }

        $this->request->url_no_lang = $uri;

        $this->request->route = $route->process($this->request->method, $uri);

        switch ($this->request->route[0]) {

            case Dispatcher::FOUND:

                $this->request->controller = Utils::getCLassName($this->request->route[1][0]);
                $this->request->function = $this->request->route[1][1];

                try{

                    call_user_func_array(
                        [
                            new $this->request->route[1][0]($this->request, $this->response, $this->server),
                            $this->request->route[1][1]
                        ],
                        $this->request->route[2]
                    );

                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (LoginException $exception) {

                    if($exception->getCode() === 403) {
                        $this->response->withStatus(403)
                            ->setContentType(Response::TYPE_JSON)
                            ->setBody(['forward' => $exception->getMessage()]);
                    } else {
                        $this->response->withStatus($exception->getCode())
                            ->withLocation($exception->getMessage());
                    }

                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (RestException $exception) {
                    $this->response->withStatus($exception->getCode())
                        ->setContentType(Response::TYPE_JSON)
                        ->setBody($exception->getMessage());
                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (MessageException $exception) {
                    $this->response->withStatus($exception->getCode())
                        ->setBody($exception->getMessage());
                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (InternalServerError $exception) {
                    $this->handleErrors(500, $exception->getMessage() ?? 'Internal server error');
                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (NotFoundException $exception) {
                    $this->handleErrors(404, $exception->getMessage() ?? '404 Not found');
                }

                break;

            case Dispatcher::NOT_FOUND:

                $this->handleErrors(404, '404 Not found');
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:

                $this->handleErrors(405, '405 Method not allowed');
                break;

            default:

                $this->handleErrors(500, 'Internal server error');

        }

        return $this->response;

    }

    /**
     * @param int $status_code
     * @param string $defaultText
     * @return void
     * @throws Exception
     * @throws FileNotFoundException
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private function handleErrors(int $status_code, string $defaultText): void {

        $function = 'e' . $status_code;

        /** @psalm-suppress UndefinedClass */
        if(class_exists(\app\controllers\errors::class) && method_exists(\app\controllers\errors::class, $function)) {
            $this->request->controller = 'errors';
            $this->request->function = $function;
            $this->response = (new \app\controllers\errors($this->request, $this->response, $this->server))->$function();
        } else {
            $this->response->setBody($defaultText)->withStatus($status_code);
        }

    }

    /**
     * @param string $location
     * @param int $status
     * @return void
     */
    private function redirect(string $location, int $status = 302): void {

        // Attach get to current future location
        if(!empty($this->request->get)) {
            $location .= '?' . http_build_query($this->request->get);
        }

        $this->response->withLocation($location)->withStatus($status);

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $server
     * @return void
     */
    public static function handleRouteUrlDebugBar(Request $request, Response $response, array $server): void {

        /** @noinspection HttpUrlsUsage */
        $host = ($request->https ? 'https://' : 'http://') . Config::$config;

        $urls = [];
        $urls['uri'] = $host . $request->uri;

        $ref = $server[ 'HTTP_REFERER' ] ?? '';
        if(!str_starts_with($ref, 'http')) {
            $urls['ref'] = $host . $ref;
        } elseif($ref !== '') {
            $urls['ref'] = $ref;
        }

        $location = $response->getLocation();
        if($location !== null) {

            if(str_starts_with($location, 'http')) {
                $urls['fwd'] = $location;
            } else {
                $urls['fwd'] = $host . $location;
            }
        }

        $panel = new GenericPanel('url', $urls);
        Debugger::getBar()->addPanel($panel);

    }

}
