<?php

namespace KAYO\Support;

use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;

/**
 * Add/replace all namespaces in a directory
 */
class NamsepaceReplacer
{
    /**
     * PHP native classes and interfaces.
     * @var array
     */
    protected $predefinedClasses;

    /**
     * Namespace to apply.
     * @var string
     */
    protected $namespace;

    /**
     * Existing namespace on the classes.
     * @var string
     */
    protected $oldNamespace;

    /**
     * Project path to look inside.
     * @var string
     */
    protected $path;

    /**
     * Regex to search for namespaces, interfaces, etc.
     * @var string
     */
    protected $searchPattern;

    /**
     * Regex replace pattern.
     * @var string
     */
    protected $replacePattern;

    /**
     * Files and directories to exclude from being replaced
     * @var array
     */
    protected $excludeFiles;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @param     string $namespace
     * @param     array\void $excludeFiles
     * @param     string\void $oldNamespace
     * @param     Filesystem\void $filesystem
     * @return    void
     */
    public function __construct($namespace, $path = '.', $excludeFiles = [], $oldNamespace = '', Filesystem $files = null)
    {
        $this->namespace = $namespace;
        $this->oldNamespace = $oldNamespace;
        $this->path = $path;
        $this->excludeFiles = $excludeFiles;
        $this->files = $files ?: (new Filesystem);

        $this->setPredefinedClasses();
        $this->setSearchPattern();
        $this->setReplacePattern();
    }

    /**
     * Run the replacement process.
     * @return void
     */
    public function run()
    {
        $files = $this->getPathFiles();

        foreach ($files as $file) {
            $this->replaceFileNamespace($file->getRealPath());
        }
    }

    /**
     * Replace namespace on a file
     *
     * @param     string $filePath
     * @return    void
     */
    protected function replaceFileNamespace($filePath)
    {
        $this->files->put(
            $filePath,
            preg_replace(
                $this->searchPattern,
                $this->replacePattern,
                $this->files->get($filePath)
            )
        );
    }

    /**
     * Get all file on the command path
     *
     * @return    array
     */
    protected function getPathFiles()
    {
        $files = Finder::create()
                    ->in($this->path)
                    ->exclude($this->excludeFiles)
                    ->name('*.php')
                    ->files();

        return $files;
    }

    /**
     * PHP native classes to ignore.
     * @return void
     */
    protected function setPredefinedClasses()
    {
        $predefinedClasses = [
            "Traversable",
            "Iterator",
            "IteratorAggregate",
            "Throwable",
            "ArrayAccess",
            "Serializable",
            "Closure",
            "Generator",
            "Directory",
            "stdClass",
            "Exception",
            "ErrorException",
            "Closure",
            "Countable",
            "ArrayIterator",
            "CachingIterator",
            "JsonSerializable",
            "InvalidArgumentException",
            "ArrayObject",
        ] + array_values(spl_classes());
        $predefinedClasses = implode("|", $predefinedClasses);
        $this->predefinedClasses = $predefinedClasses;
    }

    /**
     * searchPattern property setter
     *
     * @return    void
     */
    protected function setSearchPattern()
    {
        /*
            TODO: `use` keyword conflict with trait usage.
         */

        if (empty($this->oldNamespace)) {
            $this->searchPattern = [
                '/[^\$]namespace (\\\\?)'.$this->oldNamespace.'/',
                '/'.
                '[^\$]use \\\\?'. // match the `use` keyword and optionally may start with `\`
                '('. // start of a capturing group
                '(?!'.$this->predefinedClasses.')[a-zA-Z0-9]+'. // `?!` not in list, and should only contain numbers and letters
                ')'. // end of capturing group
                '/',
                '/(?:[^\$]new \\\\?)((?!'.$this->predefinedClasses.')[a-zA-Z0-9]+\\\\+)/U',
            ];
        } else {
            $this->searchPattern = [
                '/[^\$]namespace (\\\\?)'.$this->oldNamespace.'/',
                '/[^\$]use (\\\\?)'.$this->oldNamespace.'/',
                '/[^\$]new (\\\\?)'.$this->oldNamespace.'/U',
                // '/(\\\\?(?!ArrayAccess|Traversable)[a-zA-Z0-9]+\\\\?)+\\:\\:/'
            ];
        }
    }

    /**
     * searchPattern property getter
     *
     * @return    string
     */
    public function getSearchPattern()
    {
        return $this->searchPattern;
    }

    /**
     * replacePattern property setter
     *
     * @return    void
     */
    protected function setReplacePattern()
    {
        $this->replacePattern = [
            'namespace '.$this->namespace.'\\',
            'use '.$this->namespace,
            'new '.$this->namespace.' '
        ];
    }

    /**
     * replacePattern property getter
     *
     * @return    string
     */
    public function getReplacePattern()
    {
        return $this->replacePattern;
    }
}
