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

use Comely\IO\FileSystem\Disk\AbsolutePath;
use Comely\IO\FileSystem\Disk\DiskConstants;
use Comely\IO\FileSystem\Disk\PathInfo;
use Comely\IO\FileSystem\Disk\Privileges;
use Comely\IO\FileSystem\Exception\DiskException;
use Comely\IO\FileSystem\Exception\PathException;

/**
 * Class Disk
 * @package Comely\IO\FileSystem
 */
class Disk implements DiskConstants
{
    /** @var AbsolutePath */
    private $path;

    /**
     * Disk constructor.
     * @param string $path
     * @throws DiskException
     */
    public function __construct(string $path = null)
    {
        // Making sure we are working in local environment
        if (!stream_is_local($path)) {
            throw new DiskException('Path to directory must be on local machine');
        }

        // Current directory?
        if (!$path) {
            $path = getcwd();
        }

        // Validate path
        $diskPath = $this->validatePath(__CLASS__, $path);

        // Grab AbsolutePath instance
        try {
            $this->path = $diskPath->getAbsolute(true);
        } catch (PathException $e) {
            // Maybe directory doesn't exist, give it another chance
            $this->createDir($path, 0777);
            $this->path = $diskPath->getAbsolute(true);
        }

        // Make sure it is a directory
        if ($this->path->is() !== AbsolutePath::IS_DIR) {
            throw new DiskException('Disk component must be provided with a path to directory');
        }
    }

    /**
     * Check permissions of directory set as root directory of Disk instance
     * @return Privileges
     */
    public function privileges(): Privileges
    {
        return $this->path->permissions();
    }

    /**
     * @param $dirs
     * @param int $permissions
     * @throws DiskException
     */
    public function createDir($dirs, int $permissions = 0777): void
    {
        $path = $this->validatePath(__METHOD__, $dirs);

        // Recursively create directories
        if (!mkdir($path->path, $permissions, true)) {
            throw new DiskException(sprintf('Failed to create directories "%s"', $dirs));
        }
    }

    /**
     * @param string $path
     * @return AbsolutePath
     * @throws DiskException
     */
    public function path(string $path): AbsolutePath
    {
        return $this->validatePath(__METHOD__, $path)->getAbsolute(true);
    }

    /**
     * Grab a file, making sure that it exists
     *
     * @param string $path
     * @return AbsolutePath
     * @throws PathException
     */
    public function file(string $path): AbsolutePath
    {
        $pathInfo = $this->validatePath(__METHOD__, $path);

        try {
            $file = $pathInfo->getAbsolute(true);
        } catch (DiskException $e) {
            throw new PathException(
                sprintf('File "%s" not found in directory "%s"', basename($path), dirname($path)),
                PathException::NON_EXISTENT
            );
        }

        if ($file->is() !== Disk::IS_FILE) {
            throw new PathException(
                sprintf('"%s" in directory "%s" is not a file', basename($path), dirname($path)),
                PathException::BAD_TYPE
            );
        }

        return $file;
    }

    /**
     * Grab a directory, making sure that it exists
     *
     * @param string $path
     * @return AbsolutePath
     * @throws PathException
     */
    public function dir(string $path): AbsolutePath
    {
        $pathInfo = $this->validatePath(__METHOD__, $path);

        try {
            $dir = $pathInfo->getAbsolute(true);
        } catch (DiskException $e) {
            throw new PathException(
                sprintf('Directory "%s" not found in "%s"', basename($path), dirname($path)),
                PathException::NON_EXISTENT
            );
        }

        if ($dir->is() !== Disk::IS_DIR) {
            throw new PathException(
                sprintf('"%s" in "%s" is not a directory', basename($path), dirname($path)),
                PathException::BAD_TYPE
            );
        }

        return $dir;
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
        return $this->path->write($fileName, $contents, $append, $lock);
    }

    /**
     * @param string $pattern
     * @param int $flags
     * @return array
     * @throws PathException
     */
    public function find(string $pattern, int $flags = 0): array
    {
        return $this->path->find($pattern, $flags);
    }

    /**
     * @param string $method
     * @param string $path
     * @param string $allowedChars
     * @return PathInfo
     * @throws DiskException
     */
    private function validatePath(string $method, string $path, string $allowedChars = ""): PathInfo
    {
        try {
            return new PathInfo($path, $this->path, $allowedChars);
        } catch (DiskException $e) {
            throw DiskException::PathError($method, $e->getMessage());
        }
    }
}