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
 * Class PathInfo
 * @package Comely\IO\FileSystem\Disk
 */
class PathInfo
{
    /** @var null|string */
    public $path;
    /** @var null|string */
    public $parent;


    /**
     * PathInfo constructor.
     * @param string $path
     * @param AbsolutePath|null $parentPath
     * @param string|null $allowedChars
     * @throws PathException
     */
    public function __construct(string $path, AbsolutePath $parentPath = null, string $allowedChars = null)
    {
        // Pattern check
        $this->path = trim($path);
        if (!preg_match(sprintf('#^[\w%s]{4,}$#', preg_quote('/\_.:-' . $allowedChars, '#')), $this->path)) {
            throw new PathException('Given path contains an illegal character');
        }

        // Check for illegal references
        if (preg_match('#(\/|\\\)\.{1,2}(\/|\\\)#', $this->path)) {
            throw new PathException('Given path contains an illegal reference character');
        }

        // Join parent and given paths
        // Parent path feed by Disk component always has trailing DIRECTORY_SEPARATOR
        // Also, remove unnecessary/multiple slashes from path
        if ($parentPath) {
            $this->parent = $parentPath->path();
            $this->path = $parentPath->suffixed($this->path);
        }

        $this->path = preg_replace(
            '#' . preg_quote(DIRECTORY_SEPARATOR) . '{2,}#',
            DIRECTORY_SEPARATOR,
            $this->path
        );
    }


    /**
     * @param bool $clearStatCache
     * @return AbsolutePath
     */
    public function getAbsolute(bool $clearStatCache = true): AbsolutePath
    {
        return new AbsolutePath($this, $clearStatCache);
    }

    /**
     * @return bool
     */
    public function isLink(): bool
    {
        return is_link($this->path);
    }
}