<?php

declare(strict_types=1);

namespace Chiron\Tests\Filesystem;

use EmptyIterator;
use Chiron\Filesystem\Filesystem;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Traversable;

class DeleteTest extends TestCase
{
    /**
     * @var string
     */
    private $workingDir;

    public function setUp(): void
    {
        //$this->workingDir = self::getUniqueTmpDirectory();
        $this->workingDir = __DIR__ . '/temp';
    }

    /**
     * @link https://github.com/composer/composer/issues/3157
     * @requires function symlink
     */
    public function testUnlinkSymlinkedDirectory()
    {
        $basepath = $this->workingDir;
        $symlinked = $basepath . "/linked";
        @mkdir($basepath . "/real", 0777, true);
        touch($basepath . "/real/FILE");

        $result = @symlink($basepath . "/real", $symlinked);

        if (!$result) {
            $this->markTestSkipped('Symbolic links for directories not supported on this platform');
        }

        if (!is_dir($symlinked)) {
            $this->fail('Precondition assertion failed (is_dir is false on symbolic link to directory).');
        }

        $fs = new Filesystem();
        $result = $fs->unlink($symlinked);
        $this->assertFileNotExists($symlinked);
    }

    
    public function testDeleteSymlinkedDirectory()
    {
        $basepath = $this->workingDir;
        $symlinked = $basepath . "/linked";
        @mkdir($basepath . "/real", 0777, true);
        touch($basepath . "/real/FILE");

        $result = @symlink($basepath . "/real", $symlinked);

        if (!$result) {
            $this->markTestSkipped('Symbolic links for directories not supported on this platform');
        }

        if (!is_dir($symlinked)) {
            $this->fail('Precondition assertion failed (is_dir is false on symbolic link to directory).');
        }

        $fs = new Filesystem();
        $result = $fs->deleteDirectory($symlinked);
        $this->assertFileNotExists($symlinked);
    }


    /**
     * @link https://github.com/composer/composer/issues/3144
     * @requires function symlink
     */
    public function testDeleteSymlinkedDirectoryWithTrailingSlash()
    {
        @mkdir($this->workingDir . "/real", 0777, true);
        touch($this->workingDir . "/real/FILE");
        $symlinked = $this->workingDir . "/linked";
        $symlinkedTrailingSlash = $symlinked . "/";

        $result = @symlink($this->workingDir . "/real", $symlinked);

        if (!$result) {
            $this->markTestSkipped('Symbolic links for directories not supported on this platform');
        }

        if (!is_dir($symlinked)) {
            $this->fail('Precondition assertion failed (is_dir is false on symbolic link to directory).');
        }

        if (!is_dir($symlinkedTrailingSlash)) {
            $this->fail('Precondition assertion failed (is_dir false w trailing slash).');
        }

        $fs = new Filesystem();

        $result = $fs->deleteDirectory($symlinkedTrailingSlash);
        $this->assertTrue($result);
        $this->assertFileNotExists($symlinkedTrailingSlash);
        $this->assertFileNotExists($symlinked);
    }









    public function testRemoveDirectorySymlinks1(): void
    {
        $dirName = 'remove-directory-symlinks-1';

        $this->createFileStructure([
            $dirName => [
                'file' => 'Symlinked file.',
                'directory' => [
                    'standard-file-1' => 'Standard file 1.',
                ],
                'symlinks' => [
                    'standard-file-2' => 'Standard file 2.',
                    'symlinked-file' => ['symlink', '../file'],
                    'symlinked-directory' => ['symlink', '../directory'],
                ],
            ],
        ]);

        $basePath = $this->workingDir . '/' . $dirName . '/';

        $this->assertFileExists($basePath . 'file');
        $this->assertDirectoryExists($basePath . 'directory');
        $this->assertFileExists($basePath . 'directory/standard-file-1');
        $this->assertDirectoryExists($basePath . 'symlinks');
        $this->assertFileExists($basePath . 'symlinks/standard-file-2');
        $this->assertFileExists($basePath . 'symlinks/symlinked-file');
        $this->assertDirectoryExists($basePath . 'symlinks/symlinked-directory');
        $this->assertFileExists($basePath . 'symlinks/symlinked-directory/standard-file-1');

        $fs = new Filesystem();
        $result = $fs->deleteDirectory($basePath . 'symlinks');

        $this->assertTrue($result);

        $this->assertFileExists($basePath . 'file');
        $this->assertDirectoryExists($basePath . 'directory');
        $this->assertFileExists($basePath . 'directory/standard-file-1'); // symlinked directory still have it's file
        $this->assertDirectoryNotExists($basePath . 'symlinks');
        $this->assertFileNotExists($basePath . 'symlinks/standard-file-2');
        $this->assertFileNotExists($basePath . 'symlinks/symlinked-file');
        $this->assertDirectoryNotExists($basePath . 'symlinks/symlinked-directory');
        $this->assertFileNotExists($basePath . 'symlinks/symlinked-directory/standard-file-1');
    }


    /**
     * Creates test files structure.
     *
     * @param array $items file system objects to be created in format: objectName => objectContent
     *                         Arrays specifies directories, other values - files.
     * @param string $basePath structure base file path.
     *
     * @return void
     */
    private function createFileStructure(array $items, ?string $basePath = null): void
    {
        $basePath = $basePath ?? $this->workingDir;

        if (empty($basePath)) {
            $basePath = $this->testFilePath;
        }

        foreach ($items as $name => $content) {
            $itemName = $basePath . DIRECTORY_SEPARATOR . $name;
            if (is_array($content)) {
                if (isset($content[0], $content[1]) && $content[0] === 'symlink') {
                    symlink($basePath . '/' . $content[1], $itemName);
                } else {
                    mkdir($itemName, 0777, true);
                    $this->createFileStructure($content, $itemName);
                }
            } else {
                file_put_contents($itemName, $content);
            }
        }
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
