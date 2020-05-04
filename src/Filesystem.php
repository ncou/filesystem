<?php

declare(strict_types=1);

namespace Chiron\Boot;

use Chiron\Boot\Exception\FileNotFoundException;

use EmptyIterator;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Iterator;
use Traversable;
use CallbackFilterIterator;
use SplFileInfo;
use RegexIterator;
use RecursiveRegexIterator;

//https://github.com/opulencephp/Opulence/blob/1.1/src/Opulence/IO/FileSystem.php

//https://github.com/ventoviro/windwalker-filesystem/blob/master/Filesystem.php
//https://github.com/ventoviro/windwalker-filesystem/blob/master/Path.php

//https://github.com/cakephp/filesystem/blob/master/Folder.php
//https://github.com/paamayim/PHP-Filesystem-Helper/blob/master/src/FilesystemHelper/FilesystemHelper.php#L20
//https://github.com/owncloud/core/blob/master/lib/private/Files/Filesystem.php


//https://github.com/illuminate/filesystem/blob/master/Filesystem.php
//https://github.com/spiral/files/blob/master/src/Files.php
//https://github.com/nette/utils/blob/master/src/Utils/FileSystem.php
//https://github.com/composer/composer/blob/2285a79c6302576dec07c9bb8b52d24e6b4e8015/src/Composer/Util/Filesystem.php#L283

//https://github.com/naucon/File/blob/master/src/

// TODO : exemple de purge d'un répertoire :
//https://github.com/contributte/console-extra/blob/master/src/Utils/Files.php#L16

