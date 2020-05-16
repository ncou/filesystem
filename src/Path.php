<?php

declare(strict_types=1);

namespace Chiron\Boot;

final class Path
{

    /**
     * Normalize a path. This method will do clean() first to replace slashes and remove '..' to create a
     * Clean path. Unlike realpath(), if this path not exists, normalise() will still return this path.
     *
     * @param   string $path The path to normalize.
     * @param   string $ds   Directory separator (optional).
     *
     * @return  string  The normalized path.
     *
     * @since   2.0.4
     * @throws  \UnexpectedValueException If $path is not a string.
     */
    //https://github.com/ventoviro/windwalker-filesystem/blob/master/Path.php#L209
    public static function normalize_TEMP($path, $ds = DIRECTORY_SEPARATOR)
    {
        $parts = [];
        //$path = static::clean($path, $ds);
        $segments = explode($ds, $path);

        foreach ($segments as $segment) {
            if ($segment !== '.') {
                $test = array_pop($parts);

                if (null === $test) {
                    $parts[] = $segment;
                } elseif ($segment === '..') {
                    if ($test === '..') {
                        $parts[] = $test;
                    }

                    if ($test === '..' || $test === '') {
                        $parts[] = $segment;
                    }
                } else {
                    $parts[] = $test;
                    $parts[] = $segment;
                }
            }
        }

        return implode($ds, $parts);
    }

    /**
     * Normalizes ../. and directory separators in path.
     */
    //https://github.com/nette/utils/blob/69c5192b68ec3c7d0ee236bf9c47c4d1a54e7d29/src/Utils/FileSystem.php#L158
    public static function normalizePath2(string $path, $ds = DIRECTORY_SEPARATOR): string
    {
        $parts = $path === '' ? [] : preg_split('~[/\\\\]+~', $path);
        $res = [];

        foreach ($parts as $part) {
            if ($part === '..' && $res && end($res) !== '..' && end($res) !== '') {
                array_pop($res);
            } elseif ($part !== '.') {
                $res[] = $part;
            }
        }

        return $res === [''] ? $ds : rtrim(implode($ds, $res), $ds);
    }


    public static function normalize($path)
    {
        return static::normalizePath3($path, '/');
    }

    /**
     * Normalizes ../. and directory separators in path.
     */
    //https://github.com/nette/utils/blob/69c5192b68ec3c7d0ee236bf9c47c4d1a54e7d29/src/Utils/FileSystem.php#L158
    public static function normalizePath3(string $path, $ds = DIRECTORY_SEPARATOR): string
    {
        $parts = $path === '' ? [] : preg_split('~[/\\\\]+~', $path);
        $res = [];

        foreach ($parts as $part) {
            if ($part === '..' && !empty($res) && end($res) !== '..') {
                array_pop($res);
            } elseif ($part !== '.' && ($part !== '' || empty($res))) {
                $res[] = $part;
            }
        }

        return $res === [''] ? $ds : implode($ds, $res);
    }


    /**
     * Normalize relative directories in a path.
     *
     * @param string $path
     * @param string $ds separator used for directories
     *
     * @throws LogicException
     *
     * @return string
     */
    //https://github.com/thephpleague/flysystem/blob/d13c43dbd4b791f815215959105a008515d1a2e0/src/Util.php#L102
    public static function normalizeRelativePath(string $path, string $ds = DIRECTORY_SEPARATOR): string
    {
        $path = str_replace('\\', '/', $path);
        $path = static::removeFunkyWhiteSpace($path);

        $parts = [];

        foreach (explode('/', $path) as $part) {
            switch ($part) {
                case '':
                case '.':
                break;

            case '..':
                if (empty($parts)) {
                    throw new LogicException(sprintf('Path is outside of the defined root, path: [%s]', $path));
                }
                array_pop($parts);
                break;

            default:
                $parts[] = $part;
                break;
            }
        }

        return implode($ds, $parts);
    }

    /**
     * Removes unprintable characters and invalid unicode characters.
     *
     * @param string $path
     *
     * @return string $path
     */
    // TODO : déplacer ce bout de code dans une classe Utils ????
    private static function removeFunkyWhiteSpace(string $path): string
    {
        // We do this check in a loop, since removing invalid unicode characters
        // can lead to new characters being created.
        while (preg_match('#\p{C}+|^\./#u', $path)) {
            $path = preg_replace('#\p{C}+|^\./#u', '', $path);
        }

        return $path;
    }





