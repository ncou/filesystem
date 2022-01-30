<?php

declare(strict_types=1);

namespace Chiron\Tests\Filesystem;

use EmptyIterator;
use Chiron\Filesystem\Filesystem;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Traversable;

// TODO : créer une classe FileysytemTestCase qui se charge de créer le répertoire remporaire + de le supprimer à la fin et de mettre en variable de classe une instance de FileSystem. Exemple : https://github.com/symfony/filesystem/blob/e7550993849f986f01a9161b302d4aed8d4aab0a/Tests/FilesystemTestCase.php#L72

class ReplaceTest extends TestCase
{
    /**
     * @var string
     */
    private $workspace;

    private $filesystem;

    public function setUp(): void
    {
        $this->filesystem = new Filesystem();

        $this->workspace = sys_get_temp_dir().'/'.microtime(true).'.'.mt_rand();
        mkdir($this->workspace, 0777, true);
        $this->workspace = realpath($this->workspace);
    }

    public function testReplace()
    {
        $filename = $this->workspace.\DIRECTORY_SEPARATOR.'foo'.\DIRECTORY_SEPARATOR.'baz.txt';

        // skip mode check on Windows
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $oldMask = umask(0002);
        }

        $this->filesystem->replace($filename, 'bar');
        $this->assertFileExists($filename);
        $this->assertStringEqualsFile($filename, 'bar');

        // skip mode check on Windows
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $this->assertFilePermissions(664, $filename);
            umask($oldMask);
        }
    }

    public function testReplaceOverwritesAnExistingFile()
    {
        $filename = $this->workspace.\DIRECTORY_SEPARATOR.'foo.txt';
        file_put_contents($filename, 'FOO BAR');

        $this->filesystem->replace($filename, 'bar');

        $this->assertFileExists($filename);
        $this->assertStringEqualsFile($filename, 'bar');
    }



    /**
     * @param int    $expectedFilePerms Expected file permissions as three digits (i.e. 755)
     * @param string $filePath
     */
    private function assertFilePermissions(int $expectedFilePerms, string $filePath)
    {
        $actualFilePerms = (int) substr(sprintf('%o', fileperms($filePath)), -3);
        $this->assertEquals(
            $expectedFilePerms,
            $actualFilePerms,
            sprintf('File permissions for %s must be %s. Actual %s', $filePath, $expectedFilePerms, $actualFilePerms)
        );
    }


























    private static function getUniqueTmpDirectory()
    {
        $attempts = 5;
        $root = sys_get_temp_dir();

        do {
            $unique = $root . DIRECTORY_SEPARATOR . uniqid('composer-test-' . rand(1000, 9000));

            if (!file_exists($unique) && mkdir($unique, 0777)) {
                return realpath($unique);
            }
        } while (--$attempts);

        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }
}
