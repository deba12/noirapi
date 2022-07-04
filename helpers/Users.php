<?php
/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

namespace noirapi\helpers;

use noirapi\lib\Model;

class Users {

    /** @var model */
    private Model $model;
    /** @var callable  */
    private $hash;
    /** @var string  */
    private string $secret;
    /** @var string  */
    private string $table = 'users';

    public function __construct(Model $model, string $secret = null) {

        $this->model = $model;
        $this->hash = 'self::PasswordHash';

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

    /**
     * @param callable $hash
     * @return $this
     */
    public function setHash(callable $hash): users {
        $this->hash = $hash;
        return $this;
    }

    /**
     * @param string $field
     * @param string $login
     * @param string $password
     * @return mixed
     */
    private function login(string $field, string $login, string $password): mixed {

        $user = $this->model->db->from($this->table)
            ->where($field)->is($login)
            ->select()
            ->first();

        if(empty($user)) {
            return null;
        }

        if(call_user_func($this->hash, $password, $user->password)) {
            return $user;
        }

        return null;

    }

    /**
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function loginWithUsername(string $username, string $password): mixed {
        return $this->login('username', $username, $password);
    }

    /**
     * @param string $email
     * @param string $password
     * @return $this
     */
    public function loginWithEmail(string $email, string $password): mixed {
        return $this->login('email', $email, $password);
    }

    /**
     * @param string $username
     * @param string $password
     * @param string|null $email
     * @param string|null $ip
     * @return int|null
     */
    public function newUserWithPassword(string $username, string $password, ?string $email = null, ?string $ip = null):? int {

        if(!$this->checkEmail($email) || !$this->checkUser($username)) {
            return null;
        }

        $hash = call_user_func($this->hash, $password);

        if(is_string($hash)) {

            $this->model->db->insert([
                'username' => $username,
                'password' => call_user_func($this->hash, $password),
                'email' => $email,
                'ip' => $ip
            ])->into($this->table);

            return (int)$this->model->lastId();

        }

        return null;

    }

    /**
     * @param string $email
     * @param string $password
     * @param string|null $ip
     * @return int|null
     */
    public function newUserWithEmail(string $email, string $password, ?string $ip = null):? int {

        if(!$this->checkEmail($email)) {
            return null;
        }

        $hash = call_user_func($this->hash, $password);

        if(is_string($hash)) {

            $this->model->db->insert([
                'username'  => $email,
                'email'     => $email,
                'password'  => $hash,
                'ip'        => $ip
            ])->into('users');

            return (int)$this->model->lastId();

        }

        return null;

    }

    /**
     * @param int $user_id
     * @param string $password
     * @return bool
     */
    public function checkPassword(int $user_id, string $password): bool {

        $res = $this->model->db->from($this->table)
            ->where('id')->is($user_id)
            ->select()
            ->first();

        if(empty($res)) {
            return false;
        }

        $hash = call_user_func($this->hash, $password, $res->password);

        return $hash === true;

    }

    /**
     * @param string $email
     * @return bool
     */
    public function checkEmail(string $email): bool {

        $res = $this->model->db->from($this->table)
            ->where('email')->is($email)
            ->count();

        return $res > 0;

    }

    /**
     * @param string $username
     * @return bool
     */
    public function checkUser(string $username): bool {

        $res = $this->model->db->from($this->table)
            ->where('username')->is($username)
            ->count();

        return $res > 0;

    }

    /**
     * @param int|string $id
     * @param string $password
     * @return bool
     */
    public function changePassword(int|string $id, string $password): bool {

        $hash = call_user_func($this->hash, $password);

        if(is_string($hash)) {

            $this->model->db->update($this->table)
                ->where('id')->is($id)
                ->set([
                    'password'  => $hash
                ]);

            return true;

        }

        return false;

    }

    /**
     * @param string $action
     * @param string $identifier
     * @param string $ip
     * @param bool $ok
     * @return void
     */
    public function log(string $action, string $identifier, string $ip, bool $ok): void {

        $this->model->db->insert([
            'action'        => $action,
            'identifier'    => $identifier,
            'ip'            => $ip,
            'status'        => $ok
        ])->into('users_log');

    }

    /**
     * @return void
     */
    public function logout(): void {

        session_destroy();

    }

    public function getHashedPassword(string $password): string {
        return call_user_func($this->hash, $password);
    }

    /**
     * @param string $password
     * @param string|null $hash
     * @return string|bool
     * @noinspection PhpUnusedPrivateMethodInspection
     * @noinspection PhpSameParameterValueInspection
     */
    private function PasswordSha1(string $password, ?string $hash = null): string|bool {
        if($hash === null) {
            return sha1($this->secret . $password);
        }

        return hash_equals(sha1($this->secret . $password), $hash);

    }

    /**
     * @param string $password
     * @param string|null $hash
     * @return bool|string
     * @noinspection PhpSameParameterValueInspection
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function PasswordHash(string $password, ?string $hash = null): bool|string {

        if($hash === null) {
            return password_hash($password,  PASSWORD_BCRYPT);
        }

        return password_verify($password, $hash);

    }

}
