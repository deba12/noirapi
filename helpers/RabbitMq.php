<?php declare(strict_types = 1);

namespace noirapi\helpers;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMq {

    /** @var AMQPStreamConnection */
    private $conn;
    /** @var AMQPChannel  */
    private $ch;
    /** @var mixed|string  */
    private $queue;
    /** @var int */
    private $expiration;

    /**
     * rabbit constructor.
     * @param string|null $queue
     */
    public function __construct(?string $queue) {

        if(empty($queue)) {
            $this->queue = 'default';
        } else {
            $this->queue = $queue;
        }

        $host = defined('RABBIT_HOST') ? RABBIT_HOST : 'localhost';
        $user = defined('RABBIT_USER') ? RABBIT_USER : 'guest';
        $pass = defined('RABBIT_PASS') ? RABBIT_PASS : 'guest';

        $this->conn = new AMQPStreamConnection($host, 5672, $user, $pass);
        $this->ch = $this->conn->channel();
        $parameters = [
            'x-max-priority' => ['I', 10],
        ];

        /** @noinspection UnusedFunctionResultInspection */
        $this->ch->queue_declare($this->queue, false, true, false, false, $parameters);

    }

    /**
     * @throws Exception
     */
    public function __destruct() {

        $this->ch->close();
        $this->conn->close();

    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function setExpiration(int $seconds): rabbit {
        $this->expiration = $seconds;
        return $this;
    }

    /**
     * @param string $msgBody
     * @param int $priority
     */
    public function send(string $msgBody, int $priority = 10): void {

        $msg = new AMQPMessage($msgBody, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'priority' => $priority,
            //'expiration' => $this->expiration ?? 0
        ]);

        if(!empty($this->expiration)) {
            $msg['expiration'] = $this->expiration;
        }

        $this->ch->basic_publish($msg, '', $this->queue);


    }

    /**
     * @param array $messages
     * @param int $priority
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function sendBatch(array $messages, $priority = 10): void {

        foreach($messages as $msgBody) {

            $msg = new AMQPMessage($msgBody,[
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_NON_PERSISTENT,
                'priority' => $priority,
                'expiration' => $this->expiration ?? 0
            ]);

            $this->ch->batch_basic_publish($msg,'', $this->queue);

        }

        $this->ch->publish_batch();

    }

    /**
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function flushBatch(): void {

        $this->ch->publish_batch();

    }

    public function purge(): void {
        $this->ch->queue_purge($this->queue);
    }

    public function receive($callback): void {

        $this->ch->basic_qos(null, 1, null);

        /** @noinspection UnusedFunctionResultInspection */
        $this->ch->basic_consume(
            $this->queue,
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        while(count($this->ch->callbacks)) {

            try {

                $this->ch->wait();

            } catch(Exception $e) {

                echo $e->getMessage();

            }

        }

    }

    /**
     * @return array|null
     */
    public function listQueue(): ?array {

        return $this->ch->queue_declare($this->queue, true);

    }

}
