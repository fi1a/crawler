<?php

declare(strict_types=1);

namespace Fi1a\Crawler\ItemStorages\StorageAdapters;

use Fi1a\Filesystem\Adapters\LocalAdapter as FilesystemLocalAdapter;
use Fi1a\Filesystem\Filesystem;

/**
 * Хранение элементов в файловой системе
 */
class LocalFilesystemAdapter extends FilesystemAdapter
{
    public function __construct(string $path)
    {
        parent::__construct(new Filesystem(new FilesystemLocalAdapter($path)));
    }
}
