<?php
/** @noinspection UnknownInspectionInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use Nette\Utils\Paginator;
use noirapi\Config;
use noirapi\Exceptions\ConfigException;
use noirapi\PDO\PDO;
use Opis\Database\Connection;
use Opis\Database\Database;

use RuntimeException;

/**
 * @psalm-consistent-constructor
 */
class Model
{

    public string $driver = 'mysql';
    public Database $db;
    protected static array $pdo;

    /**
     * @throws ConfigException
     */
    public function __construct(array $params = [])
    {
        if(empty($params)) {

            if(empty(self::$pdo[$this->driver])) {

                $db = Config::get('db');
                if (empty($db[$this->driver])) {
                    throw new ConfigException('Model: unable to find config for: ' . $this->driver);
                }

                self::$pdo[$this->driver] = new PDO($this->driver . ':' . $db[$this->driver]['dsn'], $db[$this->driver]['user'] ?? null, $db[$this->driver]['pass'] ?? null);
                self::$pdo[$this->driver]->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
                self::$pdo[$this->driver]->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
                self::$pdo[$this->driver]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$pdo[$this->driver]->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

            }

            $this->db = new Database(Connection::fromPDO(self::$pdo[$this->driver]));

        } else {

            $pdo = new PDO($params['dsn'], $params['user'] ?? null, $params['pass'] ?? null);

            $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

            $this->db = new Database(Connection::fromPDO($pdo));

        }
    }

    /**
     * @return array
     */
    public static function tracyGetPdo(): array
    {
        if(! empty(self::$pdo)) {
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
    public function in_transaction(): bool
    {
        return $this->db->getConnection()->getPDO()->inTransaction();
    }

    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function begin(): void
    {
        if($this->driver === 'mysql') {
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

        if($this->driver === 'mysql') {
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

        if($this->driver === 'mysql') {
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
        if(! class_exists(Paginator::class)) {
            throw new RuntimeException('Unable to find nette/paginator');
        }

        $paginator = new Paginator();
        $paginator->setItemCount($itemCount);
        $paginator->setItemsPerPage($itemsPerPage);
        if($page !== null) {
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
