<?php /** @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

namespace noirapi\helpers;

use Exception;
use noirapi\Exceptions\UsersException;
use noirapi\lib\Model;

class Users {

	/** @var model */
	private $model;
	/** @var bool */
	private $status;
	/** @var callable  */
	private $hash;
	/** @var string  */
	private $secret;
	/** @var string  */
	private $table = 'users';
	/** @var int */
	private $lastId;

	public function __construct(Model $model, string $secret = null) {

		$this->model = $model;
		$this->hash = 'self::PasswordHashSha1';

		if($secret !== null) {
			$this->secret = $secret;
		} elseif(defined('SECRET')) {
			$this->secret = SECRET;
		} else {
			$this->secret = '';
		}

	}

	/**
	 * @param string $table
	 * @return $this
	 */
	public function setTable(string $table): users {
		$this->table = $table;
		return $this;
	}

	public function ok(): bool {
		return $this->status;
	}

	/**
	 * @param callable $hash
	 * @return $this
	 */
	public function setHash(callable $hash): users {
		$this->hash = $hash;
		return $this;
	}

	public function loginWithUsername(string $username, string $password): self {

		$hash = call_user_func($this->hash, $password);

		if(is_string($hash)) {

			$user = $this->model->db->from($this->table)
				->where('username')->is($username)
				->andWhere('password')->is($hash)
				->select()->first();

			if($user !== false) {
				$_SESSION['user'] = $user;
			}

			$this->status = $user !== false;

		} else {

			$this->status = false;

		}

		return $this;

	}

    /**
     * @param string $email
     * @param string $password
     * @return $this
     */
    public function loginWithEmail(string $email, string $password): self {

		$hash = call_user_func($this->hash, $password);

		if(is_string($hash)) {
			$user = $this->model->db->from($this->table)
				->where('email')->is($email)
				->andWhere('password')->is($hash)
				->select()->first();

			if($user !== false) {
				$_SESSION['user'] = $user;
			}

			$this->status = $user !== false;

		} else {

			$this->status = false;

		}

		return $this;

	}

    /**
     * @param string $username
     * @param string $password
     * @param string|null $email
     * @param string|null $ip
     * @return \noirapi\helpers\Users
     * @throws \noirapi\Exceptions\UsersException
     */
	public function newUserWithPassword(string $username, string $password, ?string $email = null, ?string $ip = null): self {

		if($this->checkEmail($email) !== null) {
			throw new UsersException('Email already exists');
		}

		if($this->checkUser($username) !== null) {
			throw new UsersException('Username already exists');
		}

		$hash = call_user_func($this->hash, $password);

		if(is_string($hash)) {

			$this->model->db->insert([
				'username' => $username,
				'password' => call_user_func($this->hash, $password),
				'email' => $email,
				'ip' => $ip
			])->into($this->table);

			$this->lastId = $this->model->lastId();
			$this->status = true;

		} else {

			$this->status = false;

		}

		return $this;

	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string|null $ip
	 * @return $this
	 * @throws usersException
	 */
	public function newUserWithEmail(string $email, string $password, ?string $ip = null): users {

		if($this->checkEmail($email) !== null) {
			throw new usersException('Email already exists');
		}

		$hash = call_user_func($this->hash, $password);

		if(is_string($hash)) {

			$this->model->db->insert([
				'username'  => $email,
				'email'     => $email,
				'password'  => $hash,
				'ip'        => $ip
			])->into('users');

			$this->lastId = $this->model->lastId();
			$this->status = true;

		} else {

			$this->status = false;

		}

		return $this;

	}

	/**
	 * @param int $user_id
	 * @param string $password
	 * @return bool
	 */
	public function checkPassword(int $user_id, string $password): bool {

		$hash = call_user_func($this->hash, $password);

		if(is_string($hash)) {

			$res = $this->model->db->from($this->table)
				->where('id')->is($user_id)
				->andWhere('password')->is($hash)
				->select()->first();

			return !($res === false);

		}

		return false;

	}

	/**
	 * @param string $email
	 * @return int|null
	 */
	public function checkEmail(string $email):?int {

		$res = $this->model->db->from($this->table)
			->where('email')->is($email)
			->select(static function($include) {
				$include->column('id');
			})->first(static function($id) {
				return $id;
			});

		return $res === false ? null : $res;

	}

	/**
	 * @param string $username
	 * @return int|null
	 */
	public function checkUser(string $username):?int {

		$res = $this->model->db->from($this->table)
			->where('username')->is($username)
			->select(static function($include) {
				$include->column('id');
			})->first(static function($id) {
				return $id;
			});

		return $res === false ? null : $res;

	}

	/**
	 * @param int $id
	 * @param string $password
	 * @return $this
	 * @throws Exception
	 */
	public function changePassword(int $id, string $password): users {

		$hash = call_user_func($this->hash, $password);

		if(is_string($hash)) {
			$this->status = (bool)$this->model->db->update($this->table)
				->where('id')->is($id)
				->set([
					'password'  => $hash,
					'salt'      => sha1(random_bytes(16) . $id)
				]);
		} else {
			$this->status = false;
		}

		return $this;

	}

	/**
	 * @param string $action
	 * @param string $identifier
	 * @param string $ip
	 * @return $this
	 */
	public function log(string $action, string $identifier, string $ip): users {

		$this->model->db->insert([
			'action'        => $action,
			'identifier'    => $identifier,
			'ip'            => $ip,
			'status'        => $this->status
		])->into('users_log');

		return $this;

	}

    /**
     * @return int
     */
    public function getLastId(): int {
	    return $this->lastId;
    }

	public function logout(): void {
		unset($_SESSION['user'],
			$_SESSION['admin'],
			$_SESSION['token']);
	}

	/**
	 * @param string $password
	 * @return false|string|null
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private function PasswordHashSha1(string $password): string {
		return sha1($this->secret . $password);
	}

	/**
	 * @param string $password
	 * @return false|string|null
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private function PasswordHash(string $password) {
		return password_hash($password,  PASSWORD_BCRYPT);
	}

}
