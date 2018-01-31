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
 * Class Privileges
 * @package Comely\IO\FileSystem\Disk
 */
class Privileges
{
    /** @var bool */
    public $read;
    /** @var bool */
    public $write;
    /** @var bool */
    public $execute;

    /**
     * Privileges constructor.
     * @param string $absolutePath
     */
    public function __construct(string $absolutePath)
    {
        // Remove trailing DIRECTORY_SEPARATOR
        $absolutePath = rtrim($absolutePath, DIRECTORY_SEPARATOR);

        // Check permission
        $this->read = is_readable($absolutePath);
        $this->write = is_writeable($absolutePath);
        $this->execute = is_executable($absolutePath);
    }
}