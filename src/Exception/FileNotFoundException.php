<?php

declare(strict_types=1);

namespace Chiron\Filesystem\Exception;

/**
 * When trying to access missing file.
 */
class FileNotFoundException extends FilesystemException
{
    /**
     * @param string $path
     */
    public function __construct($path)
    {
        parent::__construct("File does not exist at path {$path}");
    }
}
