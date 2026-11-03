<?php
require_once "funcoes.php";

exigir_interno();

require_once "conexao.php";

try {
    // LEFT JOIN: mensagens enviadas sem empresa logada também aparecem na lista.
    $resultado = $conexao->query(
        "SELECT mensagens_contato.*, empresas.nome_empresa
         FROM mensagens_contato
         LEFT JOIN empresas
             ON mensagens_contato.empresa_id = empresas.id
         ORDER BY mensagens_contato.data_envio DESC"
    );

    if (!$resultado) {
        throw new RuntimeException("Falha ao consultar mensagens de contato: " . $conexao->error);
    }
} catch (Throwable $erro) {
    error_log("Erro ao consultar mensagens de contato: " . $erro->getMessage());

    http_response_code(500);
    exit("Não foi possível carregar as mensagens. Tente novamente.");
}

inicio_documento("BUSCA AÍ - Mensagens");
cabecalho_pagina(links_interno(), "painel-buscaai.php");
?>

<main class="pagina-devolucoes">

        <!-- Apresentação da página -->
        <section class="devolucoes-topo">

            <span class="etiqueta">
                CONTATO
            </span>

            <h1>Mensagens recebidas</h1>

            <p>
                Consulte as mensagens enviadas pelo formulário de contato.
            </p>

        </section>

        <!-- Lista de mensagens -->
        <section class="lista-devolucoes lista-mensagens">

            <div class="cabecalho-lista">
                <span>Empresa</span>
                <span>Nome</span>
                <span>E-mail</span>
                <span>Assunto</span>
                <span>Mensagem</span>
                <span>Data</span>
            </div>

            <?php if ($resultado->num_rows > 0) { ?>

                <?php while ($mensagem = $resultado->fetch_assoc()) { ?>

                    <div class="linha-devolucao">

                        <div data-rotulo="Empresa">
                            <span><?= escapar($mensagem["nome_empresa"] ?? "") ?></span>
                        </div>

                        <div data-rotulo="Nome">
                            <strong><?= escapar($mensagem["nome"]) ?></strong>
                        </div>

                        <div data-rotulo="E-mail">
                            <span><?= escapar($mensagem["email"]) ?></span>
                        </div>

                        <div data-rotulo="Assunto">
                            <span><?= escapar($mensagem["assunto"]) ?></span>
                        </div>

                        <div data-rotulo="Mensagem">
                            <span><?= nl2br(escapar($mensagem["mensagem"])) ?></span>
                        </div>

                        <div data-rotulo="Data">
                            <span><?= escapar(formatar_data($mensagem["data_envio"] ?? null, "d/m/Y H:i")) ?></span>
                        </div>

                    </div>

                <?php } ?>

            <?php } else { ?>

                <p class="estado-vazio">Nenhuma mensagem recebida.</p>

            <?php } ?>

            <?php $resultado->free(); ?>

        </section>

    </main>

<?php fim_documento(); ?>
