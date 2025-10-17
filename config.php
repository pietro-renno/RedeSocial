<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'rede_social';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    die("ERRO: Não foi possível conectar ao banco de dados. " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");