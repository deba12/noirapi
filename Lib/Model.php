<?php

declare(strict_types=1);

namespace Noirapi\Lib;

use Nette\Utils\Paginator;
use Noirapi\Config;
use Noirapi\Lib\PDO\PDO;
use Opis\Database\Connection;
use Opis\Database\Database;
use RuntimeException;

/**
 * @psalm-consistent-constructor
 */
class Model
{
    public string $driver;
    public Database $db;
    protected static array $pdo;
    private array $params;

    /**
     * @param string $driver
     * @param array $params
     */
    public function __construct(string $driver = 'mysql', array $params = [])
    {
        if (empty($params)) {
            $db = Config::get('db');
            $this->params = $db[$driver];
            $new = false;
        } else {
            $this->params = $params;
            $new = true;
        }
        $this->driver = $driver;
        $this->connect($new);
    }

    /**
     * @return static
     * @noinspection PhpUnused
     */
    public static function getCachedInstance(): static
    {
        return new static();
    }

    /**
     * @param bool $new
     * @return void
     */
    public function connect(bool $new): void
    {
        if (str_starts_with($this->driver, 'sqlite') && (! str_starts_with($this->params['dsn'], '/') &&
                ! str_contains($this->params['dsn'], 'memory'))) {
            $this->params['dsn'] = Config::getRoot() . '/data/' . $this->params['dsn'];
        }

        if ($new) {
            $pdo = new PDO(
                $this->driver . ':' . $this->params['dsn'],
                $this->params['user'] ?? null,
                $this->params['pass'] ?? null
            );
            $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

            self::$pdo[$this->driver] = $pdo;
        } else {
            if (! isset(self::$pdo[$this->driver])) {
                self::$pdo[$this->driver] = new PDO(
                    $this->driver . ':' . $this->params['dsn'],
                    $this->params['user'] ?? null,
                    $this->params['pass'] ?? null
                );
                self::$pdo[$this->driver]->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
                self::$pdo[$this->driver]->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
                self::$pdo[$this->driver]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$pdo[$this->driver]->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
            }
        }
        $this->db = new Database(Connection::fromPDO(self::$pdo[$this->driver]));
    }

    /**
     * @return array
     */
    public static function tracyGetPdo(): array
    {
        if (! empty(self::$pdo)) {
            return self::$pdo;
        }

        return [];
    }

    /**
     * @return void
     * @noinspection PhpUnused
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function clear(): void
    {
        unset(self::$pdo[$this->driver]);
    }

    /**
     * @return string
     */
    public function lastId(): string
    {
        return $this->db->getConnection()->getPDO()->lastInsertId();
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function inTransaction(): bool
    {
        return $this->db->getConnection()->getPDO()->inTransaction();
    }

    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function begin(): void
    {
        if ($this->driver === 'mysql') {
            $this->db->getConnection()->getPDO()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
        }

        $this->db->getConnection()->getPDO()->beginTransaction();
    }

    /**
     * @return void
     * @noinspection PhpUnused
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function commit(): void
    {
        $this->db->getConnection()->getPDO()->commit();

        if ($this->driver === 'mysql') {
            $this->db->getConnection()->getPDO()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        }
    }

    /**
     * @noinspection PhpUnused
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function rollback(): void
    {
        $this->db->getConnection()->getPDO()->rollBack();

        if ($this->driver === 'mysql') {
            $this->db->getConnection()->getPDO()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        }
    }

    /**
     * @param int $itemCount
     * @param int $itemsPerPage
     * @param int|null $page
     * @return Paginator
     * @noinspection PhpUnused
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function paginator(int $itemCount, int $itemsPerPage = 20, ?int $page = null): Paginator
    {
        if (! class_exists(Paginator::class)) {
            throw new RuntimeException('Unable to find nette/paginator');
        }

        $paginator = new Paginator();
        $paginator->setItemCount($itemCount);
        $paginator->setItemsPerPage($itemsPerPage);
        if ($page !== null) {
            $paginator->setPage($page);
        }

        return $paginator;
    }

    /**
     * @param string $table
     * @return void
     * @noinspection UnusedFunctionResultInspection
     * @noinspection PhpUnused
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function lock(string $table): void
    {
        $this->db->getConnection()->query("LOCK TABLES $table WRITE");
    }

    /**
     * @return void
     * @noinspection UnusedFunctionResultInspection
     * @noinspection PhpUnused
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function unlock(): void
    {
        $this->db->getConnection()->query('UNLOCK TABLES');
    }
}
