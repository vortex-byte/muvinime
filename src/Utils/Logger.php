<?php

namespace Muvinime\Utils;

class Logger
{
    const LEVEL_DEBUG   = 0;
    const LEVEL_INFO    = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR   = 3;

    private static array $levelMap = [
        'DEBUG'   => self::LEVEL_DEBUG,
        'INFO'    => self::LEVEL_INFO,
        'WARNING' => self::LEVEL_WARNING,
        'ERROR'   => self::LEVEL_ERROR,
    ];

    public static function debug($message): bool
    {
        return self::write('DEBUG', $message);
    }

    public static function info($message): bool
    {
        return self::write('INFO', $message);
    }

    public static function warning($message): bool
    {
        return self::write('WARNING', $message);
    }

    public static function error($message): bool
    {
        return self::write('ERROR', $message);
    }

    private static function write(string $level, $message): bool
    {
        $enabled = \defined('MVNIME_ENABLE_LOG') ? MVNIME_ENABLE_LOG : get_option('muvi_enable_log');
        if (!$enabled || $enabled == 0) return false;

        $configuredLevel = get_option('muvi_log_level', 'INFO');
        $messageLevel = self::$levelMap[$level] ?? self::LEVEL_INFO;
        $minLevel = self::$levelMap[$configuredLevel] ?? self::LEVEL_INFO;

        if ($messageLevel < $minLevel) return false;

        $date = (new \DateTime())->format("d/m/Y H:i:s");

        if (!\is_string($message)) $message = json_encode($message);
        $txt = "[$date] [$level] $message";

        try {
            $logPath = \defined('MVNIME_PATH') ? MVNIME_PATH : WP_PLUGIN_DIR . '/muvinime/';
            file_put_contents($logPath . 'app.log', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
