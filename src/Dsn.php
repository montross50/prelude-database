<?php

namespace Prelude\Database;

use Prelude\Arrays;

/**
 * PDO provides a nice API for accesing database in a standard way, 
 * but the connection part is still handled using strings; and those 
 * are vendor-specific.
 * 
 * Dsn provide a simple standard to handle such differences by providing 
 * a consistent API to read the configuration, and then giving you the 
 * connected PDO object.
 * 
 * It simply stays *out of your way* while _integrating nice with others_.
 *
 * @property string driver
 * @property string|null host
 * @property string dbname
 * @property string|null user
 * @property string|null pass
 */
class Dsn {

    # common drivers
    const MYSQL = 'mysql';
    const MYSQL_SOCKET = 'mysql_socket';
    const DBLIB = 'dblib';
    const SQLSRV = 'sql_server';
    const PGSQL = 'pgsql';
    const SQLITE = 'sqlite';

    /**
     * @var string
     */
    private $driver;

    /**
     * @var array
     */
    private $config;

    /**
     * @param array  $config
     */
    function __construct(array $config) {
        $this->config = array_change_key_case($config, CASE_LOWER);
        $this->driver = Arrays::getOrCall($this->config, 'driver', function() {
        	throw new DsnException('missing `driver` key');
        });
        unset($this->config['driver']);
    }

    /**
     * @param string $key
     * @return mixed
     */
    function __get($key) {
        if ('driver' === $key = strtolower($key)) {
            return $this->driver;
        }
        return Arrays::get($this->config, $key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    function __set($key, $value) {
        if ('driver' === $key = strtolower($key)) {
            return $this->driver = $value;
        }
        return $this->config[$key] = $value;
    }

    /**
     * @return string
     */
    function __toString() {
        return $this->toString();
    }

    /**
     * @return string
     */
    function toString() {

        $driver = $this->driver . ':';

        if ($this->driver !== Dsn::SQLITE) {
            $config = $this->config;
        } else {
            $driver.= $this->config['host'] . ':';
            $config = $this->config;
            unset($config['host']);
        }

        unset($config['user'], $config['pass']);

        return $driver . http_build_query($config, null, ';');
    }

    /**
     * @return array
     */
    function toArray() {
        return array_merge(array('driver' => $this->driver), $this->config);
    }

    /**
     * @param array $options [optional]
     * @return \PDO
     */
    function connect(array $options=null) {
        // @codeCoverageIgnoreStart
        return new \PDO($this->toString(), $this->user, $this->pass, $options);
        // @codeCoverageIgnoreEnd
    }
}