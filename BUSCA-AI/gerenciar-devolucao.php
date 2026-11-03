<?php
require_once "funcoes.php";

exigir_interno();

require_once "conexao.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("ID de devolução inválido.");
}

try {
    $stmt = preparar(
        $conexao,
        "SELECT devolucoes.*, empresas.nome_empresa
         FROM devolucoes
         INNER JOIN empresas
             ON devolucoes.empresa_id = empresas.id
         WHERE devolucoes.id = ?"
    );

    $stmt->bind_param("i", $id);
    executar($stmt);

    $devolucao = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$devolucao) {
        http_response_code(404);
        exit("Devolução não encontrada.");
    }
} catch (Throwable $erro) {
    error_log("Erro ao consultar devolução: " . $erro->getMessage());

    http_response_code(500);
    exit("Não foi possível carregar os dados da devolução.");
}

$status_atual = $devolucao["status"] ?? "";

$etapas = [
    "Solicitação",
    "Em coleta",
    "Triagem",
    "Destinação",
    "Recuperação de valor"
];

inicio_documento("BUSCA AÍ - Gerenciar devolução");
cabecalho_pagina(links_interno(), "painel-buscaai.php");
?>

<main class="gerenciar-devolucao">

        <!-- Apresentação da página -->
        <section class="painel-topo">

            <span class="etiqueta">
                ÁREA INTERNA
            </span>

            <h1>Gerenciar devolução</h1>

            <p>
                Consulte os dados e atualize o andamento da devolução.
            </p>

        </section>

        <!-- Dados completos da devolução -->
        <section class="dados-devolucao">

            <h2>Dados da devolução</h2>

            <div class="dados-grid">

                <div>
                    <span>Pedido</span>
                    <strong>
                        #<?php echo escapar($devolucao["numero_pedido"]); ?>
                    </strong>
                </div>

                <div>
                    <span>Empresa</span>
                    <strong>
                        <?php echo escapar($devolucao["nome_empresa"]); ?>
                    </strong>
                </div>

                <div>
                    <span>Cliente</span>
                    <strong>
                        <?php echo escapar($devolucao["nome_cliente"]); ?>
                    </strong>
                </div>

                <div>
                    <span>E-mail</span>
                    <strong>
                        <?php echo escapar($devolucao["email_cliente"]); ?>
                    </strong>
                </div>

                <div>
                    <span>Telefone</span>
                    <strong>
                        <?php echo escapar($devolucao["telefone_cliente"]); ?>
                    </strong>
                </div>

                <div>
                    <span>Produto</span>
                    <strong>
                        <?php echo escapar($devolucao["produto"]); ?>
                    </strong>
                </div>

                <div>
                    <span>Quantidade</span>
                    <strong>
                        <?php echo (int) $devolucao["quantidade"]; ?>
                    </strong>
                </div>

                <div>
                    <span>Motivo</span>
                    <strong>
                        <?php echo escapar($devolucao["motivo"]); ?>
                    </strong>
                </div>

                <div>
                    <span>Endereço de coleta</span>
                    <strong>
                        <?php echo escapar($devolucao["endereco_coleta"]); ?>
                    </strong>
                </div>

                <div>
                    <span>Cidade</span>
                    <strong>
                        <?php echo escapar($devolucao["cidade"]); ?>
                    </strong>
                </div>

                <div>
                    <span>CEP</span>
                    <strong>
                        <?php echo escapar($devolucao["cep"]); ?>
                    </strong>
                </div>

                <div>
                    <span>Data da solicitação</span>
                    <strong>
                        <?php echo escapar(formatar_data($devolucao["data_solicitacao"] ?? null)); ?>
                    </strong>
                </div>

            </div>

            <div class="observacoes">

                <span>Observações</span>

                <p><?php
                    echo nl2br(escapar($devolucao["observacoes"] ?? ""));
                ?></p>

            </div>

        </section>

        <!-- Atualização da etapa atual -->
        <section class="alterar-status">

            <h2>Atualizar status</h2>

            <p>
                Selecione a etapa atual da devolução.
                Etapas podem ser puladas quando necessário.
            </p>

            <form action="atualizar-status.php" method="POST">

                <?= csrf_campo() ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int) $devolucao["id"]; ?>"
                >

                <select name="status" aria-label="Status da devolução" required>

                    <?php foreach ($etapas as $etapa) { ?>

                        <option
                            value="<?php echo escapar($etapa); ?>"
                            <?php
                            echo ($status_atual === $etapa)
                                ? "selected"
                                : "";
                            ?>
                        >
                            <?php echo escapar($etapa); ?>
                        </option>

                    <?php } ?>

                </select>

                <button type="submit">
                    Atualizar status
                </button>

            </form>

        </section>

        <!-- Retorno ao painel administrativo -->
        <a
            href="painel-buscaai.php"
            class="botao-devolucao"
        >
            Voltar para o painel
        </a>

    </main>

<?php fim_documento(); ?>
