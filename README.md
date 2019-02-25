# Database-Agnostic Abstractions [![Build Status](https://travis-ci.org/eridal/prelude-database.png?branch=master)](https://travis-ci.org/eridal/prelude-database)

This library provides a simplified interface to common PDO idioms.
This is not a SQL generation tool, actually it's expected from you to provide..
 - the sql query string
 - query parameters


## Install
composer.json:
```json
{
    "require": {
        "prelude/prelude-database": "*"
    }
}
```
See [packagist](https://packagist.org/packages/prelude/prelude-database) for detailed information.

## Dsn
PDO provides a nice API for accesing database in a standard way, but 
the connection part is still handled using strings; and those are _vendor-specific_.

Dsn provide a simple standard to handle such differences by providing a consistent API
to read the configuration, and then giving you the connected PDO object. 

It simply **stays out of your way** while integrating _nice with others_.

#### Reading Configuration
```php
# read from url
$dsn = DsnParser::parseUrl('pgsql://user:pass@host:port/database');
# .. or from the enviroment
$dsn = DsnParser::parseEnv('DATABASE_URL');
# .. or from file
$dsn = DsnParser::parseFile('path/to/config/db.php'); // support reading urls or arrays

# .. which under the hood all it does is:
$dsn = new Dsn([
    'driver' => Dsn::MYSQL,
      'host' => 'locahost',
    'dbName' => 'app-db'
]);
```

#### Database connection
To open a connection to the database just call `$dsn->connect()`. It will return a `PDO` instance.
```php
$pdo = $dsn->connect();
// .. which actually does
$pdo = new PDO($dsn->toString(), $dsn->user, $dsn->pass);
```
Optionally you can pass an array of drivers parameters for PDO.
```php
// with parameters:
$pdo = $dsn->connect([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
```

## Query Builder
A fluent interface to constructing `Query` instances.

The query builder handles the inner and gotchas of working with prepared statements.
It requires that **you provide the full sql-query string** and, in return, it will provide you
the `PDOStatement` fully configured, ready to execute or to fetch the results.

```php
$builder = new QueryBuilder($pdo);
$builder->setQuery('SELECT * FROM FizzBuzz where :foo > ? and :bar == :baz')
        ->setParam('foo', $foo)
        ->setParam(0, $p0)
        ->setParams(['bar' => $bar,
                     'baz' => $baz])
        ->fetchObject(FizzBuzz::CLASS);

try {
    // execute() returns a PDOStatement -- or throws
    foreach ($builder->execute() as $row) {
        // $row instanceof FizzBuzz;
    }
} catch (\PDOException $e) {
    var_dump($e); // execute() failed
}

// Need fine tune? just access the internal PDO instance!
$pdoStmt = $builder->getStatement();
```

#### Sql Query
```php
$builder->setQuery('SELECT * FROM table');
echo $builder->getQuery(); // outputs " SELECT *... "
```

#### Params and Arguments
```php
$builder->setQuery('SELECT * FROM table WHERE :foo > ?');

$builder->setParam('foo', $foo); // sets the `:foo` param
$builder->setParam(0, $zero);    // sets the first `?` argument

// or simply pass them all
$builder->setParams([$zero, 'foo' => $foo]);

// need the values back?
$builder->getParams();
$builder->getParam(string|int);

// want to clear them?
$builder->setParams(null);
```
> The are some gotchas when binding values to PDOStatements (like binding _falsy_ values).
> The QueryBuilder will delay handling these until it's required to build the real query;
> thus your value remain unmodified during building process.
> *You should not need to take special care of these edge-cases, and bind safely the values*

#### Fetch Modes
The QueryBuilder provides a simpler approach to fetch:
- `fetchObject([string $class = null [, array $ctoArgs = null]])`
    fetch the result as an object.
    Additionally you can pass the class name, and it's constructor arguments.
- `fetchArray(void)`
    fetch the result as an associative array
- `fetchList(void)`
    fetch the result as an 0-index positional array
- `fetchScalar(int|string $column = 0)`
    fetch the scalar value of the given column

Need fine tune? `setFetchMode(int $mode[, $arg1[, $arg2, ...]])`
Where `$mode` is one of the `\PDO::FETCH_*` contants.

**Example:**
```php
    $builder->fetchObject();
    // --> each record will be a `StdObject`
    $builder->fetchObject(User::CLASS, [$foo, $bar])
    // --> will call new User($foo, $bar) for each result

    $mode = $builder->getFetchStyle(); // \PDO::FETCH_CLASS;
```

## Query(PDOStatement $stmt[, array $params])
The `Query` acts as a small wrapper to enhance `PDOStatement`
You will probably not need to interact with these instances directly, except
cases that requires fine-tune control -- like running multiple times the same query.

> Although the library's idea is to construct `Query` instances using
> the `QueryBuilder::build`, nothing prevents you from manually creating
> instances as required. Its API was designed to **play nice with others**.

This class provides very basic functionality:
 - `bindParam(array $param, $value)`
    to bind a parameter to the internal _PDOStatement_
 - `bindParams(array $params)`
    to bind a group of parameters
 - `execute([array $params = null])`
    optionally bind the parameters, and the execute the _PDOStatement_

```php
$query = new Query(
    $pdo->prepare('INSERT INTO pos(x, y) VALUES(:x, :y)')
);

$query->execute(['x' => 0, 'y' => 0]);
$query->execute(['x' => 1, 'y' => 1]);
$query->execute(['x' => 1, 'y' => 2]);
...
$query->execute(['x' => 9, 'y' => 9]);
```

#### Fetching records
A central design idea of this library is to stay lean, that's why `Query::execute`
will return the `PDOStatment` for you to decide how to fetch the records.

Want to fetch the results as a on-demand, lazy, efficient iterator?
[Prelude\Iterators\Records](https://github.com/eridal/prelude-iterators/#recordspdostatement-stmt-fetchstyle) will to that trick.


### Feedback?
Please give it a try, and let me know!
