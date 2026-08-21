<?php

if (!function_exists('load_env')) {
    /**
     * Load environment variables from a given file path
     *
     * @param string $filePath
     * @return bool
     */
    function load_env(string $filePath): bool {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }

            // Split into key and value by the first '='
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Strip enclosing quotes (single or double)
            if (
                strlen($value) >= 2 && (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                )
            ) {
                $value = substr($value, 1, -1);
            }

            // Handle special boolean/null representations
            $normalizedValue = match (strtolower($value)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'empty', '(empty)' => '',
                'null', '(null)' => null,
                default => $value,
            };

            // Set in environment, $_ENV, and $_SERVER
            putenv("{$key}={$value}");
            $_ENV[$key] = $normalizedValue;
            $_SERVER[$key] = $normalizedValue;
        }

        return true;
    }
}

if (!function_exists('env')) {
    /**
     * Retrieve an environment variable with a fallback default value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed {
        $val = getenv($key);

        if ($val === false) {
            if (isset($_ENV[$key])) {
                return $_ENV[$key];
            }
            if (isset($_SERVER[$key])) {
                return $_SERVER[$key];
            }
            return $default;
        }

        return match (strtolower($val)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $val,
        };
    }
}


$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envPath)) {
    load_env($envPath);
}
