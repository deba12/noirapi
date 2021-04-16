<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUnused */
declare(strict_types = 1);

namespace noirapi\lib;

use JsonException;
use LaLit\Array2XML;
use RuntimeException;

class Response {

    /** @var $body string|callable */
    private $body = '';

    /** @var $status int  */
    private $status = 200;

    /** @var $contentType string  */
    private $contentType = self::TYPE_HTML;

    /** @var $location string */
    private $location;

    /** @var $headers array  */
    private $headers = [];

    /** @var $cookies array  */
    private $cookies = [];

    public const TYPE_JSON  = 'application/json';
    public const TYPE_XML   = 'text/xml';
    public const TYPE_TEXT  = 'text/plain';
    public const TYPE_HTML  = 'text/html; charset: utf-8';
    public const TYPE_PDF   = 'application/pdf';
    public const TYPE_CSV   = 'text/csv';
    public const TYPE_CSS   = 'text/css';
    public const TYPE_JS    = 'text/javascript';
    public const TYPE_RAW   = 'application/octet-stream';
    public const TYPE_ZIP   = 'application/zip';

    public function __toString(): string {
        return $this->body;
    }

    /**
     * @param $body
     * @return $this
     */
    public function setBody($body): response {
        $this->body = $body;
        return $this;
    }

    /**
     * @param $body
     * @return $this
     */
    public function appendBody($body): response {
        $this->body .= $body;
        return $this;
    }

    /**
     * @return string
     * @throws JsonException
     * @throws RuntimeException
     * @noinspection PhpUndefinedClassInspection
     */
    public function getBody(): string {

        if($this->contentType === self::TYPE_HTML) {
            return $this->body;
        }

        if($this->contentType === self::TYPE_JSON) {

            if(is_array($this->body)){
                return json_encode($this->body, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
            }

            return $this->body;
        }

        if($this->contentType === self::TYPE_XML) {
            if(is_array($this->body)) {
                if(class_exists(Array2XML::class)) {
                    return Array2XML::createXML('root', $this->body)->saveXML();
                }

                throw new RuntimeException('Class LaLit\Array2XML is required');
            }

            return $this->body;
        }

        if(($this->contentType === self::TYPE_CSV) && !is_string($this->body)) {
            throw new RuntimeException('CSV Requires body to be string');
        }

        return $this->body;

    }

    /**
     * @param int $status
     * @return $this
     */
    public function withStatus(int $status): response {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @param string $contentType
     * @return $this
     */
    public function setContentType(string $contentType): response {
        $this->contentType = $contentType;
        $this->addHeader('Content-Type', $contentType);
        return $this;
    }

    /**
     * @return string
     */
    public function getContentType(): string {
        return $this->contentType;
    }

    /**
     * @param string $location
     * @return $this
     */
    public function withLocation(string $location): response {
        $this->headers['Location'] = $location;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocation(): string {
        if(empty($this->location)) {
            throw new RuntimeException('$response->setLocation is mandatory');
        }

        return $this->location;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addHeader(string $key, string $value): response {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function removeHeader(string $key): response {
        unset($this->headers[$key]);
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function downloadFile(string $filename): response {
        $this->addHeader('Content-Disposition', 'attachment; filename=' . $filename . '.');
        return $this;
    }


    /**
     * @param string $key
     * @param string $value
     * @param int $expire
     * @return $this
     * $expire is max date in the future supported by php
     */
    public function addCookie(string $key, string $value, int $expire = 2147483647): response {
        $this->cookies[$key] = [
            'key'       => $key,
            'value'     => $value,
            'expire'    => $expire,
            'secure'    => true,
            'httponly'  => true,
            'samesite'  => 'strict'
        ];
        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function clearCookie(string $key): response {
        $this->addCookie($key, '', 0);
        return $this;
    }

    /**
     * @return array
     */
    public function getCookies(): array {
        return $this->cookies;
    }

}
