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
 * Class PathException
 * @package Comely\IO\FileSystem\Exception
 */
class PathException extends DiskException
{
    /**
     * @param string $message
     * @param string $path
     * @return PathException
     */
    public static function OperationError(string $message, string $path) : self
    {
        return new self(
            sprintf('%s "%s" in directory "%s"', $message, basename($path), dirname($path) . DIRECTORY_SEPARATOR)
        );
    }
}