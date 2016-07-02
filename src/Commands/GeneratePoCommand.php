<?php

namespace KAYO\Commands;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Filesystem\Filesystem;
use KAYO\Support\Composer;
use KAYO\Support\NamsepaceReplacer;

class GeneratePoCommand extends SymfonyCommand
{
    protected $fileSystem;

    /**
     * @param Filesystem|void $filesystem
     *
     * @return string
     */
    public function __construct(Filesystem $fileSystem = null)
    {
        $this->fileSystem = $fileSystem ?: (new Filesystem);

        parent::__construct();
    }

    /**
     * Configures the current command.
     * @return void
     */
    protected function configure()
    {
        $this->setName('generatepo');
        $this->setDescription('Generate Wordpress PO file from lang files.');

        $this->addArgument('langPath', InputArgument::OPTIONAL, 'Languages path');
        $this->addArgument('savePath', InputArgument::OPTIONAL, 'Save files to path');
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
        $langPath = $input->getArgument('langPath') ?: __DIR__.'/languages/native';
        $savePath = $input->getArgument('savePath') ?: $langPath;

        $langPath = realpath($langPath);
        $savePath = realpath($savePath);

        $langDirs = $this->fileSystem->directories($langPath);

        foreach ($langDirs as $langDir) {
            $files = $this->fileSystem->files($langDir);

            foreach ($files as $file) {
                $fileContent = $this->generateFileContent($file);

                $this->fileSystem->put($savePath . "/" . basename($langDir) . ".po", $fileContent);
            }
        }

        $output->writeln("Application languages generated set!");
    }

    protected function generateFileContent($file) {
        $fileContent = "Project-Id-Version: {plugin name}\n".
                            "Content-Type: text/plain; charset=UTF-8\n".
                            "X-Poedit-SourceCharset: utf-8\n".
                            "X-Poedit-KeywordsList: __;_e;__ngettext:1,2;_c\n".
                            "X-Poedit-Basepath: ../..\n".
                            "Plural-Forms: nplurals=1; plural=0;\n".
                            "X-Poedit-SearchPath-0: {plugin name}\n";
        $fileContent .= "#\n";
        $fileContent .= "msgid \"\"\n";
        $fileContent .= "msgstr \"\"\n";

        $langKeys = @require_once $file;

        if (!is_array($langKeys)) {
            return $fileContent;
        }

        $fileContent .= $this->langElementToString($langKeys, basename($file, ".php"));

        return $fileContent;
    }

    protected function langElementToString(array $elements, $prefix = "") {
        $fileContent = "";

        foreach ($elements as $key => $value) {
            $key = addslashes($prefix . "." . $key);

            if (is_array($value)) {
                $value .= $this->langElementToString($value, $key);
            } else {
                $value = addslashes($value);
            }

            $fileContent .= "\n";
            $fileContent .= "msgid \"$key\"\n";
            $fileContent .= "msgstr \"$value\"\n";
        }

        return $fileContent;
    }
}
