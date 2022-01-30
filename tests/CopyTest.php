<?php

declare(strict_types=1);

namespace Chiron\Tests\Filesystem;

use EmptyIterator;
use Chiron\Filesystem\Filesystem;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Traversable;


class CopyTest extends TestCase
{
    /**
     * @var string
     */
    private $workingDir;
    /**
     * @var string
     */
    private $testFile;


    public function setUp(): void
    {
        $this->workingDir = self::getUniqueTmpDirectory();
        $this->testFile = self::getUniqueTmpDirectory() . '/composer_test_file';
    }

    public function testCopy()
    {
        @mkdir($this->workingDir . '/foo/bar', 0777, true);
        @mkdir($this->workingDir . '/foo/baz', 0777, true);
        file_put_contents($this->workingDir . '/foo/foo.file', 'foo');
        file_put_contents($this->workingDir . '/foo/bar/foobar.file', 'foobar');
        file_put_contents($this->workingDir . '/foo/baz/foobaz.file', 'foobaz');
        file_put_contents($this->testFile, 'testfile');

        $fs = new Filesystem();

        $result1 = $fs->copy($this->workingDir . '/foo', $this->workingDir . '/foop');
        $this->assertTrue($result1, 'Copying directory failed.');
        $this->assertTrue(is_dir($this->workingDir . '/foop'), 'Not a directory: ' . $this->workingDir . '/foop');
        $this->assertTrue(is_dir($this->workingDir . '/foop/bar'), 'Not a directory: ' . $this->workingDir . '/foop/bar');
        $this->assertTrue(is_dir($this->workingDir . '/foop/baz'), 'Not a directory: ' . $this->workingDir . '/foop/baz');
        $this->assertTrue(is_file($this->workingDir . '/foop/foo.file'), 'Not a file: ' . $this->workingDir . '/foop/foo.file');
        $this->assertTrue(is_file($this->workingDir . '/foop/bar/foobar.file'), 'Not a file: ' . $this->workingDir . '/foop/bar/foobar.file');
        $this->assertTrue(is_file($this->workingDir . '/foop/baz/foobaz.file'), 'Not a file: ' . $this->workingDir . '/foop/baz/foobaz.file');

        $result2 = $fs->copy($this->testFile, $this->workingDir . '/testfile.file');
        $this->assertTrue($result2);
        $this->assertTrue(is_file($this->workingDir . '/testfile.file'));
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
