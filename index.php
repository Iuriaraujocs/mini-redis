<?php

/* Cliente miniRedis */
error_reporting(E_ALL);

/* Porta de comunicação com servidor miniRedis */
$service_port = '7080';

/* Endereco IP do servidor */
$address = '127.0.0.1';

/*  TCP/IP socket. */
$socket = socket_create(AF_INET, SOCK_STREAM, 0);
if ($socket === false) {
    echo 'socket_create() failed: reason: '.
         socket_strerror(socket_last_error())."\n";
}

$result = socket_connect($socket, $address, $service_port);
if ($result === false) {
    echo "socket_connect() failed.\nReason: ($result) ".
          socket_strerror(socket_last_error($socket))."\n";
}

$in = $_GET['cmd'] ?? '';

socket_write($socket, $in, strlen($in));

echo socket_read($socket, 2048);

socket_close($socket);