    // TODO : passer en paramétre le DIRECTORY_SEPARATOR
    //https://github.com/spiral/files/blob/master/src/Files.php#L384
    public static function relativePath(string $path, string $from): string
    {
        $path = static::normalize($path);
        $from = static::normalize($from);

        $from = explode('/', $from);
        $path = explode('/', $path);
        $relative = $path;

        foreach ($from as $depth => $dir) {
            //Find first non-matching dir
            if ($dir === $path[$depth]) {
                //Ignore this directory
                array_shift($relative);
            } else {
                //Get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    //Add traversals up to first matching directory
                    $padLength = (count($relative) + $remaining - 1) * -1;
                    $relative = array_pad($relative, $padLength, '..');
                    break;
                } else {
                    $relative[0] = './' . $relative[0];
                }
            }
        }

        return implode('/', $relative);
    }


    private static function getRelativePath(UriInterface $base, UriInterface $target)
    {
        $sourceSegments = explode('/', $base->getPath());
        $targetSegments = explode('/', $target->getPath());
        array_pop($sourceSegments);
        $targetLastSegment = array_pop($targetSegments);
        foreach ($sourceSegments as $i => $segment) {
            if (isset($targetSegments[$i]) && $segment === $targetSegments[$i]) {
                unset($sourceSegments[$i], $targetSegments[$i]);
            } else {
                break;
            }
        }
        $targetSegments[] = $targetLastSegment;
        $relativePath = str_repeat('../', count($sourceSegments)) . implode('/', $targetSegments);

        // A reference to am empty last segment or an empty first sub-segment must be prefixed with "./".
        // This also applies to a segment with a colon character (e.g., "file:colon") that cannot be used
        // as the first segment of a relative-path reference, as it would be mistaken for a scheme name.
        if ('' === $relativePath || false !== strpos(explode('/', $relativePath, 2)[0], ':')) {
            $relativePath = "./$relativePath";
        } elseif ('/' === $relativePath[0]) {
            if ($base->getAuthority() != '' && $base->getPath() === '') {
                // In this case an extra slash is added by resolve() automatically. So we must not add one here.
                $relativePath = ".$relativePath";
            } else {
                $relativePath = "./$relativePath";
            }
        }

        return $relativePath;
    }


    /**
     * Returns the target path as relative reference from the base path.
     *
     * Only the URIs path component (no schema, host etc.) is relevant and must be given, starting with a slash.
     * Both paths must be absolute and not contain relative parts.
     * Relative URLs from one resource to another are useful when generating self-contained downloadable document archives.
     * Furthermore, they can be used to reduce the link size in documents.
     *
     * Example target paths, given a base path of "/a/b/c/d":
     * - "/a/b/c/d"     -> ""
     * - "/a/b/c/"      -> "./"
     * - "/a/b/"        -> "../"
     * - "/a/b/c/other" -> "other"
     * - "/a/x/y"       -> "../../x/y"
     *
     * @param string $basePath   The base path
     * @param string $targetPath The target path
     *
     * @return string The relative target path
     */
    //https://github.com/symfony/Routing/blob/master/Generator/UrlGenerator.php#L336
    public static function getRelativePath(string $basePath, string $targetPath)
    {
        if ($basePath === $targetPath) {
            return '';
        }

        $sourceDirs = explode('/', isset($basePath[0]) && '/' === $basePath[0] ? substr($basePath, 1) : $basePath);
        $targetDirs = explode('/', isset($targetPath[0]) && '/' === $targetPath[0] ? substr($targetPath, 1) : $targetPath);
        array_pop($sourceDirs);
        $targetFile = array_pop($targetDirs);

        foreach ($sourceDirs as $i => $dir) {
            if (isset($targetDirs[$i]) && $dir === $targetDirs[$i]) {
                unset($sourceDirs[$i], $targetDirs[$i]);
            } else {
                break;
            }
        }

        $targetDirs[] = $targetFile;
        $path = str_repeat('../', \count($sourceDirs)).implode('/', $targetDirs);

        // A reference to the same base directory or an empty subdirectory must be prefixed with "./".
        // This also applies to a segment with a colon character (e.g., "file:colon") that cannot be used
        // as the first segment of a relative-path reference, as it would be mistaken for a scheme name
        // (see http://tools.ietf.org/html/rfc3986#section-4.2).
        return '' === $path || '/' === $path[0]
            || false !== ($colonPos = strpos($path, ':')) && ($colonPos < ($slashPos = strpos($path, '/')) || false === $slashPos)
            ? "./$path" : $path;
    }

}
