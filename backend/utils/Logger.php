<?php

/**
 * Writes lines to backend/logs/info.log and backend/logs/error.log.
 *
 * Example lines:
 *   [2026-11-22 14:05:12] INFO Open Library lookup ISBN 9780451524935: found (231 ms)
 *   [2026-11-22 14:05:13] ERROR Open Library lookup ISBN 9780451524935 failed: timeout
 *
 * Milestone 3 uses it for the external API; Milestone 4 adds request logging middleware.
 */
class Logger
{
    private const LOG_DIR = __DIR__ . '/../logs';

    public static function info(string $message): void
    {
        self::write('info.log', 'INFO', $message);
    }

    public static function error(string $message): void
    {
        self::write('error.log', 'ERROR', $message);
    }

    private static function write(string $file, string $level, string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . "] $level $message" . PHP_EOL;
        // LOCK_EX stops two requests from writing into the same line at the same time.
        // The @ hides a PHP warning if the logs folder is not writable; logging must never break the API.
        @file_put_contents(self::LOG_DIR . '/' . $file, $line, FILE_APPEND | LOCK_EX);
    }
}
