<?php /** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpParamsInspection */
/** @noinspection PhpMissingFieldTypeInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use JanDrabek\Tracy\GitVersionPanel;
use noirapi\Config;
use noirapi\Exceptions\UnableToForwardException;
use noirapi\helpers\Message;
use noirapi\helpers\RestMessage;
use noirapi\helpers\Session;
use noirapi\helpers\Utils;
use noirapi\Tracy\PDOBarPanel;
use Tracy\Debugger;
use function get_class;
use function in_array;

class Controller {

    public Request $request;
    public array $server;
    public $model;
    public Response $response;
    public View $view;
    public bool $dev;
    public static $panels;

    /**
     * Controller constructor.
     * @param Request $request
     * @param array $server
     */
    public function __construct(Request $request, array $server) {

        $this->request = $request;
        $this->server = $server;

        $db = Config::get('db');
        $this->dev = Config::get('dev') || (Config::get('dev_ips') && in_array($this->server[ 'REMOTE_ADDR' ], Config::get('dev_ips'), true));

        /**
         * @noinspection PhpUndefinedClassInspection
         * @noinspection RedundantSuppression
         *
         * Tracy debug bar
         */
        if($this->dev && class_exists(GitVersionPanel::class) && !isset(self::$panels[ 'git' ])) {
            self::$panels['git'] = true;
            Debugger::getBar()->addPanel(new GitVersionPanel());
        }

        if($db && empty($this->model)) {
            $model = 'app\\models\\' . Utils::getClassName(get_class($this));
            if(class_exists($model)) {
                $this->model = new $model();
            } else {
                $this->model = new Model();
            }
        }

        /**
         * Tracy debug bar
         */
        if($this->dev && !empty($db)) {

            foreach(Model::tracyGetPdo() as $driver => $pdo) {

                if(!isset(self::$panels[$driver])) {
                    self::$panels[$driver] = true;

                    $panel = new PDOBarPanel($pdo);
                    $panel->title = $driver;
                    Debugger::getBar()->addPanel($panel);
                }

            }

        }

        $this->response = new Response();

        // We need this when we are moving across domains
        if(isset($this->request->get['message'], $this->request->get[ 'type' ])) {
            $this->message($this->request->get['message'], $this->request->get['type']);
        }

    }

    /**
     * @param string|null $location
     * @param int $status
     * @param bool $skip_lang
     * @return Response
     * @throws UnableToForwardException
     */
    public function forward(?string $location = null, int $status = 302, bool $skip_lang = false): Response {

        if($status !== 302 && $status !== 301) {
            throw new UnableToForwardException('Unable to forward with status code: ' . $status);
        }

        if(empty($location)) {
            $location = $this->referer();
        }

        if(!$skip_lang && !empty($this->request->language) && str_starts_with($location, '/')) {
            return $this->response->withStatus($status)->withLocation('/' . $this->request->language . $location);
        }

        return $this->response->withStatus($status)->withLocation($location);
    }

    /**
     * @return Response
     * @noinspection PhpUnused
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

        Session::remove('message');

        if($text instanceof Message) {
            Session::set('message', null, $text);
        } else {
            Session::set('message', null, Message::new($text, $type ?? 'danger'));
        }

        return $this;

    }

    /**
     * @param bool $same_domain
     * @return string
     * @noinspection PhpUnused
     */
    public function referer(bool $same_domain = true): string {

        if(isset($this->server['HTTP_REFERER'])) {

            $url = str_replace('@', '', $this->server['HTTP_REFERER']);
            $orig_url = filter_var($url, FILTER_SANITIZE_URL);
            $url = parse_url(preg_replace('/\s+/', '', $orig_url));

            if(!$url) {
                return '/';
            }

            if(!isset($url['host'])) {
                return $orig_url;
            }

            if($same_domain && $url['host'] !== $this->server['HTTP_HOST']) {
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

    /**
     * @param bool $status
     * @param object|array|string $message
     * @param string|null $next
     * @param string|null $message_tag
     * @return Response
     * @noinspection PhpUnused
     */
    public function restMessage(bool $status, object|array|string $message, ?string $next = null, ?string $message_tag = null): Response {

        return $this->response->setBody(RestMessage::new(
                ok: $status,
                message: $message instanceof Message ? $message->message : $message,
                next: $next,
                message_tag: $message_tag
            )
        );

    }

}
