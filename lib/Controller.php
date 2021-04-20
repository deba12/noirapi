<?php declare(strict_types = 1);

namespace noirapi\lib;
use core\Exceptions\UnableToForwardException;
use stdClass;

class Controller {

    /** @var stdClass $request */
    public $request;
    /** @var array $server */
    public $server;
    /** @var Model $model */
    public $model;
    /** @var Response $response */
    public $response;
    /** @var View $view */
    public $view;

    /**
     * Controller constructor.
     * @param stdClass $request
     * @param array $server
     */
    public function __construct(stdClass $request, array $server) {

        $this->request = $request;
        $this->server = $server;

        if(defined('DB')) {
            $model = 'app\\models\\' . self::getClassName(get_class($this));
            if(class_exists($model)) {
                $this->model = new $model($this->request);
            } else {
                $this->model = new Model();
            }
        }


        $this->response = new Response();

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
     * @noinspection UnknownInspectionInspection
     */
    public function forward(string $location, int $status = 302): Response {

        if($status !== 302 && $status !== 301) {
            throw new UnableToForwardException('Unable to forward with status code: ' . $status);
        }

        return $this->response->withStatus($status)->withLocation($location);
    }

    public function message(string $text, string $type, $post = array()): self {

        if (isset($_SESSION['message'])) {
            unset($_SESSION['message']);
        }

        if (!empty($text) && !empty($type)) {
            $_SESSION['message'] = array('text' => $text, 'type' => $type);
        }

        if (isset($post) && is_array($post)) {
            $_SESSION['message']['post'] = $post;
        }

        return $this;

    }

    /**
     * @return string
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
                if(filter_var($url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . $url['query'], FILTER_VALIDATE_URL)) {

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
