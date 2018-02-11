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
 * Class Directory
 * @package Comely\IO\FileSystem\Disk
 */
class Directory extends AbstractPath
{
    /**
     * @return int
     */
    final public function is(): int
    {
        return self::IS_DIR;
    }

    /**
     * @param string $suffix
     * @return string
     */
    final public function suffixed(string $suffix): string
    {
        return $this->path() . DIRECTORY_SEPARATOR . trim($suffix, DIRECTORY_SEPARATOR);
    }

    /**
     * @param null|string $fileName
     * @return int
     * @throws PathException
     */
    final public function lastModified(?string $fileName = null): int
    {
        $fileName = Paths::Absolute($fileName, $this) ?? $this->suffixed(".");
        return $this->functions()->lastModified($fileName);
    }

    /**
     * @param int $permissions
     * @param null|string $fileName
     * @return Directory
     * @throws PathException
     */
    final public function chmod(int $permissions = 0755, ?string $fileName = null): self
    {
        $absolutePath = $fileName ? Paths::Absolute($fileName, $this) : $this->path();
        $this->functions()->chmod($absolutePath, $permissions);
        $this->permissions(true); // Reload permission
        return $this;
    }

    /**
     * @param string $fileName
     * @return string
     * @throws PathException
     */
    final public function read(string $fileName): string
    {
        return $this->functions()->read(Paths::Absolute($fileName, $this));
    }

    /**
     * @param string $fileName
     * @param string $contents
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws PathException
     */
    final public function write(string $fileName, string $contents, bool $append = false, bool $lock = false): int
    {
        return $this->functions()->write(Paths::Absolute($fileName, $this), $contents, $append, $lock);
    }

    /**
     * @param string|null $fileName
     * @param bool $clearCache
     * @return File
     * @throws PathException
     */
    final public function file(string $fileName = null, bool $clearCache = true): File
    {
        /** @var AbstractPath $instance */
        $instance = self::Instance($fileName, $this, $clearCache);
        if (!$instance instanceof File) {
            throw PathException::OperationError('Path is not a valid', $instance->path(), PathException::BAD_TYPE);
        }

        return $instance;
    }

    /**
     * @param string $dirs
     * @param int $permissions
     * @throws PathException
     */
    public function createDirectory(string $dirs, int $permissions = 0777): void
    {
        // Recursively create directories
        if (!mkdir(Paths::Absolute($dirs, $this), $permissions, true)) {
            throw new PathException(sprintf('Failed to create directories "%s"', $dirs));
        }
    }

    /**
     * @param null|string $fileName
     * @throws PathException
     */
    public function delete(?string $fileName): void
    {
        if ($fileName) {
            $this->functions()->delete(Paths::Absolute($fileName, $this));
            return;
        }

        $this->functions()->deleteRecursively($this->path());
    }

    /**
     * @param string $pattern
     * @param int $flags
     * @return array
     * @throws PathException
     */
    public function glob(string $pattern, int $flags = 0): array
    {
        if (!$this->permissions()->read) {
            throw PathException::OperationError(
                'Directory read permission error',
                $this->path(),
                PathException::PERMISSION_ERROR
            );
        }

        // Validate Path
        $glob = glob(Paths::Validate($this->suffixed($pattern), "*"), $flags);
        if (is_array($glob)) {
            $parentDirectoryLength = 0;
            if ($this->parent()) {
                $parentDirectoryLength = strlen($this->parent()->path());
            }

            if ($parentDirectoryLength) {
                $glob = array_map(function ($path) use ($parentDirectoryLength) {
                    return substr($path, $parentDirectoryLength);
                }, $glob);
            }

            return $glob;
        }

        return [];
    }
}