<?php

declare(strict_types=1);

namespace Chiron\Filesystem;

use Chiron\Filesystem\Exception\FileNotFoundException;
use Chiron\Filesystem\Exception\FilesystemException;

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
use LogicException;

//https://github.com/webmozart/path-util/blob/master/src/Path.php

//https://github.com/codeigniter4/CodeIgniter4/blob/c3e545d9f2a25c61031a65ff6ed25466f1a6a278/system/Helpers/filesystem_helper.php

//https://github.com/JBZoo/Utils/blob/5a2b7c01f48318585212fa9876c8c48c8817d974/src/FS.php

//https://github.com/webmozart/path-util/blob/master/src/Path.php

//https://github.com/yiisoft/files/blob/ee71f385bd1cb31d8cd40d6752efc15c66403cac/src/FileHelper.php

//https://github.com/opulencephp/Opulence/blob/1.1/src/Opulence/IO/FileSystem.php

//https://github.com/ventoviro/windwalker-filesystem/blob/master/Filesystem.php
//https://github.com/ventoviro/windwalker-filesystem/blob/master/Path.php


//https://github.com/thephpleague/flysystem/blob/d13c43dbd4b791f815215959105a008515d1a2e0/src/Filesystem.php
//https://github.com/thephpleague/flysystem/blob/d13c43dbd4b791f815215959105a008515d1a2e0/src/File.php
//https://github.com/thephpleague/flysystem/blob/d13c43dbd4b791f815215959105a008515d1a2e0/src/Directory.php

//https://github.com/cakephp/filesystem/blob/master/Filesystem.php
//https://github.com/cakephp/filesystem/blob/master/Folder.php
//https://github.com/cakephp/filesystem/blob/master/File.php


//https://github.com/paamayim/PHP-Filesystem-Helper/blob/master/src/FilesystemHelper/FilesystemHelper.php#L20
//https://github.com/owncloud/core/blob/master/lib/private/Files/Filesystem.php


//https://github.com/illuminate/filesystem/blob/master/Filesystem.php
//https://github.com/spiral/files/blob/master/src/Files.php
//https://github.com/nette/utils/blob/master/src/Utils/FileSystem.php
//https://github.com/composer/composer/blob/2285a79c6302576dec07c9bb8b52d24e6b4e8015/src/Composer/Util/Filesystem.php#L283

//https://github.com/naucon/File/blob/master/src/

// TODO : exemple de purge d'un répertoire :
//https://github.com/contributte/console-extra/blob/master/src/Utils/Files.php#L16

