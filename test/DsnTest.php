<?php

namespace Prelude\Database;

class DsnTest extends \PHPUnit_Framework_TestCase {

    /**
     * Tested object
     */
    private $dsn;

    const DRIVER =  Dsn::MYSQL;
    const HOST   = 'example.org';
    const PORT   =  1234;
    const DBNAME = 'name-of-db';
    const USER   = 'user-of-db';
    const PASS   = 's-e-c-r-e-t';

    function setUp() {
        $this->dsn = new Dsn(array(
            'driver' => self::DRIVER,
              'host' => self::HOST,
              'port' => self::PORT,
            'dbname' => self::DBNAME,
              'user' => self::USER,
              'pass' => self::PASS
        ));
    }

    function tearDown() {
        $this->dsn = null;
    }

    /**
     * @expectedException \Prelude\Database\DsnException
     */
    function test___construct() {
        new Dsn(array());
    }

    function testMagicGet() {
        $this->assertEquals(self::DRIVER, $this->dsn->driver);
        $this->assertEquals(self::HOST  , $this->dsn->host);
        $this->assertEquals(self::PORT  , $this->dsn->port);
        $this->assertEquals(self::DBNAME, $this->dsn->dbName);
        $this->assertEquals(self::USER, $this->dsn->user);
        $this->assertEquals(self::PASS, $this->dsn->pass);
    }

    function testMagicSet() {
        $this->assertEquals(Dsn::PGSQL, $this->dsn->driver = Dsn::PGSQL);
        $this->assertEquals(Dsn::PGSQL, $this->dsn->driver);

        $this->assertEquals(100, $this->dsn->port = 100);
        $this->assertEquals(100, $this->dsn->port);
    }

    function testMagicToString() {
        $this->goToString((string) $this->dsn);
    }

    function testToString() {
        $this->dsn->pass = 'secret';
        $this->goToString($this->dsn->toString());
    }

    function goToString($dsnStr) {
        $this->assertEquals(0, strpos($dsnStr, self::DRIVER));
    }

    function testToArray() {
        $dsnArr = $this->dsn->toArray();
        $this->assertInternalType('array', $dsnArr);

        $keys = array(
            'driver',
            'host',
            'dbname',
            'port',
            'user',
            'pass'
        );

        forEach ($keys as $key) {
            $this->assertArrayHasKey($key, $dsnArr);
            $const = constant(__CLASS__ . '::' . strtoupper($key));
            $this->assertEquals($const, $dsnArr[$key]);
        }
    }

    /**
     * @expectedException \Prelude\Database\DsnException
     */
    function testIssue_missing_driver_throws() {
        new Dsn(array(
            'host' => 'h.h',
            'user' => 'test'
        ));
    }

    function testIssue_driver_at_config() {
        $driver = 'db';
        $dsn = new Dsn(array(
            'driver' => $driver,
              'host' => 'example.org'
        ));

        $this->assertEquals($driver, $dsn->driver);
        $this->assertArrayHasKey('driver', $dsn->toArray());
        $this->assertEquals("$driver:host=example.org", $dsn->toString());
    }

    function testIssue_sqlite_dont_use_host() {

        $driver = Dsn::SQLITE;
        $path = '/path/to/db.sql';

        $dsn = new Dsn(array(
            'driver' => $driver,
              'host' => $path
        ));

        $this->assertEquals($driver, $dsn->driver);
        $this->assertEquals($path, $dsn->host);
        $this->assertStringStartsWith("$driver:$path", $dsn->toString());

        $this->assertEquals(Dsn::SQLITE, $dsn->driver);
        $this->assertEquals($path, $dsn->host);
    }

    function testIssue_pgsql_dont_use_pass() {
        $driver = Dsn::PGSQL;

        $dsn = new Dsn(array(
            'driver' => $driver,
              'host' => 'example.org',
              'user' => 'user',
              'pass' => 'pass'
        ));

        $this->assertEquals('pass', $dsn->pass);
        $this->assertEquals(Dsn::PGSQL . ':host=example.org', $dsn->toString());
    }
}
