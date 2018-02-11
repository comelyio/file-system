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
 * Class Paths
 * @package Comely\IO\FileSystem\Disk
 */
abstract class Paths
{
    /**
     * @param string $path
     * @param Directory|null $dir
     * @param null|string $allowedChars
     * @return string
     * @throws PathException
     */
    public static function Absolute(string $path, ?Directory $dir = null, ?string $allowedChars = null): string
    {
        // Path with in a directory?
        if ($dir) {
            $path = $dir->suffixed($path);
        }

        // Validate and sanitize path
        $path = self::Validate($path, $allowedChars);

        // Get absolute path (resolve any symbolic link, etc..)
        $absolute = realpath($path);
        if (!$absolute) {
            throw new PathException(
                sprintf('Could not resolve absolute path to "%s"', $path),
                PathException::NON_EXISTENT
            );
        }

        return $absolute;
    }

    /**
     * @param string $path
     * @param null|string $allowedChars
     * @return string
     * @throws PathException
     */
    public static function Validate(string $path, ?string $allowedChars = null): string
    {
        $path = trim($path);
        if (!preg_match(sprintf('#^[\w%s]{4,}$#', preg_quote('/\_.:-' . $allowedChars, '#')), $path)) {
            throw new PathException('Given path contains an illegal character');
        }

        // Check for illegal references
        if (preg_match('#(\/|\\\)\.{1,2}(\/|\\\)#', $path)) {
            throw new PathException('Given path contains an illegal reference character');
        }

        // Remove any extra directory separators
        return preg_replace(sprintf('#%s{2,}#', preg_quote(DIRECTORY_SEPARATOR)), DIRECTORY_SEPARATOR, $path);
    }
}