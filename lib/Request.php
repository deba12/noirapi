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
        $static->method    = $server['REQUEST_METHOD'];
        $static->uri       = $server['REQUEST_URI'];
        $static->get       = $get;
        $static->post      = $post;
        $static->files     = $files;
        $static->cookies   = $cookies;

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

}
