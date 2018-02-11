<?php
/**
 * This file is part of Comely package.
 * https://github.com/comelyio/comely
 *
 * Copyright (c) 2016-2018 Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comelyio/comely/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\IO\FileSystem;

use Comely\IO\FileSystem\Disk\AbstractPath;
use Comely\IO\FileSystem\Disk\AbstractPath\Functions;
use Comely\IO\FileSystem\Disk\Directory;
use Comely\IO\FileSystem\Disk\DiskInterface;
use Comely\IO\FileSystem\Disk\File;
use Comely\IO\FileSystem\Disk\Paths;
use Comely\IO\FileSystem\Disk\Privileges;
use Comely\IO\FileSystem\Exception\DiskException;
use Comely\IO\FileSystem\Exception\PathException;

/**
 * Class Disk
 * @package Comely\IO\FileSystem
 */
class Disk implements DiskInterface
{
    /** @var Directory */
    private $dir;

    /**
     * Disk constructor.
     * @param null|string $path
     * @throws DiskException
     */
    public function __construct(?string $path = null)
    {
        // Provided path or get current directory
        $path = $path ?? getcwd();

        // Making sure we are working in local environment
        if (!stream_is_local($path)) {
            throw new DiskException('Path to directory must be on local machine');
        }

        // Grab Directory Instance
        try {
            $this->dir = AbstractPath::Instance($path, $this, true);
        } catch (DiskException $e) {
            // Maybe directory doesn't exist, give it another chance
            Functions::getInstance()->createDirectory(Paths::Absolute($path, $this), 0777);
            $this->dir = AbstractPath::Instance($path, $this, true);
        }

        // Make sure it is a directory
        if ($this->dir->is() !== Disk::IS_DIR) {
            throw new DiskException('Disk component must be provided with a path to directory');
        }
    }

    /**
     * Check permissions of directory set as root directory of Disk instance
     * @return Privileges
     */
    public function privileges(): Privileges
    {
        return $this->dir->permissions();
    }

    /**
     * @return Disk
     */
    public function clearStatCache(): self
    {
        FileSystem::clearStatCache();
        return $this;
    }

    /**
     * @param string $pathToFile
     * @return File
     * @throws PathException
     */
    public function file(string $pathToFile): File
    {
        return $this->dir->file($pathToFile, false);
    }

    /**
     * @param string $pathToDirectory
     * @return Directory
     * @throws PathException
     */
    public function dir(string $pathToDirectory): Directory
    {
        /** @var AbstractPath $instance */
        $instance = Directory::Instance($pathToDirectory, $this, false);
        if (!$instance instanceof Directory) {
            throw PathException::OperationError(
                'Path is not a directory',
                $instance->path(),
                PathException::BAD_TYPE
            );
        }

        return $instance;
    }

    /**
     * @param string $fileName
     * @return string
     * @throws PathException
     */
    public function read(string $fileName): string
    {
        return $this->dir->read($fileName);
    }

    /**
     * @param string $fileName
     * @param string $contents
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws PathException
     */
    public function write(string $fileName, string $contents, bool $append = false, bool $lock = false): int
    {
        return $this->dir->write($fileName, $contents, $append, $lock);
    }

    /**
     * @param string $fileName
     * @throws PathException
     */
    public function delete(string $fileName): void
    {
        $this->dir->delete($fileName);
    }

    /**
     * @param string $pattern
     * @param int $flags
     * @return array
     * @throws PathException
     */
    public function glob(string $pattern, int $flags = 0): array
    {
        return $this->dir->glob($pattern, $flags);
    }
}