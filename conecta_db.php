<?php

function conecta_db() {
    $host   = 'localhost';
    $user   = 'root';
    $pass   = '';
    $dbname = 'bookington';

    $conexao = new mysqli($host, $user, $pass, $dbname);

    if ($conexao->connect_error) {
        return false;
    }

    $conexao->set_charset('utf8mb4');

    return $conexao;
}