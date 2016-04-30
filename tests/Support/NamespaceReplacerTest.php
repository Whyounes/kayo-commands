<?php

namespace KAYO\Support;

use Illuminate\Filesystem\Filesystem;

class NamespaceReplacerTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpDir;

    public function setUp()
    {
        $dummyClass = <<<STUB
<?php

namespace League\Flysystem;

use InvalidArgumentException;
use League\Flysystem\Plugin\PluggableTrait;
use League\Flysystem\Util\ContentListingFormatter;

class Filesystem implements FilesystemInterface
{
    use PluggableTrait;
    use ConfigAwareTrait;
}
STUB;
        $filesystem = new Filesystem;
        $this->tmpDir = __DIR__."/tmp";
        $filesystem->makeDirectory($this->tmpDir);
        $filesystem->put("{$this->tmpDir}/DummyClass.php", $dummyClass);
    }

    public function tearDown()
    {
        $filesystem = new Filesystem;
        // $filesystem->deleteDirectory($this->tmpDir);
    }

    public function testSearchPattern()
    {
        $dummyClassExpected = <<<STUB
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
STUB;
        $filesystem = new Filesystem;
        $replacer = new NamsepaceReplacer('Kayo', $this->tmpDir);
        $replacer->run();
        $this->assertEquals($filesystem->get("{$this->tmpDir}/DummyClass.php"), $dummyClassExpected);
    }
}