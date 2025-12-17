<?php

namespace Callie;

class Env {
    protected static $loaded = false;
    protected static $data = [];

    public static function load($path) {
        if (self::$loaded) return;

        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remove quotes if present
            if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            }

            self::$data[$name] = $value;
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }

        self::$loaded = true;
    }

    public static function get($key, $default = null) {
        return self::$data[$key] ?? getenv($key) ?? $default;
    }
}
