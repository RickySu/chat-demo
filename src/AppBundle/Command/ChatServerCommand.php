<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use PHPSocketIO\SocketIO;
use PHPSocketIO\Connection;
use PHPSocketIO\Response\Response;
use PHPSocketIO\Event;

class ChatServerCommand extends ContainerAwareCommand
{

    /**
     *
     * @var SocketIO
     */
    protected $socket;

    protected function configure()
    {
        $this->setName('socketio:server:run')
                ->setDescription('start socket io server.')
                ->addOption('listen', '-l', InputOption::VALUE_OPTIONAL, 'host:port')
                ->setHelp("<info>php app/console socketio:server:run</info>");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!class_exists('Event')){
            dl('event.so');
        }

        $listen = $input->getOption('listen');
        if($listen === null){
            $listen = 8080;
        }
        $this->socket = new SocketIO();
        $this->socket
            ->on('addme', function(Event\MessageEvent $messageEvent) {
                return $this->onAddme($messageEvent);
            })
            ->on('msg', function(Event\MessageEvent $messageEvent){
                return $this->onMsg($messageEvent);
            });
        $this->socket
            ->listen($listen)
            ->onConnect(function(){})
            ->onRequest('/brocast', function($connection, \EventHttpRequest $request) {
                if($request->getCommand() == \EventHttpRequest::CMD_POST){
                    if(($data = json_decode($request->getInputBuffer()->read(1024), true)) !== NULL){
                        $response = $this->onBrocast($data);
                    }
                    else{
                        $response = new Response('fail', Response::HTTP_NOT_FOUND);
                    }
                }
                else{
                    $response = new Response('fail', Response::HTTP_NOT_FOUND);
                }
                $connection->sendResponse($response);
            })
            ->dispatch();
    }

    protected function onBrocast($data)
    {
        $chat = $this->socket->getSockets();
        $chat->emit($data['type'], $data);
        $response = new Response(json_encode(array(
            'status' => true,
        )));
        return $response;
    }

}
