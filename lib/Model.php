<?php
/** @noinspection UnknownInspectionInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use Nette\Utils\Paginator;
use Opis\Database\Connection;
use Opis\Database\Database;
use PDO;

class Model {

	public $driver;
	public $db;
	private static $pdo;

	public function __construct() {

		$config = 'mysql';

		if(!isset($this->driver)) {
			$this->driver = 'mysql:';
		}

		if(strpos($this->driver, 'dblib') > 0) {
			$config = 'mssql';
		}

		if(empty(self::$pdo[$config])) {
			self::$pdo[$config] = new PDO('mysql:host=' . DB[$config]['host'] . ';dbname=' . DB[$config]['db'], DB[$config]['user'], DB[$config]['pass']);
			self::$pdo[$config]->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
			self::$pdo[$config]->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			self::$pdo[$config]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			self::$pdo[$config]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
		}

		$this->db = new Database(Connection::fromPDO(self::$pdo[$config]));

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

	/** @noinspection RepetitiveMethodCallsInspection */
	public function begin(): void {
		$this->db->getConnection()->getPDO()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
		$this->db->getConnection()->getPDO()->beginTransaction();
	}

	/** @noinspection RepetitiveMethodCallsInspection */
	public function commit(): void {
		$this->db->getConnection()->getPDO()->commit();
		$this->db->getConnection()->getPDO()->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
	}

	/** @noinspection RepetitiveMethodCallsInspection
     * @noinspection PhpUnused
     */
	public function rollback(): void {
		$this->db->getConnection()->getPDO()->rollBack();
		$this->db->getConnection()->getPDO()->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
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

}
