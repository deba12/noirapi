<?php

declare(strict_types=1);

namespace Noirapi\Lib;

use RuntimeException;
use Swoole\Http\Server;

/** @psalm-api  */
class Tasks
{
    private array $tasks = [];
    /**
     * @var Server
     * @psalm-suppress UndefinedClass
     */
    private Server $server;
    private int $timeout;

    /**
     * @param Server $server
     * @param int $timeout
     * @psalm-suppress UndefinedClass
     */
    public function __construct(Server $server, int $timeout = 300)
    {
        $this->server = $server;
        $this->timeout = $timeout;
    }

    /**
     * @param string $class
     * @param array $params
     * @return self
     */
    public function add(string $class, array $params): self
    {
        $task = 'app\\tasks\\' . $class;
        if (! class_exists($task)) {
            throw new RuntimeException("Task class $class not found");
        }

        $this->tasks[] = [
            'class'  => $task,
            'params' => $params,
        ];

        return $this;
    }

    /**
     * @return mixed
     * @psalm-suppress UndefinedClass
     */
    public function run(): mixed
    {

        return $this->server->taskWaitMulti($this->tasks, $this->timeout);
    }
}
