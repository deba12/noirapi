<?php

namespace noirapi\lib;

use Nette\SmartObject;

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

    public function requestHeaders(array $server): void {

        foreach ($server as $name => $value) {

            if (str_starts_with($name, 'HTTP_')) {
                //get Header key w/o HTTP_
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $this->headers[$key] = $value;
            }

        }

    }

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

        $static->requestHeaders($server);
        $static->method    = $server['REQUEST_METHOD'];
        $static->uri       = $server['REQUEST_URI'];
        $static->get       = $get;
        $static->post      = $post;
        $static->files     = $files;
        $static->cookies   = $cookies;

        return $static;

    }

}
