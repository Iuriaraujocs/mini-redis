<?php

//classe que realiza os comandos propriamente dito
class Command
{
    //seta uma chave com valores
    //duas formas são possíveis, a padrão e o com tempo para expirar
    public static function set(array $options)
    {
        $data = [];
        $dataExpired = [];
        $lenght = count($options);

        //verifica se o número mínimo de parametros estão corretos
        if ($lenght <= 1) {
            return [false, '(nil)'];
        } elseif ($lenght == 2) {
            return [false, 'Ok'];
        } else {     //existe parâmetro para expirar
            $options[1];
            if (is_numeric($options[2])) {
                $dataExpired = time() + (int) $options[2];

                return [$dataExpired, 'Ok'];
            } else {
                return [false, '(nil)'];
            }
        }
    }

    //deleta as chaves do banco
    public static function delete(array $data, $keys)
    {
        $delete = false;
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
                $delete = true;
            }
        }
        if ($delete) {
            return [$data, '(integer) 1'];
        }

        return [$data, '(integer) 0'];
    }

    //contabiliza quantos atributos estão armazenados no banco de dados
    public static function dbSize(array $data, array $zdata)
    {
        return '(integer) '.(count($data) + count($zdata));
    }

    //incrementa em um o valor do atributo correspondente
    public static function incr(array $data, $key)
    {
        $number = $data[$key];
        if (is_numeric($number)) {
            return [++$number, '(integer) '.$number];
        } else {
            return [$number, '0'];
        }
    }

    //adiciona uma lista de elementos com seus respectivos scores em uma chave
    public static function zAdd(array $data, array $options)
    {
        //checa se existe os parâmetros
        if (empty($options)) {
            return [[], '(Missing Parameters)'];
        }
        $myset = $options[0];

        //se não existir ainda este elemento, cria um
        if (!is_array($data[$myset])) {
            $data[$myset] = [];
        }
        $mySetArray = $data[$myset];
        for ($i = 1; $i < count($options); $i += 2) {
            $score = $options[$i];
            $element = $options[$i + 1];
            if (!self::verifyFormatAdd($score)) {
                return [[], '(Bad Parameters)'];
            }
            if (empty($element)) {
                return [[], '(Missing Parameters)'];
            }
            $mySetArray[$element] = $score;
        }

        return [$mySetArray, 'OK'];
    }

    //verifica quantos elementos há em uma chave do tipo z.
    public static function zCard(array $data, $myset)
    {
        if (isset($data[$myset]) and is_array($data[$myset])) {
            return  '(integer) '.count($data[$myset]);
        } else {
            return 0;
        }
    }

    //verifica a posição do elemento na chave de formato z
    public static function zRank(array $zData, array $options)
    {
        if (count($options) <= 1) {
            return '(nil)';
        }

        $keySet = $options[0];
        $keyElement = $options[1];

        $myset = $zData[$keySet] ?? null;
        if ($myset) {
            if (isset($myset[$keyElement])) {
                $return = $myset[$keyElement] ?? '(nil)';
                if ($return != '(nil)') {
                    $return = '(integer) '.($return - 1);

                    return $return;
                }
            }

            return '(nil)';
        }   //element
        return '(nil)';
    }

    //retorna a chave em z de acordo com sua ordem em um range específico
    public static function zRange(array $zData, array $options)
    {
        //verifica se o número de parâmetros estão corretos
        if (count($options) <= 2) {
            return '(nil)';
        }
        $keySet = $options[0];
        $from = (int) $options[1];
        $to = (int) $options[2];

        $myset = $zData[$keySet] ?? null;
        if ($myset) {
            //obtem o array no range especificado
            $myset = self::getRangeFromArray($myset, $from, $to);
            //ordena o array primeiramente pelo score, e caso haja empate, estes por ordem alfabética
            array_multisort(array_values($myset), SORT_ASC, array_keys($myset), SORT_ASC, $myset);
            $result = self::formatRange($myset);

            return $result;
        }

        return '(nil)';
    }

    //obtem o array no range especificado
    private static function getRangeFromArray(array $array, int $from, int $to)
    {
        $auxValues = array_values($array);
        $auxKeys = array_keys($array);

        if ($to < 0) {
            $to = count($array) + ($to + 1);
        }
        if ($from < 0) {
            $to = count($array) + ($from + 1);
        }   //vira tox

        $length = $to - $from + 1;
        if ($length < 0) {
            $length = $length * (-1);
            $from = $to;
        }

        $keys = array_splice($auxKeys, $from, $length, true);
        $values = array_splice($auxValues, $from, $length, true);
        $arrayCuted = array_combine($keys, $values);

        return $arrayCuted;
    }

    //formata para a saída padrão de zRange
    private static function formatRange(array $array)
    {
        $result = '';
        $scores = array_keys($array);

        for ($i = 0; $i < count($scores); ++$i) {
            $j = $i + 1;
            $result .= "\n".$j.') '.'"'.$scores[$i].'"';
        }

        return $result;
    }

    private static function verifyFormatAdd($value)
    {
        if (is_numeric($value) and $value >= 0) {
            return true;
        }

        return false;
    }
}
