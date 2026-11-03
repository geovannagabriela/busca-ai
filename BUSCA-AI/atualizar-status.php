<?php

require_once "funcoes.php";

exigir_interno();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Método não permitido.");
}

if (!csrf_valido()) {
    http_response_code(403);
    exit("Sessão expirada. Volte à página da devolução e tente novamente.");
}

require_once "conexao.php";

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$novo_status = $_POST["status"] ?? "";

$etapas = [
    "Solicitação",
    "Em coleta",
    "Triagem",
    "Destinação",
    "Recuperação de valor"
];

if (!$id || $id < 1) {
    http_response_code(400);
    exit("ID de devolução inválido.");
}

if (!is_string($novo_status)) {
    http_response_code(400);
    exit("Status inválido.");
}

$indiceNovo = array_search($novo_status, $etapas, true);

if ($indiceNovo === false) {
    http_response_code(400);
    exit("Status inválido.");
}

$transacaoIniciada = false;

try {
    $conexao->begin_transaction();
    $transacaoIniciada = true;

    // Busca e bloqueia a devolução durante a alteração.
    $sql = "SELECT id, status
            FROM devolucoes
            WHERE id = ?
            FOR UPDATE";

    $stmt = preparar($conexao, $sql);

    $stmt->bind_param("i", $id);
    executar($stmt);

    $resultado = $stmt->get_result();
    $devolucao = $resultado->fetch_assoc();

    $stmt->close();

    if (!$devolucao) {
        throw new OutOfBoundsException("Devolução não encontrada.");
    }

    $statusAtual = $devolucao["status"];
    $indiceAtual = array_search($statusAtual, $etapas, true);

    if ($indiceAtual === false) {
        throw new RuntimeException("O status atual da devolução é inválido.");
    }

    // Só altera o banco quando o status realmente muda.
    if ($novo_status !== $statusAtual) {

        // Atualiza o status atual da devolução.
        $sql = "UPDATE devolucoes
                SET status = ?
                WHERE id = ?";

        $stmt = preparar($conexao, $sql);

        $stmt->bind_param("si", $novo_status, $id);
        executar($stmt);
        $stmt->close();

        // Ao voltar de etapa, remove os registros das etapas posteriores.
        if ($indiceNovo < $indiceAtual) {
            $etapasPosteriores = array_slice($etapas, $indiceNovo + 1);

            if (!empty($etapasPosteriores)) {
                $marcadores = implode(
                    ",",
                    array_fill(0, count($etapasPosteriores), "?")
                );

                $sql = "DELETE FROM historico_devolucoes
                        WHERE devolucao_id = ?
                        AND etapa IN ($marcadores)";

                $stmt = preparar($conexao, $sql);

                $tipos = "i" . str_repeat("s", count($etapasPosteriores));
                $parametros = array_merge([$tipos, $id], $etapasPosteriores);

                $referencias = [];

                foreach ($parametros as $chave => &$valor) {
                    $referencias[$chave] = &$valor;
                }
                unset($valor);

                call_user_func_array(
                    [$stmt, "bind_param"],
                    $referencias
                );

                executar($stmt);
                $stmt->close();
            }
        }

        // Confere se a etapa já possui registro no histórico.
        $sql = "SELECT id
                FROM historico_devolucoes
                WHERE devolucao_id = ?
                  AND etapa = ?
                LIMIT 1";

        $stmt = preparar($conexao, $sql);

        $stmt->bind_param("is", $id, $novo_status);
        executar($stmt);

        $resultado = $stmt->get_result();
        $etapaExiste = $resultado->num_rows > 0;

        $stmt->close();

        // Registra a etapa somente se ela ainda não existir.
        if (!$etapaExiste) {
            $sql = "INSERT INTO historico_devolucoes
                    (devolucao_id, etapa)
                    VALUES (?, ?)";

            $stmt = preparar($conexao, $sql);

            $stmt->bind_param("is", $id, $novo_status);
            executar($stmt);
            $stmt->close();
        }
    }

    $conexao->commit();
    $transacaoIniciada = false;

    header("Location: gerenciar-devolucao.php?id=" . $id);
    exit;

} catch (OutOfBoundsException $erro) {
    if ($transacaoIniciada) {
        $conexao->rollback();
    }

    http_response_code(404);
    exit("Devolução não encontrada.");

} catch (Throwable $erro) {
    if ($transacaoIniciada) {
        try {
            $conexao->rollback();
        } catch (Throwable $erroRollback) {
            error_log("Erro ao desfazer transação: " . $erroRollback->getMessage());
        }
    }

    error_log("Erro ao atualizar devolução: " . $erro->getMessage());

    http_response_code(500);
    exit("Não foi possível atualizar a devolução. Tente novamente.");
}