final class Filesystem
{
    /**
     * Determine if the given path is a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function isFile(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param string $path
     *
     * @return bool
     */
    public function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Determine if a file or directory exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Determine if a file or directory is missing.
     *
     * @param string $path
     *
     * @return bool
     */
    public function missing(string $path): bool
    {
        return $this->exists($path) === false;
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
    /*
    public function copy(string $filename, string $destination): bool
    {
        if ($this->exists($filename)) {
            return copy($filename, $destination);
        }

        throw new FileNotFoundException($filename);
    }*/

    /**
     * Copies a file or directory from $source to $target.
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public function copy(string $source, string $target): bool
    {
        if (!is_dir($source)) {
            return copy($source, $target);
        }

        // TODO : utiliser la méthode createIterator pour mutualiser le code ????
        $it = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);
        $this->ensureDirectoryExists($target);

        $result = true;
        foreach ($ri as $file) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $ri->getSubPathName();
            if ($file->isDir()) {
                $this->ensureDirectoryExists($targetPath);
            } else {
                $result = $result && copy($file->getPathname(), $targetPath);
            }
        }

        return $result;
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
     * Write the contents of a file.
     *
     * @param  string  $filename
     * @param  string  $content
     * @param  bool  $lock
     * @return int|bool
     */
    // TODO : voir si on conserve cette fonction !!! et lui ajouter des tests !!!   
    //https://github.com/illuminate/filesystem/blob/master/Filesystem.php#L155
    //https://github.com/cakephp/filesystem/blob/master/File.php#L229
    //https://github.com/spiral/framework/blob/2.8/src/Files/src/Files.php#L113
    //https://github.com/nette/utils/blob/master/src/Utils/FileSystem.php#L168
    public function write(string $filename, string $content, bool $lock = false)
    {
        return file_put_contents($filename, $content, $lock ? LOCK_EX : 0);
    }

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * @param string $path
     * @param string $content
     */
    /*
    public function write_SAVE(string $path, string $content): void
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }*/


    /**
     * Atomically write content into a file.
     *
     * @param string $content The data to write into the file
     *
     * @throws FilesystemException if the file cannot be written to
     */
    // TODO : créer des tests en utilisant comme exemple les tests : https://github.com/symfony/filesystem/blob/master/Filesystem.php#L641 + https://github.com/symfony/filesystem/blob/e7550993849f986f01a9161b302d4aed8d4aab0a/Tests/FilesystemTest.php#L1541
    public function replace(string $filename, string $content): void
    {
        $dir = dirname($filename);

        if (! is_dir($dir)) {
            $this->makeDirectory($dir);
        }

        if (! is_writable($dir)) {
            throw new FilesystemException(sprintf('Unable to write to the "%s" directory.', $dir));
        }

        // Will create a temp file with 0600 access rights when the filesystem supports chmod.
        $tmpFile = $this->tempnam($dir, basename($filename));

        if (false === @file_put_contents($tmpFile, $content)) {
            throw new FilesystemException(sprintf('Failed to write file "%s".', $filename));
        }

        @chmod($tmpFile, file_exists($filename) ? fileperms($filename) : 0666 & ~umask());

        // rename temporary file and will overwrite existing file.
        $this->rename($tmpFile, $filename, true);
    }



    /**
     * Creates a temporary file.
     *
     * @param string $prefix The prefix of the generated temporary filename
     *                       Note: Windows uses only the first three characters of prefix
     * @param string $suffix The suffix of the generated temporary filename
     *
     * @throws FilesystemException if the tempory file cannot be created
     *
     * @return string The new temporary filename (with path), or throw an exception on failure
     */
    public function tempnam(string $dir, string $prefix): string
    {
        $tmpFile = @tempnam($dir, $prefix);

        // If tempnam failed or no scheme return the filename otherwise prepend the scheme
        if ($tmpFile === false) {
            throw new FilesystemException('A temporary file could not be created.');
        }

        return $tmpFile;
    }

    /**
     * Renames a file or a directory.
     *
     * @throws FilesystemException When target file or directory already exists
     * @throws FilesystemException When origin cannot be renamed
     */
    // TODO : renommer en renameFile()
    public function rename(string $origin, string $target, bool $overwrite = false): void
    {

        // TODO : faire une vérification si le $origin est un répertoire (is_dir) il faut lever une exception car la fonction rename fonctionne uniquement pour les fichiers et pas les dossiers !!!!

        // TODO : on devrait pas plutot effectuer un test sur exist() ???? au lieu de isReadeable ????
        // we check that target does not exist
        //if (! $overwrite && $this->isReadable($target)) {
        if (! $overwrite && is_readable($target)) {
            throw new FilesystemException(sprintf('Cannot rename because the target "%s" already exists.', $target));
        }

        if (@rename($origin, $target) === false) {
            /*
            if (is_dir($origin)) {
                // See https://bugs.php.net/54097 & https://php.net/rename#113943
                $this->mirror($origin, $target, null, ['override' => $overwrite, 'delete' => $overwrite]);
                $this->remove($origin);

                return;
            }
            */

            throw new FilesystemException(sprintf('Cannot rename "%s" to "%s".', $origin, $target));
        }
    }

    /**
     * Tells whether a file exists and is readable.
     */
    // TODO : il faudrait pas vérifier qu'on passe bien en paramétre un fichier ????
    // TODO : voir si on conserve cette fonction !!!!
    /*
    public function isReadable(string $filename): bool
    {
        return is_readable($filename);
    }*/

    /**
     * Tells whether a file exists and is writable.
     */
    // TODO : il faudrait pas vérifier qu'on passe bien en paramétre un fichier ????
    // TODO : voir si on conserve cette fonction !!!!
    /*
    public function isWritable(string $filename): bool
    {
        return is_writable($filename);
    }*/







    






    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * Uses a CHILD_FIRST RecursiveIteratorIterator to sort files
     * before directories, creating a single non-recursive loop
     * to delete files/directories in the correct order.
     *
     * @param  string  $directory
     * @param  bool  $preserve
     * @return bool
     */
    //https://github.com/yiisoft/files/blob/ee71f385bd1cb31d8cd40d6752efc15c66403cac/src/FileHelper.php#L153 + TESTS Symlink !!!!!     https://github.com/yiisoft/files/blob/ee71f385bd1cb31d8cd40d6752efc15c66403cac/tests/FileHelperTest.php#L84
    //https://github.com/illuminate/filesystem/blob/master/Filesystem.php#L610
    //https://github.com/spiral/files/blob/master/src/Files.php#L176
    //https://github.com/composer/composer/blob/2285a79c6302576dec07c9bb8b52d24e6b4e8015/src/Composer/Util/Filesystem.php#L150
    //https://github.com/nette/utils/blob/master/src/Utils/FileSystem.php#L72
    //https://github.com/ventoviro/windwalker-filesystem/blob/master/Folder.php#L180
    //https://github.com/composer/composer/blob/78b8c365cd879ce29016884360d4e61350f0d176/tests/Composer/Test/Util/FilesystemTest.php#L230
    public function deleteDirectory(string $directory, bool $preserve = false): bool
    {
        // TODO : faire plutot un formalizePath ????
        // Prevent error if the target is a symlink directory path with a trailing slash.
        // TODO : attention il faut gérer le cas ou il y a un path qui est uniquement un seul slash ou antislash. exemple: $path = '/' ou './'
        $directory = self::trimTrailingSlash($directory);

        // Sanity check
        // TODO : faire plutot une vérification si le $path est bien un chemin absolu !!!!
        /*
        if ($directory === '') {
            // Bad programmer! Bad Bad programmer!
            throw new FilesystemException(__METHOD__ . ': You can not delete a base directory.');
        }*/


        if (! $this->isDirectory($directory)) {
            // TODO : lever une exception si ce n'est pas un répertoire ou qu'il n'existe pas ? plutot que de retourner un booléen ?
            return false;
        }
        
        // TODO : code à factoriser dans la méthode createIterator()
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isFile() || $item->isLink()) {
                $this->unlink($item->getPathname());
            } else {
                $this->rmdir($item->getPathname());
            }
        }

