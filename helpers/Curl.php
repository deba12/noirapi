<?php
declare(strict_types=1);

namespace noirapi\helpers;

use noirapi\lib\Controller;
use noirapi\Tracy\CurlBarPanel;
use Tracy\Debugger;

/** @psalm-api  */
class Curl extends \Curl\Curl {

    private static array $requests = [];

    public function __construct() {

        if(!isset(Controller::$panels['curl'])) {
            Controller::$panels['curl'] = true;

            $panel = new CurlBarPanel();
            $panel->title = 'Curl';

            Debugger::getBar()->addPanel($panel);
        }

        parent::__construct();

    }

    /**
     * @param mixed $ch
     * @return mixed
     */
    public function exec($ch = null): mixed {

        $start = microtime(true);

        $res = parent::exec($ch); // TODO: Change the autogenerated stub

        $info = $this->getInfo();
        $method = $this->getOpt(CURLOPT_CUSTOMREQUEST);

        $post_fields = $this->getOpt(CURLOPT_POSTFIELDS);
        if(!empty($post_fields) && is_string($post_fields)) {
            parse_str($post_fields, $post);
        }
        $this->addLog(
            url: ($method ?? 'POST') . ' ' . $this->getUrl(),
            info: $info['http_code'] . ' ' . $info['content_type'],
            time: microtime(true) - $start,
            request: $post ?? [],
            response: is_object($this->response) ? $this->response : substr((string)$this->response, 0 ,128));

        return $res;

    }

    /**
     * @param string $url
     * @param string $info
     * @param float $time
     * @param array $request
     * @param object|string|null $response
     * @return void
     */
    public function addLog(string $url, string $info, float $time, array $request, object|string $response = null): void {

        self::$requests[] = [
            'url'       => $url,
            'request'   => $request,
            'info'      => $info,
            'time'      => $time * 1000,
            'response'  => $response,
        ];

    }

    /**
     * @return array
     */
    public static function getLog(): array {

        return self::$requests;

    }

}
