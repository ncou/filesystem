<?php

declare(strict_types=1);

namespace Chiron\Filesystem;

//https://github.com/thephpleague/flysystem/blob/d13c43dbd4b791f815215959105a008515d1a2e0/src/Util.php

final class Util
{
    /**
     * @return bool Whether the host machine is running a Windows OS
     */
    // TODO : créer une function.php "is_windows()" pour permettre d'utiliser le code ci dessous de maniére autonome !!!!
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



    /**
     * Returns the last occurred PHP error or an empty string if no error occurred. Unlike error_get_last(),
     * it is nit affected by the PHP directive html_errors and always returns text, not HTML.
     */
    //https://github.com/nette/utils/blob/3a8845d662985b202594ae62b93e0f0c2dc95154/src/Utils/Helpers.php#L35
    public static function getLastError(): string
    {
        $message = error_get_last()['message'] ?? '';
        $message = ini_get('html_errors') ? static::htmlToText($message) : $message;
        $message = preg_replace('#^\w+\(.*?\): #', '', $message);
        return $message;
    }

    /**
     * Converts given HTML code to plain text.
     */
    //https://github.com/nette/utils/blob/3a8845d662985b202594ae62b93e0f0c2dc95154/src/Utils/Html.php#L327
    public static function htmlToText(string $html): string
    {
        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }


}
