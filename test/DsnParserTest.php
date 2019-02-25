<?php

namespace Prelude\Database;

class DsnParserTest extends \PHPUnit_Framework_TestCase {

    private static $STR_CONFIG = array(
        'driver' => 'driver',
          'host' => 'host.tld',
          'user' => 'u',
          'pass' => 'p',
    );

    private static $ARR_CONFIG = array(
        'driver' => 'pgsql',
          'host' => 'pg.sql'
    );

    /**
     * @expectedException \Prelude\Database\DsnException
     */
    function test_parseFile_missing() {
        DsnParser::parseFile(__FILE__ . '-missing-file.php');
    }

    function test_parseFile_array() {
        $dsn = DsnParser::parseFile(__FILE__ . '-array.php');
        $this->go_testDsn($dsn, self::$ARR_CONFIG);
    }

    function test_parseFile_string() {
        $dsn = DsnParser::parseFile(__FILE__ . '-string.php');
        $this->go_testDsn($dsn, self::$STR_CONFIG);
    }

    function test_parseEnv() {
        $_ENV['DATABASE_URL'] = require __FILE__ . '-string.php';
        $dsn = DsnParser::parseEnv('DATABASE_URL');
        $this->go_testDsn($dsn, self::$STR_CONFIG);
    }

    function test_parseUrl() {
        $dsn = DsnParser::parseUrl(require __FILE__ . '-string.php');
        $this->go_testDsn($dsn, self::$STR_CONFIG);
    }

    function test_parseArray() {
        $dsn = DsnParser::parseArray(require __FILE__ . '-array.php');
        $this->go_testDsn($dsn, self::$ARR_CONFIG);
    }

    /**
     * @expectedException \Prelude\Database\DsnException
     */
    function test_parse_throwsWithOthers() {
		$_ENV[__METHOD__] = true;
        DsnParser::parseEnv(__METHOD__);
    }

    /**
     * @expectedException \Prelude\Database\DsnException
     */
    function test_parse_throwsWhenKeyIsMissing() {
        unset($_ENV[__METHOD__]);
        DsnParser::parseEnv(__METHOD__);
    }

    private function go_testDsn(DSn $dsn, array $config) {
        forEach ($config as $key => $value) {
            $this->assertEquals($value, $dsn->{$key});
        }
    }
}
