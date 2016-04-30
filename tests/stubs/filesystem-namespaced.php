<?php

namespace Kayo\League\Flysystem;

use InvalidArgumentException;
use Kayo\League\Flysystem\Plugin\PluggableTrait;
use Kayo\League\Flysystem\Util\ContentListingFormatter;

class Filesystem implements FilesystemInterface
{
    use PluggableTrait;
    use ConfigAwareTrait;
}