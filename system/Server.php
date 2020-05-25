<?php

require_once 'SocketHandle.php';

class Server extends SocketHandle
{
    public function __construct(string $uri)
    {
        parent::__construct($uri);
    }

    public function run(): void
    {
        while (true) {
            //a cada início de loop precisa-se atualizar os vetores de conexões
            $this->updateParams();

            // Em um looping infinito, a stream_select() retornará quantas streams foram modificadas,
            // a partir disso itera-se sobre elas (tanto as de escrita quanto de leitura), lendo ou escrevendo.
            // A stream_select() recebe os arrays por referência e ela os zera   (remove seus itens) até que uma stream muda de estado,
            // quando isso acontece, a stream_select() volta com essa stream para o array, é nesse momento que conseguimos iterar escrevendo ou lendo.
            if (stream_select($this->readable, $this->writable, $this->except, 0, 0) > 0) {
                //leitura do buffer
                $this->readStreams();
                //executa comando caso haja, e envia seu resultado
                $this->executeAndWrite();
                //finaliza as conexões
                $this->release();
            }
        }
    }
}
