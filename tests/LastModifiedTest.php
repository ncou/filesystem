<?php

declare(strict_types=1);

namespace Chiron\Tests\Filesystem;

use EmptyIterator;
use Chiron\Filesystem\Filesystem;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Traversable;

class LastModifiedTest extends TestCase
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

    public function testLastModifiedTime(): void
    {
        $dirName = 'assets';
        $basePath = $this->workingDir . '/' . $dirName;

        $this->createFileStructure(
            [
                $dirName => [
                    'css' => [
                        'stub.css' => 'testMe',
                    ],
                    'js' => [
                        'stub.js' => 'testMe',
                    ],
                ],
            ]
        );

        $fs = new Filesystem();

        $this->assertIsInt($fs->lastModifiedTime($basePath));
        $this->assertIsInt($fs->lastModifiedTime($basePath . '/css/stub.css'));
        $this->assertIsInt($fs->lastModifiedTime($basePath . '/css', $basePath . '/js'));
    }

    public function testLastModifiedTimeWithoutArguments(): void
    {
        $this->expectException(LogicException::class);

        $fs = new Filesystem();
        $fs->lastModifiedTime();
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





    /**
     * Creates test files structure.
     *
     * @param array $items file system objects to be created in format: objectName => objectContent
     * Arrays specifies directories, other values - files.
     * @param string|null $basePath structure base file path.
     */
    private function createFileStructure(array $items, ?string $basePath = null): void
    {
        $basePath = $basePath ?? $this->workingDir;

        if (empty($basePath)) {
            $basePath = $this->workingDir;
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
}
