<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpParamsInspection
 * @noinspection PhpMissingFieldTypeInspection
 */
declare(strict_types = 1);

namespace noirapi\lib;

use Laminas\Permissions\Acl\Acl;
use noirapi\Config;
use noirapi\Exceptions\LoginException;
use noirapi\Exceptions\MessageException;
use noirapi\Exceptions\UnableToForwardException;
use noirapi\helpers\Message;
use noirapi\helpers\RestMessage;
use noirapi\helpers\Session;
use noirapi\helpers\Utils;
use noirapi\Tracy\PDOBarPanel;
use Throwable;
use Tracy\Debugger;
use function get_class;
use function in_array;

class Controller {

    public Request $request;
    public array $server;
    /** @var Model|null */
    public $model;
    public Response $response;
    public ?View $view = null;
    public bool $dev;
    /** @var mixed|non-empty-array<array-key, true>|null */
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

        if($db) {

            if(empty($this->model)) {

                $model = 'app\\models\\' . Utils::getClassName(get_class($this));
                if(class_exists($model) && is_subclass_of($model, Model::class)) {
                    $this->model = new $model();
                } else {
                    $this->model = new Model();
                }

            }

            /**
             * Tracy debug bar
             */
            if($this->dev) {

                foreach($this->model::tracyGetPdo() as $driver => $pdo) {

                    if(!isset(self::$panels[$driver])) {
                        self::$panels[$driver] = true;

                        $panel = new PDOBarPanel($pdo);
                        $panel->title = $driver;
                        Debugger::getBar()->addPanel($panel);
                    }

                }

            }

        }

        $this->response = new Response();

        // We need this when we are moving across domains
        if(isset($this->request->get['message'], $this->request->get['type'])) {
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
            if($location === '/') {
                $location = '/' . $this->request->language;
            }
            if($location !== '/' . $this->request->language) {
                $location = '/' . $this->request->language . $location;
            }
            return $this->response->withStatus($status)->withLocation($location);
        }

        return $this->response->withStatus($status)->withLocation($location);
    }

    /**
     * @return Response
     * @noinspection PhpUnused
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function ok(): Response {
        return $this->response->withStatus(200);
    }

    /**
     * @return Response
     * @noinspection PhpUnused
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function notFound(): Response {
        return $this->response->withStatus(404);
    }

    /**
     * @return Response
     * @noinspection PhpUnused
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function internalServerError(): Response {
        return $this->response->withStatus(500);
    }

    /**
     * @param string|Message $text
     * @param string|null $type
     * @param string|null $translation_key
     * @param mixed ...$translation_args
     * @return $this
     */
    public function message(string|Message $text, ?string $type = null, ?string $translation_key = null, ...$translation_args): self
    {

        Session::remove('message');

        if($translation_key !== null) {
            try {
                if($text instanceof Message) {
                    $text->message = $this->view?->translator->translate($text->message, $translation_key, $translation_args);
                } else {
                    $text = Message::new($this->view?->translator->translate($text, $translation_key, $translation_args), $type ?? 'danger');
                }
            } catch (Throwable) {
                // Do nothing
            }
        }

        if($text instanceof Message) {
            Session::set('message', null, $text);
        } else {
            Session::set('message', null, Message::new($text, $type ?? 'danger'));
        }

        if($this->dev) {

            $bt = debug_backtrace();
            $caller = array_shift($bt);

            $this->response->initiator_class = $caller['class'];
            $this->response->initiator_method = $caller['function'];
            $this->response->initiator_line = $caller['line'];

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
            if(empty($url)) {
                return '/';
            }

            $orig_url = filter_var($url, FILTER_SANITIZE_URL);
            if(empty($orig_url)) {
                return '/';
            }

            /** @psalm-suppress PossiblyInvalidCast */
            $url = parse_url((string)preg_replace('/\s+/', '', $orig_url));
            if(empty($url)) {
                return '/';
            }

            if(!isset($url['host'])) {
                return $orig_url;
            }

            if($url['host'] === $this->server['HTTP_HOST']) {

                $url['path'] = empty($url['path']) ? '/' : $url['path'];

                foreach(Config::get('languages') ?? [] as $code => $_) {

                    // Condition like /en
                    if($url['path'] === '/' . $code) {
                        return '/' . $code;
                    }
                    // Condition like /en
                    if(str_starts_with($url['path'], '/' . $code . '/')) {
                        $path = substr($url['path'], strlen($code) + 1);
                        return $path . (empty($url['query']) ?  '' : '?' . ($url['query']));
                    }

                }

                return (empty($this->request->language)  ? '' : '/' . $this->request->language) . $url['path'] . (empty($url['query']) ?  '' : '?' . ($url['query']));

            }

            if($same_domain) {
                return '/';
            }

            if(isset($url['scheme']) && ($url['scheme'] === 'http' || $url['scheme'] === 'https')) {

                $url['path'] = empty($url['path']) ? '/' : $url['path'];

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

    /**
     * @param Acl $acl
     * @return void
     * @throws LoginException
     * @throws MessageException
     */
    public function hasResource(Acl $acl): void {

        if(!$acl->hasResource($this->request->controller)){

            if($this->request->ajax) {
                throw new MessageException('Page not Found', 403);
            }

            $this->message('Page not found', 'danger');
            throw new LoginException('/');

        }

    }

    /**
     * @param Acl $acl
     * @return void
     * @throws LoginException
     * @throws MessageException
     */
    public function isAllowed(Acl $acl): void {

        if(!$acl->isAllowed($this->request->role, $this->request->controller)) {

            if($this->request->ajax) {
                throw new MessageException('Please Login', 403);
            }

            if($this->request->method === 'POST') {
                $login = $this->referer();
            } else {
                $login = $this->request->uri;
            }

            Session::set('login', null, $login);

            $this->message('Please login', 'success');
            throw new LoginException('/');

        }

    }

}
