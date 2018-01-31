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
class AbsolutePath implements DiskConstants
{
    /** @var string */
    private $path;
    /** @var null|string */
    private $parentPath;
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
            throw new PathException(
                sprintf('Could not resolve absolute path to "%s"', $pathInfo->path),
                PathException::NON_EXISTENT
            );
        }

        $this->path = $absolutePath;
        $this->parentPath = $pathInfo->parent;
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

    /**
     * @param string|null $path
     * @throws PathException
     */
    public function delete(string $path = null): void
    {
        // Sub-path argument passed, proceed as directory
        if ($path) {
            if ($this->type !== self::IS_DIR) {
                throw new PathException(
                    sprintf(
                        'Delete method for regular file "%s" will not accept passed argument',
                        basename($this->path)
                    ),
                    PathException::BAD_TYPE
                );
            } elseif (!$this->privileges->write) {
                throw new PathException(
                    sprintf('Cannot delete, directory "%s" is not writable', basename($this->path)),
                    PathException::PERMISSION_ERROR
                );
            }

            $deletePath = (new PathInfo($path, $this))->path;
            $this->deleteRecursive($deletePath);
            return;
        }

        // No sub-path argument, probably deleting this file
        if ($this->type !== self::IS_FILE) {
            throw new PathException(
                sprintf('Cannot delete "%s" is not a regular file', basename($this->path)),
                PathException::BAD_TYPE
            );
        } elseif (!$this->privileges->write) {
            throw new PathException(
                sprintf('Cannot delete file "%s" is not writable', basename($this->path)),
                PathException::PERMISSION_ERROR
            );
        }

        $delete = unlink($this->path);
        if (!$delete) {
            throw PathException::OperationError('Failed to delete file', $this->path);
        }
    }

    /**
     * @param string $path
     * @throws PathException
     */
    private function deleteRecursive(string $path): void
    {
        if (is_file($path)) {
            $delete = unlink($this->path);
            if (!$delete) {
                throw PathException::OperationError('Failed to delete file', $path);
            }
        } elseif (is_dir($path)) {
            $dirScan = scandir($path);
            if (!$dirScan) {
                throw PathException::OperationError('Failed to scan directory', $path);
            }

            foreach ($dirScan as $dirContent) {
                if (in_array($dirContent, [".", ".."])) {
                    continue; // Skip dots
                }

                $this->deleteRecursive($this->suffixed($dirContent));
            }

            $delete = rmdir($path);
            if (!$delete) {
                throw PathException::OperationError('Failed to delete directory', $path);
            }
        }
    }

    /**
     * @return string
     * @throws PathException
     */
    public function read(): string
    {
        if ($this->type !== self::IS_FILE) {
            throw new PathException(
                sprintf('Cannot read "%s" is not a regular file', basename($this->path)),
                PathException::BAD_TYPE
            );
        } elseif (!$this->privileges->read) {
            throw new PathException(
                sprintf('File "%s" is not readable', basename($this->path)),
                PathException::PERMISSION_ERROR
            );
        }

        $contents = file_get_contents($this->path);;
        if (!$contents) {
            throw PathException::OperationError('Reading failed for file', $this->path);
        }

        return $contents;
    }

    /**
     * @param string $pattern
     * @param int $flags
     * @return array
     * @throws PathException
     */
    public function find(string $pattern, int $flags = 0): array
    {
        if ($this->type !== self::IS_DIR) {
            throw new PathException(
                sprintf('Find method can only be called from a directory'),
                PathException::BAD_TYPE
            );
        } elseif (!$this->privileges->read) {
            throw new PathException(
                sprintf('Directory "%s" is not readable', basename($this->path)),
                PathException::PERMISSION_ERROR
            );
        }

        // Validate Path
        $pattern = new PathInfo($pattern, $this, "*");
        $glob = glob($pattern->path, $flags);
        if (is_array($glob)) {
            $parentPathLen = strlen(strval($this->parentPath));
            if ($parentPathLen) {
                $glob = array_map(function ($path) use ($parentPathLen) {
                    return substr($path, $parentPathLen);
                }, $glob);
            }

            return $glob;
        }

        return [];
    }

    /**
     * @param string $contents
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws PathException
     */
    public function edit(string $contents, bool $append = false, bool $lock = false): int
    {
        if ($this->type !== self::IS_FILE) {
            throw new PathException(
                sprintf('Cannot edit "%s" is not a regular file', basename($this->path)),
                PathException::BAD_TYPE
            );
        } elseif (!$this->privileges->write) {
            throw new PathException(
                sprintf('File "%s" is not writable', basename($this->path)),
                PathException::PERMISSION_ERROR
            );
        }

        return $this->writeToFile($this->path, $contents, $append, $lock);
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
        if ($this->type !== self::IS_DIR) {
            throw new PathException(
                sprintf('Create method can only be called from a directory'),
                PathException::BAD_TYPE
            );
        } elseif (!$this->privileges->write) {
            throw new PathException(
                sprintf('Directory "%s" is not writable', basename($this->path)),
                PathException::PERMISSION_ERROR
            );
        }

        $filePath = new PathInfo($fileName, $this);
        return $this->writeToFile($filePath->path, $contents, $append, $lock);
    }

    /**
     * @param string $filePath
     * @param string $contents
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws PathException
     */
    private function writeToFile(string $filePath, string $contents, bool $append = false, bool $lock = false): int
    {
        $flags = 0;
        if ($append && $lock) {
            $flags = FILE_APPEND | LOCK_EX;
        } elseif ($append) {
            $flags = FILE_APPEND;
        } elseif ($lock) {
            $flags = LOCK_EX;
        }

        $bytes = file_put_contents($filePath, $contents, $flags);
        if ($bytes === false) {
            throw PathException::OperationError('Writing failed for file', $filePath);
        }

        return $bytes;
    }
}