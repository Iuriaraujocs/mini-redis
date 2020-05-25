<?php

require_once 'StringHandle.php';
require_once 'Command.php';

class ClientHandle
{
    //comando
    private $cmd;
    //parâmetros do comando
    private $options = [];
    //armazenamento padrão para todas as instâncias
    private static $data = [];
    //armazenamento de informações de tempo de expiração de dados
    private static $dataExpired = [];
    //armazenamento para variáveis do comando z.
    private static $zData = [];

    public function command(string $command)
    {
        //verifica a string recebida e identifica o comando e os parâmetros
        list($this->cmd, $this->options) = StringHandle::getCmd($command);
        //result armazena a resposta do servidor para a solicitação
        $result = $this->verify();

        return $result;
    }

    private function verify(): string
    {
        //retira os dados expirados
        self::$dataExpired = array_filter(self::$dataExpired, array($this, 'ifExpired'), ARRAY_FILTER_USE_BOTH);

        //aplica os comandos
        switch ($this->cmd) {
            case 'SET':
                $result = $this->set();
                break;

            case 'GET':
                $result = self::$data[$this->options[0]] ?? '(nil)';
                if ($result != '(nil)') {
                    $result = '"'.$result.'"';
                }
                break;

            case 'DEL':
                list(self::$data, $result) = Command::delete(self::$data, $this->options);
                break;

            case 'DBSIZE':
                $result = Command::dbSize(self::$data, self::$zData);
                break;

            case 'INCR':
                list(self::$data[$this->options[0]], $result) = Command::incr(self::$data, $this->options[0]);
                break;

            case 'ZADD':
                list($setArray, $result) = Command::zAdd(self::$zData, $this->options);
                self::$zData[$this->options[0]] = $setArray;
                break;

            case 'ZCARD':
                $result = Command::zCard(self::$zData, $this->options[0]);
                break;

            case 'ZRANK':
                $result = Command::zRank(self::$zData, $this->options);
                break;

            case 'ZRANGE':
                $result = Command::zRange(self::$zData, $this->options);
                break;

            default:
                $result = "(Command not avaiable)\n";
        }

        return $result;
    }

    private function set()
    {
        $options = $this->options;
        list($expired, $result) = Command::set($options);
        if ($expired) {
            self::$data[$options[0]] = $options[1];
            self::$dataExpired[$options[0]] = time() + (int) $options[2];  //falta verificar se o segundo parametro é so numero
        } elseif ($result == 'Ok') {
            self::$data[$options[0]] = $options[1];
        }

        return $result;
    }

    private function ifExpired($var, $key = '')
    {
        if (!isset(self::$data[$key])) {
            return false;
        }   //se nao existir no normal, deleta
        $now = time();     //se $now for maior quer dizer que expirou
        $interval = $now - (int) $var;  //se positivo, expirou

        if ($interval < 0) {   //se nao tiver expirado retorna
            return true;
        } else {
            unset(self::$data[$key]);
        }
    }
}
