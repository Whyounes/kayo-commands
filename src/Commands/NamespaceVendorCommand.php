<?php

namespace KAYO\Commands;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use KAYO\Support\NamsepaceReplacer;
use Illuminate\Filesystem\Filesystem;

class NamespaceVendorCommand extends SymfonyCommand
{
    /**
     * Configures the current command.
     * @return void
     */
    protected function configure()
    {
        $this->setName('nsvendor');
        $this->setDescription('Namespace vendor directory.');
        $this->addArgument('namespace', InputArgument::REQUIRED, 'Namespace to use.');
        $this->addArgument('path', InputArgument::OPTIONAL, 'Your project path. (defaults to current directory)');
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
        $namespace = $input->getArgument('namespace');
        $path = $input->getArgument('path') ?: '.';
        $vendorPath = $path . DIRECTORY_SEPARATOR . "vendor";

        $replacer = new NamsepaceReplacer($namespace, $vendorPath, ['composer']);
        $replacer->run();

        $output->writeln("Namespace is set!");
    }
}
