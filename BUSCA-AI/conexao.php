<?php

// Conexão central com o MySQL (XAMPP). O banco "busca_ai" e seus dados não são alterados aqui.
mysqli_report(MYSQLI_REPORT_OFF);

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "busca_ai_novo";

// O "@" evita que o aviso do PHP exiba detalhes da conexão na página.
$conexao = @new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_errno) {
    // Detalhes técnicos ficam apenas no log do PHP (xampp/apache/logs/error.log).
    error_log(
        "BUSCA AÍ - falha na conexão com o MySQL (" .
        $conexao->connect_errno . "): " . $conexao->connect_error
    );

    http_response_code(503);
    exit("Serviço temporariamente indisponível. Tente novamente em instantes.");
}

$conexao->set_charset("utf8mb4");
