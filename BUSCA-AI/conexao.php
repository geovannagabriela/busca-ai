<?php

// Configuração compatível com XAMPP e hospedagem.
// No Render, defina DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASSWORD.
mysqli_report(MYSQLI_REPORT_OFF);

$servidor = getenv("DB_HOST") ?: "localhost";
$porta = (int) (getenv("DB_PORT") ?: 3306);
$usuario = getenv("DB_USER") ?: "root";
$senha = getenv("DB_PASSWORD") ?: "";
$banco = getenv("DB_NAME") ?: "busca_ai_novo";

$conexao = @new mysqli($servidor, $usuario, $senha, $banco, $porta);

if ($conexao->connect_errno) {
    error_log(
        "BUSCA AÍ - falha na conexão com o MySQL (" .
        $conexao->connect_errno . "): " . $conexao->connect_error
    );

    http_response_code(503);
    exit("Serviço temporariamente indisponível. A conexão com o banco de dados não está configurada ou está indisponível.");
}

$conexao->set_charset("utf8mb4");
