<?php

namespace Prelude\Database;

use Prelude\Arrays;

class QueryTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \PDO
     */
    private $pdo;

    function setUp() {
        $this->pdo = new \PDO('sqlite::memory:');
    }

    /**
     * @dataProvider bindParams
     */
    function testBindParamCtor($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $stmt->setFetchMode(\PDO::FETCH_COLUMN, 0);

        $query = new Query($stmt, $params);
        $this->assertEquals($result, iterator_to_array($query->execute()));

        $this->assertSame($stmt, $query->getStatement());
    }

    /**
     * @dataProvider bindParams
     */
    function testBindParams($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $stmt->setFetchMode(\PDO::FETCH_COLUMN, 0);

        $query = new Query($stmt);
        $query->bindParams($params);
        $this->assertEquals($result, iterator_to_array($query->execute()));
        $this->assertSame($stmt, $query->getStatement());
    }

    /**
     * @dataProvider bindParams
     */
    function testBindParamExecute($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $stmt->setFetchMode(\PDO::FETCH_COLUMN, 0);

        $query = new Query($stmt);
        $this->assertEquals($result, iterator_to_array($query->execute($params)));
        $this->assertSame($stmt, $query->getStatement());
    }

    /**
     * @dataProvider bindParams
     */
    function testFetch($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt, $params);
        $query->getStatement()->setFetchMode(\PDO::FETCH_COLUMN, 0);

        foreach ($result as $object) {
            $this->assertEquals($object, $query->fetch());
        }

        $this->assertFalse($query->fetch());
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchAll($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt, $params);
        $this->assertEquals($result, $query->fetchAll(\PDO::FETCH_COLUMN, 0));
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchObject($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt, $params);
        $this->assertInternalType('object', $query->fetchObject());
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchInto($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt, $params);
        $object = new \stdClass();
        $this->assertSame($object, $query->fetchInto($object));
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchClass($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt, $params);
        $this->assertInternalType('object', $query->fetchObject('stdclass'));
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchArray($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt, $params);
        $this->assertInternalType('array', $fetched = $query->fetchArray());
        $this->assertCount(count($result), Arrays::extractMap($fetched));
        $this->assertNull(Arrays::extractList($fetched));
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchArrayWithParams($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt, $params);
        $this->assertInternalType('array', $fetched = $query->fetchArray(\PDO::FETCH_LAZY));
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchList($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt, $params);
        $this->assertInternalType('array', $fetched = $query->fetchList());
        $this->assertCount(count($result), Arrays::extractList($fetched));
        $this->assertNull(Arrays::extractMap($fetched));
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchListWithParams($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt, $params);
        $this->assertInternalType('array', $fetched = $query->fetchList(\PDO::FETCH_LAZY));
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchScalar($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt);
        $query->bindParams($params);
        $this->assertEquals($result[0], $query->fetchScalar());
    }

    /**
     * @dataProvider bindParams
     */
    function testFetchScalarIndexed($query, $result, $params) {
        $stmt = $this->pdo->prepare($query);
        $query = new Query($stmt);
        $query->bindParams($params);
        $this->assertEquals($result[0], $query->fetchScalar(0));
    }

    function bindParams() {
        return array(
            array('select "foo"', array('foo') , array()),
            array('select ?'    , array('test'), array('test')),
            array('select :p'   , array('test'), array('p' => 'test')),
            array('select :p'   , array(0)     , array('p' => false)),
            array('select :p'   , array(file_get_contents(__FILE__)), array('p' => fopen(__FILE__, 'r'))),
        );
    }

    /**
     * @expectedException \PDOException
     */
    function testFailExecuteThrows() {

        $stmt = $this->getMock('PDOStatement');

        $stmt->expects($this->once())
             ->method('execute')
             ->will($this->returnValue(false));

        $stmt->expects($this->any())
             ->method('errorInfo')
             ->will($this->returnValue(array()));

        $query = new Query($stmt);
        $query->execute();
    }

    /**
     * @dataProvider fetchers
     */
    function testFetchExecutes($fetch, $result) {

        $stmt = $this->getMock('PDOStatement');

        $stmt->expects($this->once())
             ->method('execute')
             ->will($this->returnValue(true));

         $stmt->expects($this->once())
              ->method($fetch)
              ->will($this->returnValue($result));

        $query = new Query($stmt);
        $this->assertEquals($result, $query->{$fetch}());
    }

    /**
     * @dataProvider fetchers
     */
    function testFetchNeverExecutes($fetch, $result) {
        $stmt = $this->getMock('PDOStatement');

        $stmt->expects($this->never())
             ->method('execute')
             ->will($this->returnValue(true));

         $stmt->expects($this->any())
             ->method('columnCount')
             ->will($this->returnValue(1));

         $stmt->expects($this->once())
              ->method($fetch)
              ->will($this->returnValue($result));

        $query = new Query($stmt);
        $this->assertEquals($result, $query->{$fetch}());
    }

    /**
     * @dataProvider fetchers
     */
    function testFetchExecutesOnce($fetch, $result) {
        $stmt = $this->getMock('PDOStatement');

        $stmt->expects($this->once())
             ->method('execute')
             ->will($this->returnValue(true));

         $stmt->expects($this->any())
             ->method('columnCount')
             ->will($this->returnValue(1));

         $stmt->expects($this->once())
              ->method($fetch)
              ->will($this->returnValue($result));

        $query = new Query($stmt);
        $query->execute();
        $this->assertEquals($result, $query->{$fetch}());
    }

    /**
     * provider
     */
    function fetchers() {
        return array(
            array('fetch', 12.34),
            array('fetch', array('value')),
            array('fetch', array('col' => 'value')),

            array('fetchAll', array(12.34)),
            array('fetchObject', $this),
            array('fetchObject', (object) array('a' => 12)),
        );
    }
}
