<?php

namespace Prelude\Database;

use Prelude\Arrays;
use Prelude\Check;

final class QueryBuilder {

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $query;

    /**
     * @var array
     */
    private $params = array();

    /**
     * @var array
     */
    private $fetchMode;

    /**
     * @param \PDO $pdo
     */
    function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * @return string
     */
    function getQuery() {
        return $this->query;
    }

    /**
     * @param string $query
     * @return QueryBuilder
     */
    function setQuery($query) {
        Check::argument(null === $query or is_string($query));
        $this->query = $query;
        return $this;
    }

    /**
     * @return array
     */
    function getParams() {
        return $this->params;
    }

    /**
     * @param array $params or `null` to clean parameters
     * @return QueryBuilder
     */
    function setParams(array $params = null) {

        if (null === $params) {
            $this->params = null;
            return $this;
        }

        foreach ($params as $p => $v) {
            $this->setParam($p, $v);
        }
        return $this;
    }

    /**
     * @param string $param
     * @param mixed $value
     * @return QueryBuilder
     */
    function setParam($param, $value) {
        Check::argument(Arrays::isValidKey($param));
        $this->params[$param] = $value;
        return $this;
    }

    /**
     * @param string $param
     * @return mixed
     */
    function getParam($param) {
        return Arrays::get($this->params, $param);
    }

    /**
     * @param int $mode
     * @param mixed ...$modeArgs
     *
     * @return QueryBuilder
     */
    function setFetchMode($mode) {
        if ($mode) {
            $this->fetchMode = func_get_args();
        } else {
            $this->fetchMode = null;
        }
        return $this;
    }

    /**
     * return array
     */
    function getFetchMode() {
        return $this->fetchMode;
    }

    /**
     * @return int
     */
    function getFetchStyle() {
        if ($this->fetchMode) {
            return $this->fetchMode[0];
        }
    }

    /**
     * @return array
     */
    function getFetchArguments() {
        if ($this->fetchMode and $args = Arrays::tail($this->fetchMode)) {
            return $args;
        }
    }

    /**
     * @param string $class [optional]
     * @param array $ctorArguments [optional]
     *
     * @return QueryBuilder
     */
    function fetchObject($class = null, array $ctorArguments = null) {

        if ($class) {
            Check::argument(class_exists($class), 'class `%s` does not exists', $class);
            return $this->setFetchMode(\PDO::FETCH_CLASS, $class, $ctorArguments);
        }

        return $this->setFetchMode(\PDO::FETCH_OBJ);
    }

    /**
     * @param object $object
     * @return QueryBuilder
     */
    function fetchInto($object) {
        Check::argument(is_object($object), 'object required');
        return $this->setFetchMode(\PDO::FETCH_INTO, $object);
    }

    /**
     * @return QueryBuilder
     */
    function fetchArray() {
        return $this->setFetchMode(\PDO::FETCH_ASSOC);
    }

    /**
     * @return QueryBuilder
     */
    function fetchList() {
        return $this->setFetchMode(\PDO::FETCH_NUM);
    }

    /**
     * @param int|string $column
     * @return QueryBuilder
     */
    function fetchScalar($column = 0) {
        Check::argument(null === $column or is_scalar($column), 'scalar `column` required');
        return $this->setFetchMode(\PDO::FETCH_COLUMN, $column);
    }

    /**
     * @return Query
     * @throws \PDOException
     */
    function build() {

        Check::notEmpty($this->query, 'missing query string');

        if ($stmt = $this->pdo->prepare($this->query)) {

            if ($this->fetchMode) {
                call_user_func_array(array($stmt, 'setFetchMode'), $this->fetchMode);
            }

            return new Query($stmt, $this->params);
        }

        throw Exceptions::fromConnection($this->pdo);
    }

    /**
     * @param array $params
     * @return \PDOStatement
     * @throws \PDOException
     */
    function execute(array $params = null) {
        return $this->build()->execute($params);
    }
}
