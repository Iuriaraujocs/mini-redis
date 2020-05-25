<?php

//classe que verifica a string recebida pelo servidor, e identifica o comando e os parâmetros
class StringHandle
{
    private static $cmd = '';
    private static $options = [];
    private static $optionsAux = [];

    public static function getCmd(string $string)
    {
        //identifica e retorna o comando redis
        self::$cmd = self::getMainCmd($string);
        //identifica e retorna os parâmetros do comando
        self::$options = self::getOptions();

        return [self::$cmd, self::$options];
    }

    public static function getMainCmd(string $command)
    {
        $command = trim($command);
        $aux = explode(' ', $command);
        $cmd = strtoupper($aux[0]);
        unset($aux[0]);
        self::$optionsAux = $aux;

        return $cmd;
    }

    public static function getOptions()
    {
        /**os valores das variaveis podem conter espaço caso estejam dentro de aspas duplas*/
        $options = [];
        $optionsAux = implode(' ', self::$optionsAux);
        $pattern = '/"(.*?)"/';   //padrão de identificação de aspas duplas

        preg_match_all($pattern, $optionsAux, $matches);
        /*troca os valores encontrados contidos em aspas duplas para uma variável genérica sem espaços,
          para assim poder da um explode com backspace como caractere identificador e identificar todos
          os parâmetros corretamente*/
        foreach ($matches[0] as $match) {
            $optionsAux = str_replace($match, 'stringVar', $optionsAux);
        }

        $aux2 = explode(' ', $optionsAux);
        //troca-se a variável genérica pelo seu respectivo valor
        foreach ($aux2 as $option) {
            if ($option == 'stringVar') {
                $options[] = current($matches[1]);
                next($matches[1]);
            } else {
                $options[] = $option;
            }
        }

        return $options;
    }
}
