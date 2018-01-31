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

namespace Comely\IO\FileSystem\Disk;

use Comely\IO\FileSystem\Exception\PathException;

/**
 * Class AbsolutePath
 * @package Comely\IO\FileSystem\Disk
 */
class AbsolutePath
{
    public const IS_UNKNOWN = 1000;
    public const IS_FILE = 1001;
    public const IS_DIR = 1002;

    /** @var string */
    private $path;
    /** @var int */
    private $type;
    /** @var null|Privileges */
    private $privileges;

    /**
     * AbsolutePath constructor.
     * @param PathInfo $pathInfo
     * @param bool $clearCache
     * @throws PathException
     */
    public function __construct(PathInfo $pathInfo, bool $clearCache = true)
    {
        if ($clearCache) {
            clearstatcache(true);
        }

        $absolutePath = realpath($pathInfo->path);
        if (!$absolutePath) {
            throw new PathException(sprintf('Could not resolve absolute path to "%s"', $pathInfo->path));
        }

        $this->path = $absolutePath;
        $this->type = self::IS_UNKNOWN;

        // Determine if path is a regular file or directory
        if (is_file($this->path)) {
            $this->type = self::IS_FILE;
        } elseif (is_dir($this->path)) {
            $this->type = self::IS_DIR;
        }
    }

    /**
     * @return int
     */
    public function is(): int
    {
        return $this->type;
    }

    /**
     * To save system resources, privileges will only be checked (once) if this method is called
     * @return Privileges
     */
    public function permissions(): Privileges
    {
        if (!$this->privileges) {
            $this->privileges = new Privileges($this->path);
        }

        return $this->privileges;
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @param string $suffix
     * @return string
     */
    public function suffixed(string $suffix): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $suffix;
    }

    /**
     * @param int $permissions
     * @return AbsolutePath
     * @throws PathException
     */
    public function chmod(int $permissions = 0755): self
    {
        if (!preg_match('/^0[0-9]{3}$/', $permissions)) {
            throw PathException::OperationError('Cannot set permission for', $this->path);
        }

        $chmod = chmod($this->path, $permissions);
        if (!$chmod) {
            throw PathException::OperationError(
                sprintf('Failed to set "%s" permissions for', $permissions), $this->path
            );
        }

        $this->privileges = new Privileges($this->path); // Reload privileges
        return $this;
    }

    /**
     * @return int
     * @throws PathException
     */
    public function lastModified(): int
    {
        $lastModifiedFile = $this->path;
        if ($this->type === self::IS_DIR) {
            $lastModifiedFile = $this->suffixed(".");
        }

        $lastModifiedOn = filemtime($lastModifiedFile);
        if (!$lastModifiedOn) {
            throw PathException::OperationError('Failed to retrieve last modification time for', $this->path);
        }

        return $lastModifiedOn;
    }
}