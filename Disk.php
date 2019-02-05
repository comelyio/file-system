<?php
/**
 * This file is part of Comely package.
 * https://github.com/comelyio/comely
 *
 * Copyright (c) 2016-2019 Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comelyio/comely/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\IO\FileSystem;

use Comely\IO\FileSystem\Disk\Directory;
use Comely\IO\FileSystem\Disk\DiskInterface;
use Comely\IO\FileSystem\Exception\DiskException;
use Comely\IO\FileSystem\Exception\PathException;

/**
 * Class Disk
 * @package Comely\IO\FileSystem
 */
class Disk extends Directory implements DiskInterface
{
    /**
     * Disk constructor.
     * @param null|string $path
     * @param bool $clearCache
     * @throws DiskException
     * @throws Exception\PathException
     */
    public function __construct(?string $path = null, bool $clearCache = true)
    {
        // Provided path or get current directory
        $path = $path ?? getcwd();

        // Grab Directory Instance
        try {
            parent::__construct($path, null, $clearCache);
        } catch (PathException $e) {
            if ($e->getCode() === PathException::BAD_TYPE) {
                throw new DiskException('Disk component must be provided with a path to directory');
            }

            throw new DiskException($e->getMessage());
        }
    }
}