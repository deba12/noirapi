<?php
/** @noinspection UnknownInspectionInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use Nette\Utils\Paginator;
use Opis\Database\Connection;
use Opis\Database\Database;
use PDO;

class Model {

    public $driver = 'mysql';
    public $db;
    private static $pdo;

    public function __construct() {

        if(empty(self::$pdo[$this->driver])) {

            self::$pdo[$this->driver] = new PDO($this->driver . ':' . DB[$this->driver]['dsn'], DB[$this->driver]['user'] ?? null, DB[$this->driver]['pass'] ?? null);
            self::$pdo[$this->driver]->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
            self::$pdo[$this->driver]->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            self::$pdo[$this->driver]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo[$this->driver]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        }

        $this->db = new Database(Connection::fromPDO(self::$pdo[$this->driver]));

    }

    public function clear() {
        unset(self::$pdo[$this->driver]);
    }

    /**
     * @return string
     */
    public function lastId(): string {
        return $this->db->getConnection()->getPDO()->lastInsertId();
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function in_transaction(): bool {
        return $this->db->getConnection()->getPDO()->inTransaction();
    }

    public function begin(): void {
        if($this->driver === 'mysql') {
            $this->db->getConnection()->getPDO()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        }
        $this->db->getConnection()->getPDO()->beginTransaction();
    }

    public function commit(): void {
        $this->db->getConnection()->getPDO()->commit();
        if($this->driver === 'mysql') {
            $this->db->getConnection()->getPDO()->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        }
    }

    /**
     * @noinspection PhpUnused
     */
    public function rollback(): void {
        $this->db->getConnection()->getPDO()->rollBack();
        if($this->driver === 'mysql') {
            $this->db->getConnection()->getPDO()->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        }
    }

    /**
     * @param int $itemCount
     * @param int $itemsPerPage
     * @param null $page
     * @return Paginator
     * @noinspection PhpUnused
     */
    public function paginator(int $itemCount, int $itemsPerPage = 20, $page = null): Paginator {

        $paginator = new Paginator();
        $paginator->setItemCount($itemCount);
        $paginator->setItemsPerPage($itemsPerPage);
        if($page !== null) {
            $paginator->setPage((int)$page);
        }

        return $paginator;

    }

    /**
     * @param string $table
     * @return void
     * @noinspection UnusedFunctionResultInspection
     */
    public function lock(string $table): void {
        $this->db->getConnection()->query('LOCK TABLES ' . $this->db->getConnection()->getPDO()->quote($table)  . ' WRITE', [$table]);
    }

    /**
     * @return void
     * @noinspection UnusedFunctionResultInspection
     */
    public function unlock(): void {
        $this->db->getConnection()->query('UNLOCK TABLES');
    }

    /**
     * @param string $text
     * @return bool
     */
    public function shouldRetry(string $text): bool {

        $errors = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'SSL connection has been closed unexpectedly',
        ];

        foreach($errors as $error) {
            if(str_contains($text, $error)) {
                return true;
            }
        }

        return false;

    }

}
