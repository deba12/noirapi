<?php
declare(strict_types=1);

namespace noirapi\PDO;

use PDOStatement as NativePdoStatement;

/** @psalm-suppress MissingTemplateParam */
class PDOStatement extends NativePdoStatement {
    /**
     * PDO instance.
     */
    protected PDO $pdo;

    /**
     * For binding simulations purposes.
     */
    protected array $bindings = [];

    protected function __construct(PDO $pdo)
    {

        $this->pdo = $pdo;

    }

    /**
     * @param int|string $param
     * @param mixed $var
     * @param int $type
     * @param int|null $maxLength
     * @param mixed $driverOptions
     * @return bool
     * @psalm-suppress PossiblyNullArgument
     */
    public function bindParam(int|string $param, mixed &$var, int $type = \PDO::PARAM_STR, int $maxLength = null, mixed $driverOptions = null): bool
    {
        $this->bindings[$param] = $var;
        return parent::bindParam($param, $var, $type, $maxLength, $driverOptions);

    }

    /**
     * @param int|string $param
     * @param mixed $value
     * @param int $type
     * @return bool
     */
    public function bindValue(int|string $param, mixed $value, int $type = \PDO::PARAM_STR): bool
    {
        $this->bindings[$param] = $value;
        return parent::bindValue($param, $value, $type);

    }

    /**
     * @param array|null $params
     * @return bool
     */
    public function execute(?array $params = null): bool
    {

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

    private function produceStatementWithBindingsInForLogging(array $bindings, string $query): string
    {

        $result = $query;

        foreach ($bindings as $value) {
            $valueForPresentation = $this->translateValueForPresentationInsideStatement($value);
            $result = preg_replace('/\?/', $valueForPresentation, $result, 1);
        }

        return $result;

    }

    private function translateValueForPresentationInsideStatement(mixed $value): string
    {

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
