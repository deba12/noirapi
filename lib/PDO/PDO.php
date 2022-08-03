<?php
declare(strict_types=1);

namespace noirapi\PDO;

use PDO as NativePdo;

class PDO extends NativePdo {
    /**
     * Logged queries.
     * @var array<array>
     */
    protected array $log = [];

    /**
     * @inheritDoc
     */
    public function __construct($dsn, $username = null, $passwd = null, $options = null) {

        parent::__construct($dsn, $username, $passwd, $options);
        $this->setAttribute(self::ATTR_STATEMENT_CLASS, [PDOStatement::class, [$this]]);

    }


    /**
     * @param $statement
     * @return bool|int
     */
    public function exec($statement): false|int {

        $start = microtime(true);
        $result = parent::exec($statement);
        $this->addLog($statement, microtime(true) - $start);

        return $result;

    }


    /**
     * @param $statement
     * @param int $mode
     * @param ...$ctorargs
     * @return false|\PDOStatement
     */
    public function query($statement, $mode = NativePdo::ATTR_DEFAULT_FETCH_MODE, ...$ctorargs): false|\PDOStatement {
        $start = microtime(true);
        $result = parent::query($statement, $mode, ...$ctorargs);

        $this->addLog($statement, microtime(true) - $start);

        return $result;
    }

    /**
     * Add query to logged queries.
     *
     * @param string $statement
     * @param float $time Elapsed seconds with microseconds
     */
    public function addLog(string $statement, float $time): void {

        $this->log[] = [
            'statement' => $statement,
            'time' => $time * 1000
        ];

    }

    /**
     * Return logged queries.
     * @return array<array{statement:string, time:float}> Logged queries
     * @noinspection PhpUnused
     */
    public function getLog(): array {

        return $this->log;

    }

}
