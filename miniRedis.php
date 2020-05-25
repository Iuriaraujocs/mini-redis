<?php

/*Inicializacao do servidor miniRedis
	** Autor: Iuri Cardoso Araújo
    							*****/
declare(strict_types=1);

require_once __DIR__.'/system/Server.php';

error_reporting(E_ERROR | E_PARSE);

$address = '127.0.0.1';
$port = '7080';
$server = new Server("tcp://{$address}:{$port}");
$server->run();
