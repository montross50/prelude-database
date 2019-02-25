<?php

namespace Prelude\Database;

final class Query {

    /**
     * @var \PDOStatement
     */
    private $stmt;

    /**
     * @param \PDOStatement $stmt
     * @param array $params [optional]
     */
    function __construct(\PDOStatement $stmt, array $params = null) {

        $this->stmt = $stmt;

        if ($params) {
            $this->bindParams($params);
        }
    }

    /**
     * @return \PDOStatement
     */
    function getStatement() {
        return $this->stmt;
    }

    /**
     * @param array $params
     */
    function bindParams(array $params) {
        foreach ($params as $key => $value) {
            $this->bindParam($key, $value);
        }
    }

    /**
     * @param string|int $param a string `:param` or a 0-indexed numeric offset
     * @param mixed $value
     *
     * @return void
     */
    function bindParam($param, $value) {

        if ($value === false) {
            // php casts `false` into a empty string
            // https://bugs.php.net/bug.php?id=33876
            $value = 0;
        }

        if (is_numeric($param)) {
            // PDO parameters are 1-indexed
            $param += 1;
        }

        if (is_resource($value)) {
            // http://php.net/pdo.constants
            $this->stmt->bindValue($param, $value, \PDO::PARAM_LOB);
        } else {
            $this->stmt->bindValue($param, $value);
        }
    }

    /**
     * @param array $params [optional]
     * @return \PDOStatement
     * @throws \PDOException
     */
    function execute(array $params = null) {

        if ($params) {
            $this->bindParams($params);
        }

        if ($this->stmt->execute()) {
            return $this->stmt;
        }

        throw Exceptions::fromStatement($this->stmt);
    }

    /**
     * @return void
     */
    private function ensureExecuted() {
        if (!$this->stmt->columnCount()) {
            $this->execute();
        }
    }

    /**
     * @return mixed
     */
    function fetch() {
        $this->ensureExecuted();
        return call_user_func_array(array($this->stmt, __FUNCTION__), func_get_args());
    }

    /**
     * @return array
     */
    function fetchAll() {
        $this->ensureExecuted();
        return call_user_func_array(array($this->stmt, 'fetchAll'), func_get_args());
    }

    /**
     * @param int|string $column [optional]
     * @return array
     */
    function fetchScalar($column=0) {
        $this->ensureExecuted();
        return $this->stmt->fetchColumn($column);
    }

    /**
     * @param string $class
     * @param array $ctorArgs
     * @return object
     */
    function fetchObject($class=null, array $ctorArgs=null) {
        $this->ensureExecuted();
        return call_user_func_array(array($this->stmt, 'fetchObject'), func_get_args());
    }

    /**
     * @param object $object
     * @return object
     */
    function fetchInto($object) {
        $this->ensureExecuted();

        $this->stmt->setFetchMode(\PDO::FETCH_INTO, $object);
        return $this->stmt->fetch();
    }

    /**
     * @return array
     */
    function fetchArray() {
        $this->ensureExecuted();

        if ($args = func_get_args()) {
            $args[0] += \PDO::FETCH_ASSOC;
        } else {
            $args = array(\PDO::FETCH_ASSOC);
        }

        return call_user_func_array(array($this->stmt, 'fetch'), $args);
    }

    /**
     * @return array
     */
    function fetchList() {
        $this->ensureExecuted();

        if ($args = func_get_args()) {
            $args[0] += \PDO::FETCH_NUM;
        } else {
            $args = array(\PDO::FETCH_NUM);
        }

        return call_user_func_array(array($this->stmt, 'fetch'), $args);
    }
}