// TODO : renommer la classe en FileSystem avec un S majuscule !!!!
// TODO : créer une interface FilesystemInterface ?????
final class Filesystem
{
    /**
     * Determine if the given path is a file.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function isFile(string $filename): bool
    {
        return is_file($filename);
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function isDirectory(string $filename): bool
    {
        return is_dir($filename);
    }

    public function exists(string $filename): bool
    {
        return file_exists($filename);
    }

    /**
     * Determine if a file or directory is missing.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function missing(string $filename): bool
    {
        return ! $this->exists($filename);
    }

    public function read(string $filename): string
    {
        if ($this->isFile($filename)) {
            return file_get_contents($filename);
        }

        throw new FileNotFoundException($filename);
    }

    public function md5(string $filename): string
    {
        if ($this->isFile($filename)) {
            return md5_file($filename);
        }

        throw new FileNotFoundException($filename);
    }

    public function sha1(string $filename): string
    {
        if ($this->isFile($filename)) {
            return sha1_file($filename);
        }

        throw new FileNotFoundException($filename);
    }

    // TODO : améliorer la copie en utilisant cette fonction qui supporte la copie de répertoires : https://github.com/composer/composer/blob/2285a79c6302576dec07c9bb8b52d24e6b4e8015/src/Composer/Util/Filesystem.php#L283
    public function copy(string $filename, string $destination): bool
    {
        if ($this->exists($filename)) {
            return copy($filename, $destination);
        }

        throw new FileNotFoundException($filename);
    }

    /**
     * Extract the file name from a file path.
     *
     * @param string $filename
     *
     * @return string
     */
    public function name(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     *
     * @param string $filename
     *
     * @return string
     */
    public function basename(string $filename): string
    {
        return pathinfo($filename, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     *
     * @param string $filename
     *
     * @return string
     */
    public function dirname(string $filename): string
    {
        return pathinfo($filename, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     *
     * @param string $filename
     *
     * @return string
     */
    public function extension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * @param string $path
     * @param string $content
     */
    public function write(string $path, string $content): void
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    /**
     * Get the returned value of a file.
     *
     * @param string $filename
     *
     * @throws FileNotFoundException
     *
     * @return mixed
     */
    public function getRequire(string $filename)
    {
        if ($this->isFile($filename)) {
            return require $filename;
        }

        throw new FileNotFoundException();
    }

    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @return bool
     */
    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false): bool
    {
        return mkdir($path, $mode, $recursive);
    }

    /**
     * Joins all given path segments then normalizes the resulting path.
     */
    public function joinPaths(string ...$paths): string
    {
        return $this->normalizePath(implode('/', $paths));
    }

    /**
     * Normalize a path. This replaces backslashes with slashes, removes ending
     * slash and collapses redundant separators and up-level references.
     *
     * @param  string $path Path to the file or directory
     * @return string
     */
    public function normalizePath(string $path): string
    {
        $parts = array();
        $path = strtr($path, '\\', '/');
        $prefix = '';
        $absolute = false;

        // extract a prefix being a protocol://, protocol:, protocol://drive: or simply drive:
        if (preg_match('{^( [0-9a-z]{2,}+: (?: // (?: [a-z]: )? )? | [a-z]: )}ix', $path, $match)) {
            $prefix = $match[1];
            $path = substr($path, strlen($prefix));
        }

        if (substr($path, 0, 1) === '/') {
            $absolute = true;
            $path = substr($path, 1);
        }

        $up = false;
        foreach (explode('/', $path) as $chunk) {
            if ('..' === $chunk && ($absolute || $up)) {
                array_pop($parts);
                $up = !(empty($parts) || '..' === end($parts));
            } elseif ('.' !== $chunk && '' !== $chunk) {
                $parts[] = $chunk;
                $up = '..' !== $chunk;
            }
        }

        return $prefix.($absolute ? '/' : '').implode('/', $parts);
    }

    /**
     * Normalizes ../. and directory separators in path.
     */
    public static function normalizePath2(string $path): string
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

        return $res === ['']
            ? DIRECTORY_SEPARATOR
            : implode(DIRECTORY_SEPARATOR, $res);
    }

    


    /**
     * Compares two files and returns true if their contents is equal.
     *
     * @param $file1
     * @param $file2
     * @return bool
     */
    /*
    public static function filesHaveSameContents($file1, $file2): bool
    {
        return sha1_file($file1) === sha1_file($file2);
    }*/

    /**
     * Get an array of all files in a given directory.
     *
     * @param string $directory
     * @param bool   $recursive    Whether or not we should recurse through child directories
     *
     * @return Iterator<string, SplFileInfo>
     */
    public function files(string $directory, bool $recursive = true): Traversable
    {
        $iterator = $this->buildIterator($directory, $recursive);

        $files = new CallbackFilterIterator($iterator, function (SplFileInfo $item) {
            return $item->isFile();
        });

        return $files;
    }

    /**
     * Get an array of all directories in a given directory.
     *
     * @param string $directory
     * @param bool   $recursive    Whether or not we should recurse through child directories
     *
     * @return Iterator<string, SplFileInfo>
     */
    public function directories(string $directory, bool $recursive = true): Traversable
    {
        $iterator = $this->buildIterator($directory, $recursive);

        $directories = new CallbackFilterIterator($iterator, function (SplFileInfo $item) {
            return $item->isDir();
        });

        return $directories;
    }

    public function find(string $directory, string $mask, bool $recursive = true): Traversable
    {
        $regex = $this->toRegEx($mask);
        $iterator = $this->buildIterator($directory, $recursive);

        $finder = new CallbackFilterIterator($iterator, function (SplFileInfo $item) use ($regex, $iterator) {
            return $regex === null || preg_match($regex, '/' . strtr($iterator->getSubPathName(), '\\', '/'));
        });

        return $finder;
    }

    // TODO : tester quand le mask est vie ou seulement à '*'
    private function toRegEx(string $mask): ?string
    {
        $mask = rtrim(strtr($mask, '\\', '/'), '/');
        $prefix = '';

        if ($mask === '') {
            // TODO : on devrait plutot lever une exception pour indiquer que le mask est incorrect !!!
            return null;
        } elseif ($mask === '*') {
            return null;
        } elseif ($mask[0] === '/') { // absolute fixing
            $mask = ltrim($mask, '/');
            $prefix = '(?<=^/)';
        }

        $pattern = $prefix . strtr(preg_quote($mask, '#'),
            ['\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]', '\[\!' => '[^', '\[' => '[', '\]' => ']', '\-' => '-']);

        return '#/(' . $pattern . ')$#Di';
    }

    /**
     * Gets all of the files or directories at the input path.
     *
     * @param string $directory
     * @param bool   $recursive    Whether or not we should recurse through child directories
     *
     * @return Iterator<string, \SplFileInfo>
     */
    private function buildIterator(string $directory, bool $recursive): Iterator 
    {
        if (! $this->isDirectory($directory)) {
            return new EmptyIterator();
        }

        $flags = FilesystemIterator::FOLLOW_SYMLINKS | FilesystemIterator::SKIP_DOTS;
        $iterator = new RecursiveDirectoryIterator($directory, $flags);

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
        }

        return $iterator;
    }
}
