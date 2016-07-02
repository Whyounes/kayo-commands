<?php

namespace KAYO\Commands;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Str;

class TranslateCommand extends SymfonyCommand
{
    protected $fileSystem;

    protected $langDir;

    protected $langFile;

    protected $viewsPath;

    /**
     * @param Filesystem|void $filesystem
     *
     * @return string
     */
    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: (new Filesystem());

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('translate');
        $this->setDescription('Help translate language keys.');

        $this->addArgument('viewsPath', InputArgument::OPTIONAL, 'HTML views path.');
        $this->addArgument('langDir', InputArgument::OPTIONAL, 'Language directory.');
        $this->addArgument('langFile', InputArgument::OPTIONAL, 'Language file to use (defaults to `dashboard.php`)');
    }

    /**
     * langDir property setter.
     *
     * @param string $langDir
     */
    public function setLangDir($langDir)
    {
        $langDir = $langDir ?: __DIR__.'/languages/native/en_US/';
        if (!$this->filesystem->exists($langDir)) {
            throw new \Exception("Lang directory not found `{$langDir}`!");
        }

        $this->langDir = $langDir;
    }

    /**
     * langFile property setter.
     *
     * @param string $langFile
     */
    public function setLangFile($langFile)
    {
        $langFile = $langFile ?: 'dashboard.php';
        if (!$this->filesystem->exists($this->langDir.'/'.$langFile)) {
            throw new \Exception("Default lang file not found `{$this->langDir}/{$langFile}`!");
        }

        $this->langFile = $langFile;
    }

    /**
     * viewsPath property setter.
     *
     * @param string $viewsPath
     */
    protected function setViewsPath($viewsPath)
    {
        $viewsPath = $viewsPath ?: __DIR__.'/public/views';
        if (!$this->filesystem->exists($viewsPath)) {
            throw new \Exception("Views directory not found `{$viewsPath}`!");
        }

        $this->viewsPath = $viewsPath;
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setLangDir($input->getArgument('langDir'));
        $this->setLangFile($input->getArgument('langFile'));
        $this->setViewsPath($input->getArgument('viewsPath'));

        $langFileName = explode('.', $this->langFile)[0];
        $langFileContent = (array) @require $this->langDir.'/'.$this->langFile;
        $viewFiles = Finder::create()
                    ->in($this->viewsPath)
                    ->name('*.html')
                    ->name('*.php')
                    ->files();

        foreach ($viewFiles as $file) {
            $fileContent = $this->filesystem->get($file->getRealPath());
            preg_match_all(
                '/H::trans\(["|\']([^\'"]*)["|\']\)/',
                $fileContent,
                $matches,
                PREG_SET_ORDER
            );

            if ($matches !== false) {
                foreach ($matches as $match) {
                    $firstMatch = trim($match[1]);

                    if ($firstMatch == '') {
                        continue;
                    }
                    if ($this->langContainVariable($firstMatch)) {
                        echo sprintf("Skipped variable inside a key `%s` in file `%s`.%s", $firstMatch, $file->getRealPath(), PHP_EOL);
                        continue;
                    }

                    if (strpos($firstMatch, ' ') == false && strpos($firstMatch, '.') > 0) {
                        $this->transMatchExistentKey($firstMatch);
                    } else {
                        $langKey = Str::slug($firstMatch);

                        if (
                            isset($langFileContent[$langKey]) &&
                            $langFileContent[$langKey] !== $firstMatch
                        ) {
                            $langKey = $langKey . '-' . time();
                            echo $langKey.' - '.$firstMatch.PHP_EOL;
                        }

                        $langFileContent[$langKey] = $firstMatch;
                        $fileContent = preg_replace(
                            '/H::trans\(["|\']'.$firstMatch.'["|\']\)/',
                            "H::trans('".$langFileName.'.'.$langKey."')",
                            $fileContent
                        );
                    }
                }

                $this->filesystem->put($file->getRealPath(), $fileContent);
            }
        }

        $this->filesystem->put(
            "{$this->langDir}/{$this->langFile}",
            $this->buildLangFileContent($langFileContent, true, true)
        );

        $output->writeln('Done translating!');
    }

    /**
     * Test if the language key contain a PHP variable.
     *
     * @param     string $langKey
     * @return    bool
     */
    protected function langContainVariable($langKey)
    {
        preg_match_all(
            '/\$[a-z_]\w*/',
            $langKey,
            $matches,
            PREG_SET_ORDER
        );

        return !!$matches;
    }

    /**
     * Regex match value is a lang key. This method will verify
     * and add the new key if necessary.
     *
     * @param array $match
     */
    protected function transMatchExistentKey($match)
    {
        list($matchLang, $matchLangKey) = explode('.', $match);
        $matchLangFilename = $this->langDir.'/'.$matchLang.'.php';

        $matchLangFileContent = [];
        if ($this->filesystem->exists($matchLangFilename)) {
            $matchLangFileContent = (array) @require $this->langDir.'/'.$matchLang.'.php';
        }

        if (!isset($matchLangFileContent[$matchLangKey])) {
            $matchLangFileContent[$matchLangKey] = '';
        }

        $this->filesystem->put($matchLangFilename, $this->buildLangFileContent($matchLangFileContent, true, true));
    }

    /**
     * Create the literal string to be stroed from an array.
     *
     * @param array $content
     * @param bool  $fillValues Whether to keep values or add them empty.
     * @param bool  $tag        Add PHP tags around array content.
     *
     * @return string
     */
    protected function buildLangFileContent(array $content, $fillValues = false, $tag = false)
    {
        if ($tag) {
            $str = "<?php\nreturn [\n\t";
        } else {
            $str = "[\n\t";
        }

        foreach ($content as $key => $value) {
            if (is_array($value)) {
                $value = $this->buildLangFileContent($value, $fillValues);
                $str .= "\t'{$key}' => {$value},\n";
            } else {
                if ($fillValues) {
                    $value = stripslashes($value);
                    $value = addcslashes($value, "'");
                } else {
                    $value = '';
                }

                $str .= "\t'{$key}' => '{$value}',\n";
            }
        }
        if ($tag) {
            $str .= '];';
        } else {
            $str .= ']';
        }

        return $str;
    }
}
