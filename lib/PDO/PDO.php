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
    public function __construct(string $dsn, ?string $username = null, ?string $passwd = null, ?array $options = null) {

        parent::__construct($dsn, $username, $passwd, $options);
        $this->setAttribute(self::ATTR_STATEMENT_CLASS, [PDOStatement::class, [$this]]);

    }


    /**
     * @param string $statement
     * @return false|int
     */
    public function exec(string $statement): false|int {

        $start = microtime(true);
        $result = parent::exec($statement);
        $this->addLog($statement, microtime(true) - $start);

        return $result;

    }

    /**
     * @param string $query
     * @param int|null $fetchMode
     * @param mixed ...$fetch_mode_args
     * @return false|\PDOStatement
     */
    public function query(string $query, ?int $fetchMode = NativePdo::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args): false|\PDOStatement {
        $start = microtime(true);
        /** @psalm-suppress TooManyArguments $result */
        $result = parent::query($query, $fetchMode, ...$fetch_mode_args);

        $this->addLog($query, microtime(true) - $start);

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
