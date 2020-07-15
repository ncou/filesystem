<?php

declare(strict_types=1);

namespace Chiron\Filesystem;

//https://github.com/thephpleague/flysystem/blob/d13c43dbd4b791f815215959105a008515d1a2e0/src/Util.php

final class Util
{
    /**
     * @return bool Whether the host machine is running a Windows OS
     */
    public static function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }









    /**
     * Nice formatting for computer sizes (Bytes).
     *
     * @param integer|float $bytes    The number in bytes to format
     * @param integer       $decimals The number of decimal points to include
     * @return  string
     */
    // TODO : fonction à renommer en formatMemory() ou formatSize() ???
    public static function format($bytes, $decimals = 2): string
    {
        $exp = 0;
        $value = 0;
        $symbol = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $bytes = (float)$bytes;

        if ($bytes > 0) {
            $exp = floor(log($bytes) / log(1024));
            $value = ($bytes / (1024 ** floor($exp)));
        }

        if ($symbol[$exp] === 'B') {
            $decimals = 0;
        }

        return number_format($value, $decimals, '.', '') . ' ' . $symbol[$exp];
    }

    public static function formatMemory(int $memory)
    {
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GiB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MiB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KiB', $memory / 1024);
        }

        return sprintf('%d B', $memory);
    }


    /**
     * Describes memory usage in real-world units. Intended for use
     * with memory_get_usage, etc.
     *
     * @param $bytes
     *
     * @return string
     */
    public static function describeMemory(int $bytes): string
    {
        if ($bytes < 1024)
        {
            return $bytes . 'B';
        }
        else if ($bytes < 1048576)
        {
            return round($bytes / 1024, 2) . 'KB';
        }

        return round($bytes / 1048576, 2) . 'MB';
    }


}
