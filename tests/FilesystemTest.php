<?php

declare(strict_types=1);

namespace Chiron\Tests\Filesystem;

use EmptyIterator;
use Chiron\Boot\Filesystem;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Traversable;

class FilesystemTest extends TestCase
{
    private $dir;

    protected function setUp(): void
    {
        // avoid problems with Windows path separator versus Unix separator.
        $this->dir = strtr(__DIR__, '\\', '/');
    }

    public function testFindItems()
    {
        $filesystem = new Filesystem();

        $result = $filesystem->find($this->dir . '/fixture', '*A*');

        static::assertSame([
            $this->dir . '/fixture/dirA',
            $this->dir . '/fixture/dirA/fileA.txt',
            $this->dir . '/fixture/dirA/fileAA.txt',
            $this->dir . '/fixture/dirB/fileBA.txt',
            $this->dir . '/fixture/fileA1.txt',
            $this->dir . '/fixture/fileA2.txt',
        ], $this->export($result));
    }

    public function testFindItemsComplexeRegex()
    {
        $filesystem = new Filesystem();

        // serch .txt files without the characters A/B/C/2 in the path name
        $result = $filesystem->find($this->dir . '/fixture', '*[!ABC2].t?t');

        static::assertSame([
            $this->dir . '/fixture/fileA1.txt',
        ], $this->export($result));
    }

    public function testSearchFiles()
    {
        $filesystem = new Filesystem();

        $result = $filesystem->files($this->dir . '/fixture', false);
        
        static::assertSame([
            $this->dir . '/fixture/fileA1.txt',
            $this->dir . '/fixture/fileA2.txt',
        ], $this->export($result));
    }

    public function testSearchFilesRecursive()
    {
        $filesystem = new Filesystem();

        $result = $filesystem->files($this->dir . '/fixture');
        
        static::assertSame([
            $this->dir . '/fixture/dirA/fileA.txt',
            $this->dir . '/fixture/dirA/fileAA.txt',
            $this->dir . '/fixture/dirB/fileBA.txt',
            $this->dir . '/fixture/dirB/fileBB.txt',
            $this->dir . '/fixture/dirC/dirC2/fileC2.txt',
            $this->dir . '/fixture/dirC/fileC.txt',
            $this->dir . '/fixture/fileA1.txt',
            $this->dir . '/fixture/fileA2.txt',
        ], $this->export($result));
    }

    public function testSearchDirectories()
    {
        $filesystem = new Filesystem();

        $result = $filesystem->directories($this->dir . '/fixture', false);
        
        static::assertSame([
            $this->dir . '/fixture/dirA',
            $this->dir . '/fixture/dirB',
            $this->dir . '/fixture/dirC',
        ], $this->export($result));
    }

    public function testSearchDirectoriesRecursive()
    {
        $filesystem = new Filesystem();

        $result = $filesystem->directories($this->dir . '/fixture');
        
        static::assertSame([
            $this->dir . '/fixture/dirA',
            $this->dir . '/fixture/dirB',
            $this->dir . '/fixture/dirC',
            $this->dir . '/fixture/dirC/dirC1',
            $this->dir . '/fixture/dirC/dirC2',
        ], $this->export($result));
    }

    public function testSearchFilesInNonExistingFolder()
    {
        $filesystem = new Filesystem();

        $result = $filesystem->files($this->dir . '/non_existing_folder');
        
        static::assertSame([], $this->export($result));
    }

    public function testSearchDirectoriesInNonExistingFolder()
    {
        $filesystem = new Filesystem();

        $result = $filesystem->directories($this->dir . '/non_existing_folder');
        
        static::assertSame([], $this->export($result));
    }

    private function export(Traversable $data): array
    {
        $arr = [];
        foreach ($data as $key => $value) {
            $arr[] = strtr($key, '\\', '/');
        }
        sort($arr);
        return $arr;
    }    
}
