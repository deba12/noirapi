<?php
/** @noinspection UnknownInspectionInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use Nette\Utils\Paginator;
use noirapi\Config;
use noirapi\Exceptions\ConfigException;
use Opis\Database\Connection;
use Opis\Database\Database;
use PDO;
use RuntimeException;

class Model {

    public string $driver = 'mysql';
    public Database $db;
    private static $pdo;

    /**
     * @throws ConfigException
     */
    public function __construct() {

        $db = Config::get('db');
        if(empty($db[$this->driver])) {
            throw new ConfigException('Model: unable to find config for: ' . $this->driver);
        }

        if(empty(self::$pdo[$this->driver])) {

            self::$pdo[$this->driver] = new PDO($this->driver . ':' . $db[$this->driver]['dsn'], $db[$this->driver]['user'] ?? null, $db[$this->driver]['pass'] ?? null);
            self::$pdo[$this->driver]->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
            self::$pdo[$this->driver]->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            self::$pdo[$this->driver]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo[$this->driver]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        }

        $this->db = new Database(Connection::fromPDO(self::$pdo[$this->driver]));

    }

    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function clear(): void {
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

    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function begin(): void {
        if($this->driver === 'mysql') {
            $this->db->getConnection()->getPDO()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        }
        $this->db->getConnection()->getPDO()->beginTransaction();
    }

    /**
     * @return void
     * @noinspection PhpUnused
     */
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

        if(!class_exists(Paginator::class)) {
            throw new RuntimeException('Unable to find nette/paginator');
        }

        $paginator = new Paginator();
        $paginator->setItemCount($itemCount);
        $paginator->setItemsPerPage($itemsPerPage);
        if($page !== null) {
            $paginator->setPage((int)$page);
        }

        return $paginator;

    }

    /**
     * @param string $text
     * @return bool
     * @noinspection PhpUnused
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
