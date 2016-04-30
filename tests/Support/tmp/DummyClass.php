<?php
namespace Kayo\League\Flysystem;

use InvalidArgumentException;
use League\Flysystem\Plugin\PluggableTrait;
use League\Flysystem\Util\ContentListingFormatter;

class Filesystem implements FilesystemInterface
{
    use PluggableTrait;
    use ConfigAwareTrait;
}