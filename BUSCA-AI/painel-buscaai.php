<?php
require_once "funcoes.php";

exigir_interno();

require_once "conexao.php";

try {
    $resultado = $conexao->query(
        "SELECT devolucoes.*, empresas.nome_empresa
         FROM devolucoes
         INNER JOIN empresas
             ON devolucoes.empresa_id = empresas.id
         ORDER BY devolucoes.data_solicitacao DESC"
    );

    if (!$resultado) {
        throw new RuntimeException("Falha ao consultar devoluções: " . $conexao->error);
    }
} catch (Throwable $erro) {
    error_log("Erro ao consultar devoluções: " . $erro->getMessage());

    http_response_code(500);
    exit("Não foi possível carregar as devoluções. Tente novamente.");
}

inicio_documento("BUSCA AÍ - Painel interno");
cabecalho_pagina(links_interno(), "painel-buscaai.php");
?>

<main class="painel-buscaai">

    <section class="painel-topo">

        <span class="etiqueta">
            ÁREA INTERNA
        </span>

        <h1>
            Painel de devoluções
        </h1>

        <p>
            Gerencie as devoluções solicitadas pelas empresas.
        </p>

    </section>

    <section class="lista-painel">

        <div class="cabecalho-painel">
            <span>Pedido</span>
            <span>Empresa</span>
            <span>Produto</span>
            <span>Data</span>
            <span>Status</span>
            <span></span>
        </div>

        <?php if ($resultado->num_rows === 0) { ?>

            <p class="estado-vazio">Nenhuma devolução cadastrada.</p>

        <?php } else { ?>

            <?php while ($devolucao = $resultado->fetch_assoc()) { ?>

                <div class="linha-painel">

                    <div data-rotulo="Pedido">
                        <strong>
                            #<?php echo escapar($devolucao["numero_pedido"]); ?>
                        </strong>
                    </div>

                    <div data-rotulo="Empresa">
                        <?php echo escapar($devolucao["nome_empresa"]); ?>
                    </div>

                    <div data-rotulo="Produto">
                        <?php echo escapar($devolucao["produto"]); ?>
                    </div>

                    <div data-rotulo="Data">
                        <?php echo escapar(formatar_data($devolucao["data_solicitacao"] ?? null)); ?>
                    </div>

                    <div data-rotulo="Status">
                        <?= etiqueta_status((string) $devolucao["status"]) ?>
                    </div>

                    <div>
                        <a href="gerenciar-devolucao.php?id=<?php echo (int) $devolucao["id"]; ?>">
                            Gerenciar
                        </a>
                    </div>

                </div>

            <?php } ?>

        <?php } ?>

    </section>

</main>

<?php fim_documento(); ?>
