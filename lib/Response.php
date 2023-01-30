<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUnused */
declare(strict_types = 1);

namespace noirapi\lib;

use JsonException;
use LaLit\Array2XML;
use noirapi\helpers\RestMessage;
use RuntimeException;
use function is_array;

class Response {

    private string|array|RestMessage $body = '';
    private int $status = 200;
    private string $contentType = self::TYPE_HTML;
    private array $headers = [];
    private array $cookies = [];

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
    public function setBody($body): Response {
        $this->body = $body;
        return $this;
    }

    /**
     * @param $body
     * @return $this
     */
    public function appendBody($body): Response {
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

        if($this->body instanceof RestMessage) {
            return $this->body->toJson();
        }

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

        if(($this->contentType === self::TYPE_CSV) && is_array($this->body)) {

            $this->body = $this->toCsv($this->body);

        }

        return $this->body;

    }

    /**
     * @return array|string
     */
    public function getRawBody(): array|string {
        return $this->body;
    }

    public function getRestMessage(): RestMessage {

        if($this->body instanceof RestMessage) {
            return $this->body;
        }

        throw new RuntimeException('Response body is not a RestMessage');

    }

    /**
     * @param int $status
     * @return $this
     */
    public function withStatus(int $status): Response {
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
    public function setContentType(string $contentType): Response {
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
    public function withLocation(string $location): Response {
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
    public function addHeader(string $key, string $value): Response {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function removeHeader(string $key): Response {
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
    public function downloadFile(string $filename): Response {
        $this->addHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $this;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function inlineFile(string $filename): Response {
        $this->addHeader('Content-Disposition', 'inline; filename="' . $filename . '"');
        $this->addHeader('Content-Transfer-Encoding', 'binary');
        return $this;
    }


    /**
     * @param string $key
     * @param string $value
     * @param int $expire
     * @return $this
     * $expire is max date in the future supported by php
     */
    public function addCookie(string $key, string $value, int $expire = 2147483647): Response {
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
    public function clearCookie(string $key): Response {
        $this->addCookie($key, '', 0);
        return $this;
    }

    /**
     * @return array
     */
    public function getCookies(): array {
        return $this->cookies;
    }

    private function toCsv($data): bool|string {

        $fh = fopen('php://temp', 'rwb');
        fputcsv($fh, array_keys(current($data)));

        foreach ( $data as $row ) {
            fputcsv($fh, $row);
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;

    }

}
