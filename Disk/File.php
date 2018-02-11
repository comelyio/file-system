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
 * Class File
 * @package Comely\IO\FileSystem\Disk
 */
class File extends AbstractPath
{
    /**
     * @return int
     */
    final public function is(): int
    {
        return self::IS_FILE;
    }

    /**
     * @return int
     * @throws PathException
     */
    final public function lastModified(): int
    {
        return $this->functions()->lastModified($this->path());
    }

    /**
     * @param int $permissions
     * @return File
     * @throws PathException
     */
    final public function chmod(int $permissions = 0755): self
    {
        $this->functions()->chmod($this->path(), $permissions);
        $this->permissions(true); // Reload permission
        return $this;
    }

    /**
     * @return string
     * @throws PathException
     */
    final public function read(): string
    {
        if (!$this->permissions()->read) {
            throw PathException::OperationError(
                'Read permission error',
                $this->path(),
                PathException::PERMISSION_ERROR
            );
        }

        return $this->functions()->read($this->path());
    }

    /**
     * @param string $contents
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws PathException
     */
    public function write(string $contents, bool $append = false, bool $lock = false): int
    {
        if (!$this->permissions()->write) {
            throw PathException::OperationError(
                'Write permission error',
                $this->path(),
                PathException::PERMISSION_ERROR
            );
        }

        $written = $this->functions()->write($this->path(), $contents, $append, $lock);
        return $written;
    }

    /**
     * @throws PathException
     */
    public function delete(): void
    {
        if (!$this->permissions()->write) {
            throw PathException::OperationError(
                'Delete permission error',
                $this->path(),
                PathException::PERMISSION_ERROR
            );
        }

        $this->functions()->delete($this->path());
    }
}