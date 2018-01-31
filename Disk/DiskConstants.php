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

/**
 * Interface DiskConstants
 * @package Comely\IO\FileSystem\Disk
 */
interface DiskConstants
{
    public const IS_UNKNOWN = 1000;
    public const IS_FILE = 1001;
    public const IS_DIR = 1002;
}