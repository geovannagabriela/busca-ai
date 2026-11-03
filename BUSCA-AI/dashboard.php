<?php
require_once "funcoes.php";

$empresa_id = exigir_empresa();
$nome_empresa = $_SESSION["empresa_nome"] ?? "Empresa";

require_once "conexao.php";

// Conta devoluções da empresa, opcionalmente filtrando pelo status atual.
function contar_devolucoes(mysqli $conexao, int $empresa_id, ?string $status = null): int
{
    if ($status === null) {
        $stmt = preparar(
            $conexao,
            "SELECT COUNT(*) AS total FROM devolucoes WHERE empresa_id = ?"
        );
        $stmt->bind_param("i", $empresa_id);
    } else {
        $stmt = preparar(
            $conexao,
            "SELECT COUNT(*) AS total FROM devolucoes WHERE empresa_id = ? AND status = ?"
        );
        $stmt->bind_param("is", $empresa_id, $status);
    }

    executar($stmt);
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($linha["total"] ?? 0);
}

try {
    $total_devolucoes = contar_devolucoes($conexao, $empresa_id);
    $devolucoes_coleta = contar_devolucoes($conexao, $empresa_id, "Em coleta");
    $devolucoes_triagem = contar_devolucoes($conexao, $empresa_id, "Triagem");
    $devolucoes_recuperacao = contar_devolucoes($conexao, $empresa_id, "Recuperação de valor");

    $stmt = preparar(
        $conexao,
        "SELECT numero_pedido, produto, status
         FROM devolucoes
         WHERE empresa_id = ?
         ORDER BY data_solicitacao DESC
         LIMIT 5"
    );
    $stmt->bind_param("i", $empresa_id);
    executar($stmt);
    $ultimas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Throwable $erro) {
    error_log("Erro ao carregar dashboard: " . $erro->getMessage());

    http_response_code(500);
    exit("Não foi possível carregar o dashboard. Tente novamente.");
}

inicio_documento("Dashboard - BUSCA AÍ");
cabecalho_pagina(links_empresa(), "dashboard.php");
?>

<main class="dashboard">

    <section class="boas-vindas">
        <h1>Olá, <?= escapar($nome_empresa) ?>!</h1>
        <p>Acompanhe suas devoluções e solicitações.</p>
    </section>

    <section class="resumo">

        <div class="card-resumo">
            <h3>Solicitações</h3>
            <strong><?= $total_devolucoes ?></strong>
            <p>Total de devoluções</p>
        </div>

        <div class="card-resumo">
            <h3>Em coleta</h3>
            <strong><?= $devolucoes_coleta ?></strong>
            <p>Devoluções em coleta</p>
        </div>

        <div class="card-resumo">
            <h3>Em triagem</h3>
            <strong><?= $devolucoes_triagem ?></strong>
            <p>Produtos em análise</p>
        </div>

        <div class="card-resumo">
            <h3>Recuperação de valor</h3>
            <strong><?= $devolucoes_recuperacao ?></strong>
            <p>Devoluções nessa etapa</p>
        </div>

    </section>

    <section class="ultimas-devolucoes">

        <div class="titulo-secao">
            <h2>Últimas devoluções</h2>
            <a href="devolucoes.php">Ver todas</a>
        </div>

        <?php if (count($ultimas) > 0) { ?>

            <?php foreach ($ultimas as $devolucao) { ?>

                <div class="item-devolucao">
                    <div>
                        <strong>#<?= escapar($devolucao["numero_pedido"]) ?></strong>
                        <span><?= escapar($devolucao["produto"]) ?></span>
                    </div>

                    <?= etiqueta_status((string) $devolucao["status"]) ?>
                </div>

            <?php } ?>

        <?php } else { ?>

            <p class="estado-vazio">Nenhuma devolução cadastrada até o momento.</p>

        <?php } ?>

    </section>

    <a href="solicitar-devolucao.php" class="botao-devolucao">
        + Solicitar nova devolução
    </a>

</main>

<?php fim_documento(); ?>
