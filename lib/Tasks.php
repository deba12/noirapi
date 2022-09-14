<?php
declare(strict_types=1);

namespace noirapi\lib;

use RuntimeException;
use Swoole\Http\Server;

class Tasks {

    private array $tasks = [];
    private Server $server;
    private int $timeout;

    public function __construct(Server $server, int $timeout = 300) {
        $this->server = $server;
        $this->timeout = $timeout;
    }

    /**
     * @param string $class
     * @param array $params
     * @return Tasks
     */
    public function add(string $class, array $params): static {
        $task = 'app\\tasks\\' . $class;
        if(!class_exists($task)) {
            throw new RuntimeException("Task class $class not found");
        }

        $this->tasks[] = [
            'class' => $task,
            'params' => $params
        ];

        return $this;
    }

    public function run(): mixed {

        return $this->server->taskWaitMulti($this->tasks, $this->timeout);

    }

}
