<?php
declare(strict_types=1);

namespace noirapi\PDO;

use PDOStatement as NativePdoStatement;

class PDOStatement extends NativePdoStatement {
    /**
     * PDO instance.
     */
    protected PDO $pdo;

    /**
     * For binding simulations purposes.
     */
    protected array $bindings = [];

    protected function __construct(PDO $pdo) {

        $this->pdo = $pdo;

    }

    /**
     * @param $param
     * @param $var
     * @param $type
     * @param $maxLength
     * @param $driverOptions
     * @return bool
     */
    public function bindParam($param, &$var, $type = \PDO::PARAM_STR, $maxLength = null, $driverOptions = null): bool {
        $this->bindings[$param] = $var;
        return parent::bindParam($param, $var, $type, $maxLength, $driverOptions);

    }

    /**
     * @param $param
     * @param $value
     * @param $type
     * @return bool
     */
    public function bindValue($param, $value, $type = \PDO::PARAM_STR): bool {
        $this->bindings[$param] = $value;
        return parent::bindValue($param, $value, $type);

    }

    /**
     * @param $params
     * @return bool
     */
    public function execute($params = null): bool {

        if (is_array($params)) {
            $this->bindings = $params;
        }

        $start  = microtime(true);
        $result = parent::execute($params);

        $this->pdo->addLog(
            $this->produceStatementWithBindingsInForLogging($this->bindings, $this->queryString),
            microtime(true) - $start
        );

        return $result;

    }

    private function produceStatementWithBindingsInForLogging(array $bindings, string $query): string {

        $indexed = (array_is_list($bindings));

        $result = $query;

        foreach ($bindings as $value) {
            $valueForPresentation = $this->translateValueForPresentationInsideStatement($value);
            $result = preg_replace('/\?/', $valueForPresentation, $result, 1);
        }

        return $result;

    }

    private function translateValueForPresentationInsideStatement(mixed $value): string {

        $result = $value;

        if ($value === null) {
            $result = 'null';
        } elseif (is_string($value)) {
            $result = $this->pdo->quote($value);
        } elseif ($value === false) {
            $result = '0';
        } elseif ($value === true) {
            $result = '1';
        }

        return (string)$result;

    }

}
