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

namespace Comely\IO\FileSystem\Exception;

/**
 * Class DiskException
 * @package Comely\IO\FileSystem\Exception
 */
class DiskException extends FileSystemException
{
    /**
     * @param string $method
     * @param string $message
     * @return DiskException
     */
    public static function PathError(string $method, string $message): self
    {
        return new self(sprintf('[%1$s]: %2$s', $method, $message));
    }
}