<?php
declare(strict_types = 1);

namespace noirapi\lib;

use Nette\SmartObject;
use function is_string;

class Request {

    use SmartObject;

    public array $headers;
    public string $method;
    public string $uri;
    public ?array $get;
    public ?array $post;
    public ?array $files;
    public ?array $cookies;
    public string $controller;
    public string $function;
    public array $route;
    public string $role;
    public bool $https;
    public bool $ajax;
    public $swoole = null;
    public string $host;
    public ?string $language = null;

    /**
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $cookies
     * @return static
     */
    public static function fromGlobals(array $server, array $get, array $post, array $files, array $cookies): static {

        $static = new static();

        $static->globalsRequestHeaders($server);
        $static->host       = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? '';
        $static->method     = $server['REQUEST_METHOD'];
        $static->uri        = $server['REQUEST_URI'];
        $static->get        = $get;
        $static->post       = $post;
        $static->files      = $files;
        $static->cookies    = $cookies;

        $static->https = self::is_https($server);
        $static->ajax = self::is_ajax($server);

        return $static;

    }

    public static function fromSwoole(array $server, array $get, array $post, array $files, array $cookies): static {

        $static = new static();
        $static->headers = self::swooleUpperCase($server['headers']);
        $static->method = $server['request_method'];
        $static->uri = $server['request_uri'];
        $static->get = $get;
        $static->post = $post;
        $static->files = $files;
        $static->cookies = $cookies;

        $static->https = self::is_https($server);
        $static->ajax = self::is_ajax($server);

        return $static;

    }

    private function globalsRequestHeaders(array $server): void {

        foreach ($server as $name => $value) {

            if (str_starts_with($name, 'HTTP_')) {
                //get Header key w/o HTTP_
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $this->headers[$key] = $value;
            }

        }

    }

    /**
     * @param array $headers
     * @return array
     */
    public static function swooleUpperCase(array $headers): array {

        $res = [];
        array_walk($headers, static function($value, $key) use(&$res) {
            if(is_string($key)) {
                $key = str_replace('-', '_', strtoupper($key));
            }
            $res[$key] = $value;
        });

        return $res;

    }

    /**
     * @param array $server
     * @return bool
     */
    private static function is_https(array $server): bool {

        if (isset($_SERVER['HTTPS'])) {

            if (strtolower($server['HTTPS'])  === 'on') {
                return true;
            }

            if ($server['HTTPS'] === "1") {
                return true;
            }

        } elseif (isset($server['SERVER_PORT']) && ($server['SERVER_PORT'] === 443)) {

            return true;

        }

        return false;

    }

    private static function is_ajax(array $server): bool {

        return isset($server[ 'HTTP_X_REQUESTED_WITH' ]) && strtolower($server[ 'HTTP_X_REQUESTED_WITH' ]) === 'xmlhttprequest';

    }

    /**
     * @param bool $https
     * @return $this
     * @noinspection PhpUnused
     */
    public function setHttps(bool $https): static {
        $this->https = $https;

        return $this;
    }

    /**
     * @param bool $ajax
     * @return $this
     * @noinspection PhpUnused
     */
    public function setAjax(bool $ajax): static {
        $this->ajax = $ajax;

        return $this;
    }

}
