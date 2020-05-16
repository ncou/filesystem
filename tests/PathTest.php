<?php

declare(strict_types=1);

namespace Chiron\Tests\Filesystem;

use Chiron\Boot\Path;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    public function testRelativePath()
    {
        $this->assertSame(
            'some-filename.txt',
            Path::relativePath('/abc/some-filename.txt', '/abc')
        );

        $this->assertSame(
            '../some-filename.txt',
            Path::relativePath('/abc/../some-filename.txt', '/abc')
        );

        $this->assertSame(
            '../../some-filename.txt',
            Path::relativePath('/abc/../../some-filename.txt', '/abc')
        );

        $this->assertSame(
            './some-filename.txt',
            Path::relativePath('/abc/some-filename.txt', '/abc/..')
        );

        $this->assertSame(
            '../some-filename.txt',
            Path::relativePath('/abc/some-filename.txt', '/abc/../..')
        );
    } 


    /**
     * @dataProvider provideRelativePaths
     */
    public function testRelativePath2($sourcePath, $targetPath, $expectedPath)
    {
        $this->assertSame($expectedPath, Path::getRelativePath($sourcePath, $targetPath));
    }

    public function provideRelativePaths()
    {
        return [
            [
                '/same/dir/',
                '/same/dir/',
                '',
            ],
            [
                '/same/file',
                '/same/file',
                '',
            ],
            [
                '/',
                '/file',
                'file',
            ],
            [
                '/',
                '/dir/file',
                'dir/file',
            ],
            [
                '/dir/file.html',
                '/dir/different-file.html',
                'different-file.html',
            ],
            [
                '/same/dir/extra-file',
                '/same/dir/',
                './',
            ],
            [
                '/parent/dir/',
                '/parent/',
                '../',
            ],
            [
                '/parent/dir/extra-file',
                '/parent/',
                '../',
            ],
            [
                '/a/b/',
                '/x/y/z/',
                '../../x/y/z/',
            ],
            [
                '/a/b/c/d/e',
                '/a/c/d',
                '../../../c/d',
            ],
            [
                '/a/b/c//',
                '/a/b/c/',
                '../',
            ],
            [
                '/a/b/c/',
                '/a/b/c//',
                './/',
            ],
            [
                '/root/a/b/c/',
                '/root/x/b/c/',
                '../../../x/b/c/',
            ],
            [
                '/a/b/c/d/',
                '/a',
                '../../../../a',
            ],
            [
                '/special-chars/sp%20ce/1€/mäh/e=mc²',
                '/special-chars/sp%20ce/1€/<µ>/e=mc²',
                '../<µ>/e=mc²',
            ],
            [
                'not-rooted',
                'dir/file',
                'dir/file',
            ],
            [
                '//dir/',
                '',
                '../../',
            ],
            [
                '/dir/',
                '/dir/file:with-colon',
                './file:with-colon',
            ],
            [
                '/dir/',
                '/dir/subdir/file:with-colon',
                'subdir/file:with-colon',
            ],
            [
                '/dir/',
                '/dir/:subdir/',
                './:subdir/',
            ],
        ];
    } 

    //https://github.com/yiisoft/files/blob/ee71f385bd1cb31d8cd40d6752efc15c66403cac/tests/FileHelperTest.php#L166
    public function testNormalizePath(): void
    {
        $this->assertEquals('/a/b', Path::normalize('//a\\b/'));
        $this->assertEquals('/b/c', Path::normalize('/a/../b/c'));
        $this->assertEquals('/c', Path::normalize('/a\\b/../..///c'));
        $this->assertEquals('/c', Path::normalize('/a/.\\b//../../c'));
        $this->assertEquals('c', Path::normalize('/a/.\\b/../..//../c'));
        $this->assertEquals('../c', Path::normalize('//a/.\\b//..//..//../../c'));

        // relative paths
        $this->assertEquals('.', Path::normalize('.'));
        $this->assertEquals('.', Path::normalize('./'));
        $this->assertEquals('a', Path::normalize('.\\a'));
        $this->assertEquals('a/b', Path::normalize('./a\\b'));
        $this->assertEquals('.', Path::normalize('./a\\../'));
        $this->assertEquals('../../a', Path::normalize('../..\\a'));
        $this->assertEquals('../../a', Path::normalize('../..\\a/../a'));
        $this->assertEquals('../../b', Path::normalize('../..\\a/../b'));
        $this->assertEquals('../a', Path::normalize('./..\\a'));
        $this->assertEquals('../a', Path::normalize('././..\\a'));
        $this->assertEquals('../a', Path::normalize('./..\\a/../a'));
        $this->assertEquals('../b', Path::normalize('./..\\a/../b'));

        // Windows file system may have paths for network shares that start with two backslashes. These two backslashes
        // should not be touched.
        // https://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        // https://github.com/yiisoft/yii2/issues/13034
        $this->assertEquals('\\\\server/share/path/file', Path::normalize('\\\\server\share\path\file'));
        $this->assertEquals('\\\\server/share/path/file', Path::normalize('\\\\server\share\path//file'));
    }








    public function invalidPathProvider()
    {
        return [
            ['something/../../../hehe'],
            ['/something/../../..'],
            ['..'],
            ['something\\..\\..'],
            ['\\something\\..\\..\\dirname'],
        ];
    }

    /**
     * @expectedException  LogicException
     * @dataProvider       invalidPathProvider
     */
    public function testOutsideRootPath($path)
    {
        Path::normalize($path);
    }

    public function pathProvider()
    {
        return [
            ['.', ''],
            ['/path/to/dir/.', 'path/to/dir'],
            ['/dirname/', 'dirname'],
            ['dirname/..', ''],
            ['dirname/../', ''],
            ['dirname./', 'dirname.'],
            ['dirname/./', 'dirname'],
            ['dirname/.', 'dirname'],
            ['./dir/../././', ''],
            ['/something/deep/../../dirname', 'dirname'],
            ['00004869/files/other/10-75..stl', '00004869/files/other/10-75..stl'],
            ['/dirname//subdir///subsubdir', 'dirname/subdir/subsubdir'],
            ['\dirname\\\\subdir\\\\\\subsubdir', 'dirname/subdir/subsubdir'],
            ['\\\\some\shared\\\\drive', 'some/shared/drive'],
            ['C:\dirname\\\\subdir\\\\\\subsubdir', 'C:/dirname/subdir/subsubdir'],
            ['C:\\\\dirname\subdir\\\\subsubdir', 'C:/dirname/subdir/subsubdir'],
            ['example/path/..txt', 'example/path/..txt'],
            ['\\example\\path.txt', 'example/path.txt'],
            ['\\example\\..\\path.txt', 'path.txt'],
            ["some\0/path.txt", 'some/path.txt'],
        ];
    }

    /**
     * @dataProvider  pathProvider
     */
    public function testNormalizePath2($input, $expected)
    {
        $result = Path::normalize($input);
        $double = Path::normalize(Path::normalize($input));
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, $double);
    }

}
