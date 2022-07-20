<?php
declare(strict_types=1);

namespace noirapi\helpers;

class Curl extends \Curl\Curl {

    private static array $requests = [];

    public function exec($ch = null): mixed {

        $start = microtime(true);

        $res = parent::exec($ch); // TODO: Change the autogenerated stub

        $info = $this->getInfo();
        $method = $this->getOpt(CURLOPT_CUSTOMREQUEST);
        $this->addLog(($method ?? 'POST') . ' ' . $this->getUrl(), $info['http_code'] . ' ' . $info['content_type'], microtime(true) - $start);

        return $res;

    }

    public function addLog(string $request, $info, float $time): void {

        self::$requests[] = [
            'request'   => $request,
            'info'      => $info,
            'time'      => $time * 1000
        ];

    }

    /**
     * @return array
     */
    public static function getLog(): array {

        return self::$requests;

    }

}
