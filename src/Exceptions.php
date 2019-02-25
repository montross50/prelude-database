<?php

namespace Prelude\Database;

use Prelude\Arrays;

final class Exceptions {

    /**
     *
     * @link http://php.net/pdo.errorinfo
     *
     * @param array $errorInfo
     * @throws \PDOException
     * @return \PDOException
     */
    private static function fromInfo(array $errorInfo) {
        throw new \PDOException(
            Arrays::get($errorInfo, 2),
            Arrays::get($errorInfo, 1)
        );
    }

    /**
     * @param \PDO $pdo
     * @return \PDOException nothing.. it will just throw an exception
     * @throws \PDOException
     */
    static function fromConnection(\PDO $pdo) {
        throw self::fromInfo($pdo->errorInfo());
    }

    /**
     * @param \PDOStatement $stmt
     *
     * @return \PDOException nothing.. it will throw an exception
     * @throws \PDOException
     *
     * @link http://php.net/pdo.errorinfo
     */
    static function fromStatement(\PDOStatement $stmt) {
        throw self::fromInfo($stmt->errorInfo());
    }
}
