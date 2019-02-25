<?php

namespace Prelude\Database;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var QueryBuilder The tested class
     */
    private $builder;

    function setUp() {
        $this->builder = new QueryBuilder(
            new \PDO('sqlite::memory:')
        );
    }

    private function goSetGet($set, $get, $value) {
        $this->assertSame($this->builder, $this->builder->{$set}($value));
        $this->assertEquals($value, $this->builder->{$get}());
    }

    /**
     * @dataProvider queryStrings
     */
    function testQuery($query) {
        $this->goSetGet('setQuery', 'getQuery', $query);
    }

    function queryStrings() {
        return array(
            array(null),
            array('select * from table'),
            array('not-a-sql-string'),
        );
    }

    /**
     * @dataProvider badQueryStrings
     * @expectedException \InvalidArgumentException
     */
    function testQueryThrows($badQuery) {
        $this->builder->setQuery($badQuery);
    }

    function badQueryStrings() {
        return array(
            array(12),
            array(false),
            array(true),
            array(array()),
            array($this),
            array(1.2),
        );
    }

    /**
     * @dataProvider paramMaps
     */
    function testParams(array $params=null) {
        $this->goSetGet('setParams', 'getParams', $params);

        $count = 0;

        foreach ($params as $param => $value) {
            $this->assertEquals($value, $this->builder->getParam($param));

            $this->assertSame($value, $this->builder->getParam($param));
            $this->assertEquals($this->builder, $this->builder->setParam($param, null));
            $this->assertNull($this->builder->getParam($param));

            $count += 1;
        }

        $this->assertCount($count, $this->builder->getParams());

        $this->assertSame($this->builder, $this->builder->setParams(null));
        $this->assertNull($this->builder->getParams());
    }

    function paramMaps() {
        return array(
            array(array()),
            array(array('a' => 1)),
            array(array('a' => 1,
                        'b' => 'd')
            ),
        );
    }

    function testFetchMode() {
        $this->assertNull($this->builder->getFetchMode());
        $this->assertSame($this->builder, $this->builder->setFetchMode(1, 2, 3));

        $this->assertEquals(1, $this->builder->getFetchStyle());
        $this->assertEquals(array(1, 2, 3), $this->builder->getFetchMode());
        $this->assertEquals(array(2, 3), $this->builder->getFetchArguments());

        $this->assertSame($this->builder, $this->builder->setFetchMode(null));
        $this->assertNull($this->builder->getFetchStyle());
        $this->assertNull($this->builder->getFetchMode());
        $this->assertNull($this->builder->getFetchArguments());
    }
    /**
     * @dataProvider fetchObjects
     */
    function testFetchObject($class = null, $ctorArgs = null) {

        $this->assertNull($this->builder->getFetchStyle());
        $this->assertEquals($this->builder, $this->builder->fetchObject($class, $ctorArgs));

        if ($class) {
            $mode = \PDO::FETCH_CLASS;
            $this->assertEquals($mode, $this->builder->getFetchStyle());
            $this->assertEquals(array($class, $ctorArgs), $this->builder->getFetchArguments());
            $this->assertEquals(array($mode, $class, $ctorArgs), $this->builder->getFetchMode());
        } else {
            $mode = \PDO::FETCH_OBJ;
            $this->assertEquals($mode, $this->builder->getFetchStyle());
            $this->assertEquals(null, $this->builder->getFetchArguments());
            $this->assertEquals(array($mode), $this->builder->getFetchMode());
        }
    }

    function fetchObjects() {
        return array(
            array(),
            array('StdClass'),
            array('StdClass', array(1, 2, 3)),
        );
    }

    /**
     * @dataProvider fetchIntos
     */
    function testFetchInto($object) {
        $this->assertNull($this->builder->getFetchStyle());
        $this->assertEquals($this->builder, $this->builder->fetchInto($object));

        $this->assertEquals(\PDO::FETCH_INTO, $this->builder->getFetchStyle());
        $this->assertEquals(array($object), $this->builder->getFetchArguments());
        $this->assertEquals(array(\PDO::FETCH_INTO, $object), $this->builder->getFetchMode());
    }

    function fetchIntos() {
        return array(
            array($this),
            array(new \stdClass()),
            array(new \EmptyIterator()),
        );
    }

    /**
     * @dataProvider fetchScalars
     * @expectedException \InvalidArgumentException
     */
    function testFetchIntoThrows($notObject=null) {
        $this->builder->fetchInto($notObject);
    }

    /**
     * @dataProvider fetchScalars
     */
    function testFetchScalar($column=null) {
        $this->assertNull($this->builder->getFetchStyle());
        $this->assertEquals($this->builder, $this->builder->fetchScalar($column));

        $this->assertEquals(\PDO::FETCH_COLUMN, $this->builder->getFetchStyle());
        $this->assertEquals(array($column), $this->builder->getFetchArguments());
        $this->assertEquals(array(\PDO::FETCH_COLUMN, $column), $this->builder->getFetchMode());

    }

    function fetchScalars () {
        return array(
            array(),
            array(0),
            array('test'),
        );
    }

    function testFetchArray() {
        $this->assertNull($this->builder->getFetchStyle());
        $this->assertEquals($this->builder, $this->builder->fetchArray());
        $this->assertEquals(\PDO::FETCH_ASSOC, $this->builder->getFetchStyle());
        $this->assertNull($this->builder->getFetchArguments());
    }

    function testFetchList() {
        $this->assertNull($this->builder->getFetchStyle());
        $this->assertEquals($this->builder, $this->builder->fetchList());
        $this->assertEquals(\PDO::FETCH_NUM, $this->builder->getFetchStyle());
        $this->assertNull($this->builder->getFetchArguments());
    }


    private function setupBuilder($query=null, $params=null, array $fetchMode=null) {
        $this->builder->setQuery($query)
                      ->setParams($params);
        if ($fetchMode) {
            call_user_func_array(array($this->builder, 'setFetchMode'), $fetchMode);
        }
    }

    /**
     * @dataProvider builders
     */
    function testBuild($query=null, $params=null, array $fetchMode=null) {
        $this->setupBuilder($query, $params, $fetchMode);
        $this->assertInstanceOf('Prelude\\Database\\Query', $this->builder->build());
    }

    /**
     * @dataProvider builders
     */
    function testExecute($query=null, $params=null, array $fetchMode=null) {
        $this->setupBuilder($query, $params, $fetchMode);
        $this->assertInstanceOf('PDOStatement', $this->builder->execute());
    }

    function builders() {
        return array(
            array('select time()'),
            array('select time()', array()),
            array('select time() where ? > ? and ?', array(1, 2, 3)),
            array('select time() where :a == "b"', array(
                'a' => 'b'
            )),
            array('select time() where ? == 1 and :a == 2 and ? == "b"',
                    array(1, 'a' => 2, 2 => 'b')
            ),
            array('select time()', null, array(\PDO::FETCH_ASSOC)),
            array('select time()', null, array(\PDO::FETCH_INTO, new \StdClass)),
        );
    }

    /**
     * @expectedException \PDOException
     */
    function testBadBuilders() {
        $this->builder->setQuery('bad select from table awful query')
                      ->execute();
    }
}
