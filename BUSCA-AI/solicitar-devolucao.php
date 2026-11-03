<?php
require_once "funcoes.php";

$empresa_id = exigir_empresa();

require_once "conexao.php";

$mensagem = "";
$tipo_mensagem = "";

$numero_pedido = "";
$nome_cliente = "";
$telefone_cliente = "";
$email_cliente = "";
$endereco_coleta = "";
$cidade = "";
$cep = "";
$produto = "";
$motivo = "";
$quantidadeTexto = "";
$observacoes = "";

$campo = static function (string $nome): string {
    $valor = $_POST[$nome] ?? "";
    return is_string($valor) ? trim($valor) : "";
};

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_valido()) {
        $mensagem = "Sessão expirada. Recarregue a página e tente novamente.";
        $tipo_mensagem = "erro";
    }

    $numero_pedido = $campo("pedido");
    $nome_cliente = $campo("nome_cliente");
    $telefone_cliente = $campo("telefone");
    $email_cliente = $campo("email");
    $endereco_coleta = $campo("endereco");
    $cidade = $campo("cidade");
    $cep = $campo("cep");
    $produto = $campo("produto");
    $motivo = $campo("motivo");
    $quantidadeTexto = $campo("quantidade");
    $observacoes = $campo("observacoes");

    $motivosPermitidos = [
        "defeito",
        "avaria",
        "produto-errado",
        "tamanho",
        "arrependimento",
        "outro"
    ];

    $quantidade = filter_var(
        $quantidadeTexto,
        FILTER_VALIDATE_INT
    );

    if ($mensagem !== "") {
        // Falha de CSRF já sinalizada acima.

    } elseif (
        $numero_pedido === "" ||
        $nome_cliente === "" ||
        $telefone_cliente === "" ||
        $email_cliente === "" ||
        $endereco_coleta === "" ||
        $cidade === "" ||
        $cep === "" ||
        $produto === "" ||
        $motivo === "" ||
        $quantidadeTexto === ""
    ) {
        $mensagem = "Preencha todos os campos obrigatórios.";
        $tipo_mensagem = "erro";

    } elseif (
        strlen($numero_pedido) > 100 ||
        strlen($nome_cliente) > 255 ||
        strlen($telefone_cliente) > 30 ||
        strlen($email_cliente) > 255 ||
        strlen($endereco_coleta) > 255 ||
        strlen($cidade) > 150 ||
        strlen($cep) > 9 ||
        strlen($produto) > 255 ||
        strlen($observacoes) > 10000
    ) {
        $mensagem = "Um ou mais campos excedem o tamanho permitido.";
        $tipo_mensagem = "erro";

    } elseif (!filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Informe um e-mail válido.";
        $tipo_mensagem = "erro";

    } elseif (!in_array($motivo, $motivosPermitidos, true)) {
        $mensagem = "Selecione um motivo de devolução válido.";
        $tipo_mensagem = "erro";

    } elseif ($quantidade === false || $quantidade < 1) {
        $mensagem = "A quantidade deve ser um número inteiro maior que zero.";
        $tipo_mensagem = "erro";

    } else {
        $observacoes = $observacoes === "" ? null : $observacoes;
        $transacaoIniciada = false;
        $stmt = null;
        $stmtHistorico = null;

        try {
            $conexao->begin_transaction();
            $transacaoIniciada = true;

            $sql = "INSERT INTO devolucoes (
                        empresa_id,
                        numero_pedido,
                        nome_cliente,
                        telefone_cliente,
                        email_cliente,
                        endereco_coleta,
                        cidade,
                        cep,
                        produto,
                        motivo,
                        quantidade,
                        observacoes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = preparar($conexao, $sql);

            $stmt->bind_param(
                "isssssssssis",
                $empresa_id,
                $numero_pedido,
                $nome_cliente,
                $telefone_cliente,
                $email_cliente,
                $endereco_coleta,
                $cidade,
                $cep,
                $produto,
                $motivo,
                $quantidade,
                $observacoes
            );

            executar($stmt);

            $devolucao_id = $conexao->insert_id;
            $stmt->close();
            $stmt = null;

            $sqlHistorico = "INSERT INTO historico_devolucoes
                                (devolucao_id, etapa)
                             VALUES (?, ?)";

            $etapaInicial = "Solicitação";

            $stmtHistorico = preparar($conexao, $sqlHistorico);

            $stmtHistorico->bind_param(
                "is",
                $devolucao_id,
                $etapaInicial
            );

            executar($stmtHistorico);

            $stmtHistorico->close();
            $stmtHistorico = null;

            if (!$conexao->commit()) {
                throw new RuntimeException(
                    "Falha ao confirmar o cadastro."
                );
            }

            $transacaoIniciada = false;

            $mensagem = "Devolução solicitada com sucesso!";
            $tipo_mensagem = "sucesso";

            // Limpa os campos após o cadastro bem-sucedido.
            $numero_pedido = "";
            $nome_cliente = "";
            $telefone_cliente = "";
            $email_cliente = "";
            $endereco_coleta = "";
            $cidade = "";
            $cep = "";
            $produto = "";
            $motivo = "";
            $quantidadeTexto = "";
            $observacoes = "";

        } catch (Throwable $erro) {
            if ($transacaoIniciada) {
                try {
                    $conexao->rollback();
                } catch (Throwable $erroRollback) {
                    error_log(
                        "Erro ao desfazer transação: " .
                        $erroRollback->getMessage()
                    );
                }
            }

            error_log(
                "Erro ao cadastrar devolução: " .
                $erro->getMessage()
            );

            $mensagem = "Não foi possível cadastrar a devolução. Tente novamente.";
            $tipo_mensagem = "erro";

        } finally {
            if ($stmt instanceof mysqli_stmt) {
                $stmt->close();
            }

            if ($stmtHistorico instanceof mysqli_stmt) {
                $stmtHistorico->close();
            }
        }
    }
}

