<?php

require_once 'ClientHandle.php';

//classe que abstrai abstração das funções do servidor
class SocketHandle
{
    protected $except;
    protected $server;

    protected $buffers = [];
    protected $writable = [];
    protected $readable = [];
    protected $connections = [];

    protected $client = [];

    public function __construct(string $uri)
    {
        //cria um socket de acordo com a porta e ip address em questão e coloca em modo de listening
        $this->server = stream_socket_server($uri);
        //set_blocking false significa que não haverá bloqueio no socket,
        //e portanto poderá multiplas conecções sem travar
        stream_set_blocking($this->server, false);

        //caso haja erro na criação do socket
        if ($this->server === false) {
            exit(1);
        } else {
            fwrite(STDERR, sprintf("Listening on: %s\n", stream_socket_get_name($this->server, false)));
        }
    }

    //atualiza os parâmetros no loop de read and write
    protected function updateParams()
    {
        $this->except = null;
        $this->writable = $this->connections;
        $this->readable = $this->connections;

        // Adiciona a stream do servidor no array de streams de somente leitura,
        // para que consigamos aceitar novas conexões quando disponíveis;
        $this->readable[] = $this->server;
    }

    protected function readStreams(): void
    {
        foreach ($this->readable as $stream) {
            // Se essa $stream é a do servidor, então uma nova conexão precisa ser aceita;
            if ($stream === $this->server) {
                $this->acceptConnection($stream);
                continue;
            }

            // Uma stream é um resource, tipo especial do PHP,
            // quando aplicamos um casting de inteiro nela, obtemos o id desse resource;
            $key = (int) $stream;

            // Armazena no nosso array de buffer os dados recebidos;
            if (isset($this->buffers[$key])) {
                $this->buffers[$key] .= fread($stream, 10000);
            } else {
                $this->buffers[$key] = '';
            }
        }
    }

    protected function executeAndWrite(): void
    {
        foreach ($this->writable as $stream) {
            $key = (int) $stream;
            $buffer = $this->buffers[$key] ?? null;

            //verifica se o usuário quer logout, e o realiza
            if ($buffer && $buffer !== '') {
                if ($this->logoutif($key, $buffer)) {
                    continue;
                }
                //executa o comando e envia a resposta
                $this->execWrite($stream, $key, $buffer);
            }
        }
    }

    protected function release(): void
    {
        foreach ($this->connections as $key => $connection) {
            // Quando uma conexão é fechada, ela entra no modo EOF (end-of-file),
            // usamos a feof() pra verificar esse estado e então devidamente executar fclose().
            if (feof($connection)) {
                fwrite(STDERR, sprintf("Client [%s] closed the connection; \n", stream_socket_get_name($connection, true)));

                fclose($connection);
                unset($this->connections[$key]);
            }
        }
    }

    //verifica novas conexões aceitas
    protected function acceptConnection($stream): void
    {
        $connection = stream_socket_accept($stream, 0, $clientAddress);

        if ($connection) {
            stream_set_blocking($connection, false);
            $key = (int) $connection;
            $this->connections[$key] = $connection;
            $this->client[$key] = new ClientHandle();

            fwrite(STDERR, sprintf("Client [%s] connected; \n", $clientAddress));
        }
    }

    //finaliza a conexão caso o usuário queira.
    private function logoutif($key, $buffer)
    {
        $buffer = trim($buffer);
        //se buffer valer um dos valores de saída
        if (in_array($buffer, ['quit', 'logout', 'out', 'exit', '0'])) {
            fwrite(STDERR, sprintf("Client [%s] closed the connection; \n", stream_socket_get_name($this->connections[$key], true)));
            fclose($this->connections[$key]);
            unset($this->connections[$key]);
            unset($this->client[$key]);

            return true;
        }

        return false;
    }

    //executa o comando e escreve no stream seu resultado.
    private function execWrite($stream, $key, $buffer)
    {
        $result = $this->client[$key]->command($buffer);
        $bytesWritten = fwrite($stream, "{$result}\n", 2048);
        // Imediatamente remove do buffer a parte que foi escrita;
        $this->buffers[$key] = '';
    }
}
