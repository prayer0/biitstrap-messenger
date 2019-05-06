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

use App\Server\MessageServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;


class ServerCommand extends ContainerAwareCommand
{
    public function __construct(){
        parent::__construct();
    }

    protected function configure()
    {
        $this
        // the name of the command (the part after "bin/console")
        ->setName('server')

        // the short description shown while running "php bin/console list"
        ->setDescription('Runs the server.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('$php biitstrap server -- runs the server.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit','1024M');

        $server = IoServer::factory(new HttpServer(
            new WsServer(
                new MessageServer($this->getContainer())
            )
        ), $this->getContainer()->getParameter('port'));

        $server->run();
    }
}