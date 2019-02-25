<?php

namespace Prelude\Database;

use Prelude\Arrays;
use Prelude\Urls;

class DsnParser {

    /**
     * @param string $file
     * @return \Prelude\Database\Dsn
     *
     * @throws \Prelude\Database\DsnException
     */
    static function parseFile($file) {

        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            $value = require $file;
        } else {
            throw new DsnException("missing file `$file`");
        }

        return self::parse($value);
    }

    /**
     * @param string $key
     * @return \Prelude\Database\Dsn
     *
     * @throws \Prelude\Database\DsnException
     */
    static function parseEnv($key) {
        if ($value = Arrays::get($_ENV, $key, getenv($key))) {
            return self::parse($value);
        }
        throw new DsnException("missing environment key: `$key`");
    }

    /**
     * @param string $url
     * @return \Prelude\Database\Dsn
     *
     * @throws \Prelude\Database\DsnException
     */
    static function parseUrl($url) {

        $parsed = Urls::parse($url);

        $config = array(
            'driver' => Arrays::getOrThrow($parsed, 'scheme'),
            'host'   => Arrays::getOrThrow($parsed, 'host'),
            'dbname' => ltrim(Arrays::get($parsed, 'path', ''), '/'),
        );

        $fields = array(
            'host',
            'user',
            'pass',
        );

        forEach ($fields as $key => $field) {
            if (is_int($key)) {
                $key = $field;
            }
            if (array_key_exists($key, $parsed)) {
                $config[$field] = $parsed[$key];
            }
        }

        return self::parseArray($config);
    }

    /**
     * @param array $config
     * @return \Prelude\Database\Dsn
     *
     * @throws \Prelude\Database\DsnException
     */
    static function parseArray(array $config) {
        static $DRIVER_ALIAS = array(
            'postgres' => 'pgsql',
          'postgresql' => 'pgsql'
        );

        $scheme = Arrays::getOrThrow($config, 'driver', new DsnException("driver is not optional"));
        # translate driver alias
        $config['driver'] = Arrays::get($DRIVER_ALIAS, $scheme, $scheme);
        return new Dsn($config);
    }

    /**
     * @param string|array $value
     * @return \Prelude\Database\Dsn
     *
     * @throws \Prelude\Database\DsnException
     */
    private static function parse($value) {

        if (is_string($value)) {
            return self::parseUrl($value);
        }

        if (is_array($value)) {
            return self::parseArray($value);
        }

        throw new DsnException("unable to parse: unknown type " . gettype($value));
    }
}