inicio_documento("BUSCA AÍ - Solicitar devolução");
cabecalho_pagina(links_empresa(), "dashboard.php");
?>

<main class="devolucao">

    <section class="devolucao-topo">
        <span class="etiqueta">SOLICITAÇÃO</span>

        <h1>Solicite sua devolução</h1>

        <p>
            Preencha os dados abaixo para iniciar o processo
            de devolução do produto.
        </p>
    </section>

    <section class="devolucao-formulario">

        <div class="formulario-info">
            <h2>Solicitar devolução</h2>

            <p>
                Para iniciarmos o processo, informe os dados
                do pedido e do produto que será devolvido.
            </p>

            <div class="etapas-devolucao">
                <div>
                    <span>01</span>
                    <p>Solicitação</p>
                    <br>
                </div>

                <div>
                    <span>02</span>
                    <p>Coleta</p>
                    <br>
                </div>

                <div>
                    <span>03</span>
                    <p>Triagem</p>
                    <br>
                </div>

                <div>
                    <span>04</span>
                    <p>Recuperação de valor</p>
                </div>
            </div>
        </div>

        <div class="formulario-card">

            <?php if ($mensagem !== "") { echo aviso($tipo_mensagem, $mensagem); } ?>

            <form action="solicitar-devolucao.php" method="POST">

                <?= csrf_campo() ?>

                <div class="campo">
                    <label for="pedido">Número do pedido</label>
                    <input
                        type="text"
                        id="pedido"
                        name="pedido"
                        placeholder="Ex: 10245"
                        maxlength="100"
                        value="<?= escapar($numero_pedido) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="nome">Nome do cliente</label>
                    <input
                        type="text"
                        id="nome"
                        name="nome_cliente"
                        placeholder="Nome completo"
                        maxlength="255"
                        value="<?= escapar($nome_cliente) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="telefone">Telefone</label>
                    <input
                        type="tel"
                        id="telefone"
                        name="telefone"
                        placeholder="(34) 99999-9999"
                        maxlength="30"
                        value="<?= escapar($telefone_cliente) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="cliente@email.com"
                        maxlength="255"
                        value="<?= escapar($email_cliente) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="endereco">Endereço para coleta</label>
                    <input
                        type="text"
                        id="endereco"
                        name="endereco"
                        placeholder="Rua, número e complemento"
                        maxlength="255"
                        value="<?= escapar($endereco_coleta) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="cidade">Cidade</label>
                    <input
                        type="text"
                        id="cidade"
                        name="cidade"
                        placeholder="Cidade"
                        maxlength="150"
                        value="<?= escapar($cidade) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="cep">CEP</label>
                    <input
                        type="text"
                        id="cep"
                        name="cep"
                        placeholder="00000-000"
                        maxlength="9"
                        value="<?= escapar($cep) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="produto">Produto</label>
                    <input
                        type="text"
                        id="produto"
                        name="produto"
                        placeholder="Nome do produto"
                        maxlength="255"
                        value="<?= escapar($produto) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="motivo">Motivo da devolução</label>

                    <select id="motivo" name="motivo" required>
                        <option value="">Selecione o motivo</option>
                        <option value="defeito" <?= $motivo === "defeito" ? "selected" : "" ?>>Produto com defeito</option>
                        <option value="avaria" <?= $motivo === "avaria" ? "selected" : "" ?>>Produto danificado</option>
                        <option value="produto-errado" <?= $motivo === "produto-errado" ? "selected" : "" ?>>Produto diferente do pedido</option>
                        <option value="tamanho" <?= $motivo === "tamanho" ? "selected" : "" ?>>Tamanho incorreto</option>
                        <option value="arrependimento" <?= $motivo === "arrependimento" ? "selected" : "" ?>>Arrependimento</option>
                        <option value="outro" <?= $motivo === "outro" ? "selected" : "" ?>>Outro motivo</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="quantidade">Quantidade</label>
                    <input
                        type="number"
                        id="quantidade"
                        name="quantidade"
                        min="1"
                        step="1"
                        placeholder="Quantidade"
                        value="<?= escapar($quantidadeTexto) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="observacoes">Observações</label>
                    <textarea
                        id="observacoes"
                        name="observacoes"
                        maxlength="10000"
                        placeholder="Informe detalhes importantes sobre a devolução"
                    ><?= escapar($observacoes) ?></textarea>
                </div>

                <button type="submit">
                    Solicitar devolução
                </button>

            </form>

        </div>

    </section>

</main>

<?php fim_documento(); ?>
