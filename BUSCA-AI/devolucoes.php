<?php
require_once "funcoes.php";

// O identificador vem da sessão da empresa autenticada (antes era fixo: 1).
$empresa_id = exigir_empresa();

require_once "conexao.php";

try {
    $stmt = preparar(
        $conexao,
        "SELECT id, numero_pedido, produto, data_solicitacao, status
         FROM devolucoes
         WHERE empresa_id = ?
         ORDER BY data_solicitacao DESC"
    );
    $stmt->bind_param("i", $empresa_id);
    executar($stmt);
    $devolucoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Throwable $erro) {
    error_log("Erro ao consultar devoluções: " . $erro->getMessage());

    http_response_code(500);
    exit("Não foi possível carregar suas devoluções. Tente novamente.");
}

inicio_documento("BUSCA AÍ - Devoluções");
cabecalho_pagina(links_empresa(), "dashboard.php");
?>

<main class="pagina-devolucoes">

    <section class="devolucoes-topo">
        <span class="etiqueta">MINHAS DEVOLUÇÕES</span>

        <h1>Devoluções</h1>

        <p>
            Acompanhe todas as devoluções solicitadas pela sua empresa.
        </p>
    </section>

    <section class="lista-devolucoes">

        <div class="cabecalho-lista">
            <span>Pedido</span>
            <span>Produto</span>
            <span>Data</span>
            <span>Status</span>
            <span></span>
        </div>

        <?php if (count($devolucoes) > 0) { ?>

            <?php foreach ($devolucoes as $devolucao) { ?>

                <div class="linha-devolucao">

                    <div data-rotulo="Pedido">
                        <strong>#<?= escapar($devolucao["numero_pedido"]) ?></strong>
                    </div>

                    <div data-rotulo="Produto">
                        <span><?= escapar($devolucao["produto"]) ?></span>
                    </div>

                    <div data-rotulo="Data">
                        <span><?= escapar(formatar_data($devolucao["data_solicitacao"], "d/m/Y")) ?></span>
                    </div>

                    <div data-rotulo="Status">
                        <?= etiqueta_status((string) $devolucao["status"]) ?>
                    </div>

                    <div>
                        <a href="acompanhar-devolucao.php?id=<?= (int) $devolucao["id"] ?>">
                            Acompanhar
                        </a>
                    </div>

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
