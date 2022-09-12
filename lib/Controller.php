<?php
/** @noinspection PhpMissingFieldTypeInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use noirapi\Config;
use noirapi\Exceptions\UnableToForwardException;
use noirapi\helpers\Message;

class Controller {

    /** @var Request $request */
    public Request $request;
    /** @var array $server */
    public array $server;
    public $model;
    /** @var Response $response */
    public Response $response;
    /** @var View $view */
    public View $view;
    /** @var Server|null $swoole */
    public ?Server $swoole;

    /**
     * Controller constructor.
     * @param Request $request
     * @param array $server
     * @param Server|null $swoole
     */
    public function __construct(Request $request, array $server) {

        $this->request = $request;
        $this->server = $server;

        $db = Config::get('db');

        if($db && empty($this->model)) {
            $model = 'app\\models\\' . self::getClassName(get_class($this));
            if(class_exists($model)) {
                $this->model = new $model();
            } else {
                $this->model = new Model();
            }
        }

        $this->response = new Response();

        new TracyExtras();

    }

    /**
     * @param string $class
     * @return string
     */
    public static function getClassName(string $class):string {
        $path = explode('\\', $class);
        return array_pop($path);
    }

    /**
     * @param string $location
     * @param int $status
     * @return Response
     * @throws UnableToForwardException
     * @noinspection PhpUnused
     */
    public function forward(string $location, int $status = 302): Response {

        if($status !== 302 && $status !== 301) {
            throw new UnableToForwardException('Unable to forward with status code: ' . $status);
        }

        return $this->response->withStatus($status)->withLocation($location);
    }

    /**
     * @return Response
     */
    public function ok(): Response {
        return $this->response->withStatus(200);
    }

    /**
     * @return Response
     * @noinspection PhpUnused
     */
    public function notFound(): Response {
        return $this->response->withStatus(404);
    }

    /**
     * @return Response
     * @noinspection PhpUnused
     */
    public function internalServerError(): Response {
        return $this->response->withStatus(500);
    }

    /**
     * @param string|Message $text
     * @param string|null $type
     * @return $this
     */
    public function message(string|Message $text, ?string $type = null): self {

        if (isset($_SESSION['message'])) {
            unset($_SESSION['message']);
        }

        if($text instanceof Message) {
            $_SESSION['message'] = $text;
        } else {
            $_SESSION['message'] = Message::new($text, $type ?? 'danger');
        }

        return $this;

    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function referer(): string {

        if(isset($this->server['HTTP_REFERER'])) {

            $url = str_replace('@', '', $this->server['HTTP_REFERER']);
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = parse_url(preg_replace('/\s+/', '', $url));

            if(!$url) {
                return '/';
            }

            if($url['scheme'] === 'http' || $url['scheme'] === 'https') {

                /** @noinspection BypassedUrlValidationInspection */
                if(filter_var($url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . ($url['query'] ?? ''), FILTER_VALIDATE_URL)) {

                    if(empty($url['query'])) {
                        return $url['scheme'] . '://' . $url['host'] . $url['path'];
                    }

                    return $url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . $url['query'];

                }

            }

            return '/';

        }

        return '/';

    }

}
