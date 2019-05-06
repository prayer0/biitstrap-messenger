<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;

class SetupCommand extends ContainerAwareCommand
{
    public function __construct(){
        parent::__construct();
    }

    protected function configure()
    {
        $this
        // the name of the command (the part after "bin/console")
        ->setName('setup')

        // the short description shown while running "php bin/console list"
        ->setDescription('Does initial things.. Execute before start everything.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('$ php biitstrap setup -- does initial things.. execute before start everything.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit','1024M');

        $store = new FlockStore(sys_get_temp_dir());
        $factory = new Factory($store);
        $lock = $factory->createLock('setup');

        if($lock->acquire())
        {
            // create db
            $command = $this->getApplication()->find("doctrine:database:create");
            $arguments = [
            ];
            $greetInput = new ArrayInput($arguments);
            $returnCode = $command->run($greetInput, $output);

            // create tables
            $command = $this->getApplication()->find("doctrine:schema:update");
            $arguments = [
                '--force'  => true,
            ];
            $greetInput = new ArrayInput($arguments);
            $returnCode = $command->run($greetInput, $output);

            // clear caches
            $command = $this->getApplication()->find("cache:clear");
            $arguments = [
                '--env'  => "prod",
            ];
        }
        else
        {
            $output->writeln('This command is already running in another process.');
        }
    }
}