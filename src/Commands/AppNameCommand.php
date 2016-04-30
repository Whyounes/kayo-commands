<?php

namespace KAYO\Commands;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Filesystem\Filesystem;
use KAYO\Support\Composer;
use KAYO\Support\NamsepaceReplacer;

class AppNameCommand extends SymfonyCommand
{
    /**
     * Configures the current command.
     * @return void
     */
    protected function configure()
    {
        $this->setName('appname');
        $this->setDescription('Set the application namespace.');

        $this->addArgument('newNamespace', InputArgument::REQUIRED, 'New namespace.');
        $this->addArgument('currentNamespace', InputArgument::OPTIONAL, 'Current namespace.');
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
        $newNamespace = $input->getArgument('newNamespace');
        $currentNamespace = $input->getArgument('currentNamespace') ?: '';
        $appPath = $input->getArgument('path') ?: '.';

        $replacer = new NamsepaceReplacer($newNamespace, $appPath, ['vendor'], $currentNamespace);
        $replacer->run();

        // $composer = new Composer(new Filesystem);
        // $composer->dumpAutoloads();

        $output->writeln("Application namespace set!");
    }
}
