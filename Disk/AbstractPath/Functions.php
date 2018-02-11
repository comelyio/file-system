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

namespace Comely\IO\FileSystem\Disk\AbstractPath;

use Comely\IO\FileSystem\Disk\Paths;
use Comely\IO\FileSystem\Exception\PathException;

/**
 * Class Functions
 * @package Comely\IO\FileSystem\Disk\AbstractPath
 */
class Functions
{
    /** @var self */
    private static $instance;

    /**
     * @return Functions
     */
    public static function getInstance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        return new self();
    }

    /**
     * Functions constructor.
     */
    private function __construct()
    {
    }


    /**
     * @param string $absolutePath
     * @param int $permissions
     * @throws PathException
     */
    public function createDirectory(string $absolutePath, int $permissions = 0777): void
    {
        // Recursively create directories
        if (!mkdir($absolutePath, $permissions, true)) {
            throw new PathException(sprintf('Failed to create directories "%s"', $absolutePath));
        }
    }

    /**
     * @param string $absolutePath
     * @return int
     * @throws PathException
     */
    public function lastModified(string $absolutePath): int
    {
        $lastModifiedOn = filemtime($absolutePath);
        if (!$lastModifiedOn) {
            throw PathException::OperationError('Failed to retrieve last modification time for', $absolutePath);
        }

        return $lastModifiedOn;
    }

    /**
     * @param string $absolutePath
     * @param int $permissions
     * @throws PathException
     */
    public function chmod(string $absolutePath, int $permissions = 0755): void
    {
        if (!preg_match('/^0[0-9]{3}$/', $permissions)) {
            throw PathException::OperationError('Invalid permissions argument for', $absolutePath);
        }

        $chmod = chmod($absolutePath, $permissions);
        if (!$chmod) {
            throw PathException::OperationError(
                sprintf('Failed to set "%s" permissions for', $permissions), $absolutePath
            );
        }
    }

    /**
     * @param string $absolutePath
     * @return string
     * @throws PathException
     */
    public function read(string $absolutePath): string
    {
        $contents = file_get_contents($absolutePath);
        if (!$contents) {
            throw PathException::OperationError('Reading failed for file', $absolutePath);
        }

        return $contents;
    }

    /**
     * @param string $absolutePath
     * @param string $contents
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws PathException
     */
    public function write(string $absolutePath, string $contents, bool $append = false, bool $lock = false): int
    {
        $flags = 0;
        if ($append && $lock) {
            $flags = FILE_APPEND | LOCK_EX;
        } elseif ($append) {
            $flags = FILE_APPEND;
        } elseif ($lock) {
            $flags = LOCK_EX;
        }

        $bytes = file_put_contents($absolutePath, $contents, $flags);
        if ($bytes === false) {
            throw PathException::OperationError('Writing failed for file', $absolutePath);
        }

        return $bytes;
    }

    /**
     * @param string $absolutePath
     * @throws PathException
     */
    public function delete(string $absolutePath): void
    {
        $delete = unlink($absolutePath);
        if (!$delete) {
            throw PathException::OperationError('Failed to delete file', $absolutePath);
        }
    }

    /**
     * @param string $absolutePath
     * @throws PathException
     */
    public function deleteRecursively(string $absolutePath): void
    {
        if (is_file($absolutePath)) {
            $this->delete($absolutePath);
        } elseif (is_dir($absolutePath)) {
            $dirScan = scandir($absolutePath);
            if (!$dirScan) {
                throw PathException::OperationError('Failed to scan directory for deleting', $absolutePath);
            }

            foreach ($dirScan as $dirContent) {
                if (in_array($dirContent, [".", ".."])) {
                    continue; // Skip dots
                }

                try {
                    $this->deleteRecursively(Paths::Validate($absolutePath . DIRECTORY_SEPARATOR . $dirContent));
                } catch (PathException $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }

            $delete = rmdir($absolutePath);
            if (!$delete) {
                throw PathException::OperationError('Failed to delete directory', $absolutePath);
            }
        }
    }
}