        // should we "preserve" the current folder ?
        if (! $preserve) {
            if (is_link($directory)) {
                $this->unlink($directory);
            } else {
                $this->rmdir($directory);
            }
        }

        return true;
    }

    /**
     * Attempts to rmdir a file
     *
     * @param  string            $path
     * @throws FilesystemException
     * @return bool
     */
    public function rmdir(string $path): bool
    {
        // TODO : il faudrait vérifier que le $path existe bien, sinon lever une notfoundexception !!!!

        $deleted = @rmdir($path);

        if (!$deleted) {
            $error = error_get_last();
            throw new FilesystemException($error['message'], $error['type']);
        }

        return true;
    }

    /**
     * Attempts to unlink a file
     *
     * @param  string            $path
     * @throws FilesystemException
     * @return bool
     */
    // TODO : utiliser plutot un typehint "void" pour le retour car cette fonction ne pourra jamais retourner false car on lévera une exception dans ce cas là. Eventuellemment regarder en utilisant un @ quelle est la valeur de retour
    // TODO : renommer la méthode en delete() ????
    public function unlink(string $path): bool
    {
        // TODO : il faudrait vérifier que le $path existe bien, sinon lever une notfoundexception !!!!
        $unlinked = @$this->unlinkImplementation($path);

        if (!$unlinked) {
            $error = error_get_last();

            // TODO : corriger le cas ou le fichier n'existe pas car la méthode unlinkimplementation va retourner false mais sans qu'il n'y ait d'erreurs donc le error_get_last retournera null. Pour l'instnat on ajoute un fix temporaire. Exemple : https://github.com/nette/utils/blob/master/src/Utils/FileSystem.php#L78
            if ($error === null) {
                $error = ['message' => 'Unlink failed', 'type' => 0];
            }

            throw new FilesystemException($error['message'], $error['type']);
        }

        return true;
    }

    /**
     * delete symbolic link implementation (commonly known as "unlink()")
     *
     * symbolic links on windows which link to directories need rmdir instead of unlink
     *
     * @param string $path
     *
     * @return bool
     */
    private function unlinkImplementation($path)
    {
        // Prevent error if the target is a symlink directory path with a trailing slash.
        $path = self::trimTrailingSlash($path);

        if (Util::isWindows() && is_dir($path) && is_link($path)) {
            return rmdir($path);
        }

        return unlink($path);
    }

    public function unlink2(string $filename)
    {
        if ($this->exists($filename)) {
            $result = unlink($filename);

            //Wiping out changes in local file cache
            clearstatcache(false, $filename);

            return $result;
        }

        return false;
    }

    // TODO : à déplacer dans la future classe Path::class !!!!
    public static function trimTrailingSlash(string $path): string
    {
        // TODO : attention il faut gérer le cas ou il y a un path qui est uniquement un seul slash ou antislash. exemple: $path = '/'
        return rtrim($path, '/\\');
    }

    /**
     * Normalizes given directory names by removing trailing slashes.
     *
     * Excluding: (s)ftp:// or ssh2.(s)ftp:// wrapper
     */
    // TODO : utiliser la méthode normalizeDir plutot que trimTrailingSlash ????
    private function normalizeDir(string $dir): string
    {
        if ('/' === $dir) {
            return $dir;
        }

        $dir = rtrim($dir, '/'. DIRECTORY_SEPARATOR);

        if (preg_match('#^(ssh2\.)?s?ftp://#', $dir)) {
            $dir .= '/';
        }

        return $dir;
    }























    /**
     * {@inheritdoc}
     */
    // TODO : https://github.com/ventoviro/windwalker-filesystem/blob/8ed58bd689224b301ae5b0d3dade988fbefbfc44/Path.php#L107
    public function getPermissions(string $filename): int
    {
        if (!$this->exists($filename)) {
            throw new FileNotFoundException($filename);
        }

        //TODO : faire un decoct ? mais cela retourna une string !!!!     https://github.com/cakephp/filesystem/blob/8fe8713f9be87e0fe08d445e5f6a3b5ebb6923ca/File.php#L461
        return fileperms($filename) & 0777;
    }

    /**
     * {@inheritdoc}
     */
    //https://github.com/illuminate/filesystem/blob/master/Filesystem.php#L195
    // TODO : https://github.com/ventoviro/windwalker-filesystem/blob/8ed58bd689224b301ae5b0d3dade988fbefbfc44/Path.php#L55
    // TODO : https://github.com/cakephp/filesystem/blob/7d7df204b495d1be864254c7804c117fa5ac4ba0/Folder.php#L446
    public function setPermissions(string $filename, int $mode): bool
    {
        // TODO : voir si on conserve ce "if". Réfléchir aussi si on devrait pas utiliser la méthode interne isDir() au lieu de is_dir()
        if (is_dir($filename)) {
            //Directories must always be executable (i.e. 664 for dir => 775)
            $mode |= 0111;
        }

        return $this->getPermissions($filename) === $mode || chmod($filename, $mode);
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
    // TODO : fonction à virer (actuellement utilisée uniquement dans le PackageManifest, si on passe par un format json cette fonction ne servira plus à rien)
    /*
    public function getRequire(string $filename)
    {
        if ($this->isFile($filename)) {
            return require $filename;
        }

        throw new FileNotFoundException();
    }*/

    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @return bool
     */
    // TODO : renomme la fonction en mkdir() ???
    // TODO : virer le dernier paramétre et toujours le laisser à true ????
    // TODO : créer une méthode 'ensureDirectoryExists()' qui appellera la méthode makeDirectory si nécessaire !!! https://github.com/illuminate/filesystem/blob/master/Filesystem.php#L559
    // https://github.com/nette/utils/blob/master/src/Utils/FileSystem.php#L26
    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        // TODO : retourner une exception si la valeur de retour du mkdir est à FALSE !!!!
        return mkdir($path, $mode, $recursive);
    }

    /**
     * Ensure a directory exists.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @return void
     */
    // https://github.com/illuminate/filesystem/blob/master/Filesystem.php#L559
    // TODO : il faudrait que cette méthode retourne un booléen pour savoir si la création du répertoire à bien fonctionnée !!!
    public function ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true): void
    {
        if (! $this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }


    /**
     * Returns the last modification time for the given paths.
     *
     * If the path is a directory, any nested files/directories will be checked as well.
     *
     * @param string ...$paths The directories to be checked.
     *
     * @throws LogicException If path is not set.
     *
     * @return int Unix timestamp representing the last modification time.
     */
    public function lastModifiedTime(string ...$paths): int
    {
        // TODO : utiliser la methode exists() pour vérifier si le path est correcte, si ce n'est pas le cas lever une FileNotFoundException, attention aussi à vérifier que le variadic est bien passé en paramétre et pas une chaine/tableau vide !!!!
        if (empty($paths)) {
            throw new LogicException('Path is required.');
        }

        $times = [];

        foreach ($paths as $path) {
            $times[] = filemtime($path);

            if (is_file($path)) {
                continue;
            }

            // TODO : voir si on peut utiliser createIterator() histoire de mutualiser le code !!!
            /** @var iterable<string, string> $iterator */
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $p => $_info) {
                $times[] = filemtime($p);
            }
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return max($times);
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
    //https://github.com/composer/composer/blob/74a63b4d6b16aede081a12fbec7645ecbfa8bc64/src/Composer/Util/Filesystem.php#L477
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
    //https://github.com/nette/utils/blob/69c5192b68ec3c7d0ee236bf9c47c4d1a54e7d29/src/Utils/FileSystem.php#L158
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
     * Normalizes a file/directory path.
     *
     * The normalization does the following work:
     *
     * - Convert all directory separators into `/` (e.g. "\a/b\c" becomes "/a/b/c")
     * - Remove trailing directory separators (e.g. "/a/b/c/" becomes "/a/b/c")
     * - Turn multiple consecutive slashes into a single one (e.g. "/a///b/c" becomes "/a/b/c")
     * - Remove ".." and "." based on their meanings (e.g. "/a/./b/../c" becomes "/a/c")
     *
     * @param string $path the file/directory path to be normalized
     *
     * @return string the normalized file/directory path
     */
    //https://github.com/yiisoft/files/blob/ee71f385bd1cb31d8cd40d6752efc15c66403cac/src/FileHelper.php#L108
    public static function normalizePath4(string $path): string
    {
        $isWindowsShare = strpos($path, '\\\\') === 0;

        if ($isWindowsShare) {
            $path = substr($path, 2);
        }

        $path = rtrim(strtr($path, '/\\', '//'), '/');

        if (strpos('/' . $path, '/.') === false && strpos($path, '//') === false) {
            return $isWindowsShare ? "\\\\$path" : $path;
        }

        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part !== '.' && ($part !== '' || empty($parts))) {
                $parts[] = $part;
            }
        }

        $path = implode('/', $parts);

        if ($isWindowsShare) {
            $path = '\\\\' . $path;
        }

        return $path === '' ? '.' : $path;
    }






    /**
     * Returns a correct set of slashes for given $path. (\\ for Windows paths and / for other paths.)
     *
     * @param string $path Path to transform
     * @return string Path with the correct set of slashes ("\\" or "/")
     */
    //https://github.com/cakephp/filesystem/blob/master/Folder.php#L362
    public static function normalizeFullPath(string $path): string
    {
        $to = Folder::correctSlashFor($path);
        $from = ($to === '/' ? '\\' : '/');

        return str_replace($from, $to, $path);
    }


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
    public static function normalize($path, $ds = DIRECTORY_SEPARATOR)
    {
        $parts = [];
        $path = static::clean($path, $ds);
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
    // TODO : vérifier que dans le phpdoc le type de retour est correct. Pourquoi l'iterator a une string comme clés ????
    public function files(string $directory, bool $recursive = true): Traversable
    {
        $iterator = $this->createIterator($directory, $recursive);

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
    // TODO : vérifier que dans le phpdoc le type de retour est correct. Pourquoi l'iterator a une string comme clés ????
    public function directories(string $directory, bool $recursive = true): Traversable
    {
        $iterator = $this->createIterator($directory, $recursive);

        $directories = new CallbackFilterIterator($iterator, function (SplFileInfo $item) {
            return $item->isDir();
        });

        return $directories;
    }

    // TODO : il faudrait mettre un mask par défaut pour renvoer d'office l'ensemble des dossiers et fichiers d'un répertoire sous forme de tableau de SplFileInfo. Si on ne modifie pas cette méthode il faudrait créer une méthode ->items($directory, $recursive) pour retourner l'ensemble des items (cad que ca serait une sous méthode de ->directories() et de ->files() mais sans le filtrage sur isFile ou isDir !!!!) 
    // TODO : il faudrait aussi pouvoir caster le résultat en un typehint 'array' !!!
    // TODO : on devrait permettre de passer une chaine vide pour le mask ? non ? donc le typehint passerai à "?string"
    public function find(string $directory, string $mask, bool $recursive = true): Traversable
    {
        $regex = $this->toRegEx($mask);
        $iterator = $this->createIterator($directory, $recursive);

        $finder = new CallbackFilterIterator($iterator, function (SplFileInfo $item) use ($regex, $iterator) {
            return $regex === null || preg_match($regex, '/' . strtr($iterator->getSubPathName(), '\\', '/'));
        });

        return $finder;
    }

    // TODO : tester quand le mask est vie ou seulement à '*'
    // TODO : déplacer ce bout de code dans une classe Utils ????
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
    // TODO : à déplacer dans la classe Util ? ou éventuellement créer une classe Iterator::class
    private function createIterator(string $directory, bool $recursive): Iterator 
    {
        // TODO : vérifier si cela gére bien le cas du $directory vide et du $directory qui ne peut pas être ouvert, car sinon on ava avoir une RuntimeException si le path est vide, et une Unexpected ValueException si le chemin ne peut pas être ouvert !!!!  https://www.php.net/manual/fr/directoryiterator.construct.php
        if (! $this->isDirectory($directory)) {
            return new EmptyIterator();
        }

        // TODO : il faudrait faire un catch de l'erreur Throwable et la transformer en FilesystemException. Cela peut arriver si le chemin ne peut pas être ouvert (je suppose si il n'existepas ou si il est inaccessible en terme de droits)
        $flags = FilesystemIterator::FOLLOW_SYMLINKS | FilesystemIterator::SKIP_DOTS;
        $iterator = new RecursiveDirectoryIterator($directory, $flags);

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
        }

        return $iterator;



/*
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } catch (UnexpectedValueException $exception) {
            throw new RuntimeException($exception->getMessage());
        }
*/






    }



    /**
     * iteratorToArray
     *@see https://github.com/symfony/symfony/issues/6460
     *
     * @param \Traversable $iterator
     *
     * @return  array
     */
    // TODO : à déplacer dans la classe Util ? ou éventuellement créer une classe Iterator::class
    public static function iteratorToArray(\Traversable $iterator)
    {
        $array = [];

        foreach ($iterator as $key => $file) {
            $array[] = (string) $file;
        }

        return $array;
    }




    /**
     * {@inheritdoc}
     *
     * @link http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
     */
    //https://github.com/spiral/files/blob/master/src/Files.php#L384
    public function relativePath(string $path, string $from): string
    {
        $path = $this->normalizePath($path);
        $from = $this->normalizePath($from);

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


    /**
     * @param string[] $ignored
     */
    //https://github.com/contributte/console-extra/blob/master/src/Utils/Files.php#L16
    // TODO : méthode à virer elle correspond à la méthode deleteDirectory !!!!
    /*
    public static function purge(string $dir, array $ignored = []): void
    {
        if (!is_dir($dir) && !mkdir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $entry) {
            if (!in_array(str_replace('\\', '/', (string) $entry->getRealPath()), $ignored, true)) {
                if ($entry->isDir()) {
                    rmdir((string) $entry->getRealPath());
                } else {
                    unlink((string) $entry->getRealPath());
                }
            }
        }
    }*/

/*
    function dirsize($dir)
    {
        foreach (glob($dir . "/*") as $f) {
            $d += is_file($f) ? filesize($f) : dirsize($f);
        }
        return round($d / 1024, 3);
    }
*/

    



    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @link https://bugs.php.net/bug.php?id=54709
     *
     * @param string $file
     *
     * @return boolean
     *
     * @throws             \Exception
     * @codeCoverageIgnore Not practical to test, as travis runs on linux
     */
    //https://github.com/codeigniter4/CodeIgniter4/blob/9355f0326ade101fdb9f656c3a0e33f25a1e0fe8/system/Common.php#L615
    public static function is_really_writable(string $file): bool
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' || ! ini_get('safe_mode'))
        {
            return is_writable($file);
        }

        /* For Windows servers and safe_mode "on" installations we'll actually
         * write a file then read it. Bah...
         */
        if (is_dir($file))
        {
            $file = rtrim($file, '/') . '/' . bin2hex(random_bytes(16));
            if (($fp = @fopen($file, 'ab')) === false)
            {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);

            return true;
        }
        elseif (! is_file($file) || ( $fp = @fopen($file, 'ab')) === false)
        {
            return false;
        }

        fclose($fp);

        return true;
    }







    /**
     * Create the most normalized version for path to file or location.
     *
     * @param string $path        File or location path.
     * @param bool   $asDirectory Path points to directory.
     *
     * @return string
     */
    // https://github.com/spiral/framework/blob/2.8/src/Files/src/Files.php#L383
    public function normalizePath22222(string $path, bool $asDirectory = false): string
    {
        $path = str_replace(['//', '\\'], '/', $path);

        //Potentially open links and ../ type directories?
        return rtrim($path, '/') . ($asDirectory ? '/' : '');
    }


    /**
     * Get relative location based on absolute path.
     * @see http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
     *
     * @param string $path Original file or directory location (to).
     * @param string $from Path will be converted to be relative to this directory (from).
     *
     * @return string
     */
    // https://github.com/spiral/framework/blob/2.8/src/Files/src/Files.php#L396
    public function relativePath222222(string $path, string $from): string
    {
        $path = $this->normalizePath($path);
        $from = $this->normalizePath($from);

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
                }
                $relative[0] = './' . $relative[0];
            }
        }

        return implode('/', $relative);
    }


}
