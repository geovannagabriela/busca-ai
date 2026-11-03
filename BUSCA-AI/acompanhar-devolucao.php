<?php
require_once "funcoes.php";

$empresa_id = exigir_empresa();

require_once "conexao.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    die("ID de devolução inválido.");
}

try {
    // Busca a devolução pertencente à empresa autenticada.
    $sql = "SELECT *
            FROM devolucoes
            WHERE id = ? AND empresa_id = ?";

    $stmt = preparar($conexao, $sql);
    $stmt->bind_param("ii", $id, $empresa_id);
    executar($stmt);

    $resultado = $stmt->get_result();

    if ($resultado->num_rows !== 1) {
        http_response_code(404);
        die("Devolução não encontrada.");
    }

    $devolucao = $resultado->fetch_assoc();
    $stmt->close();
    $status = trim($devolucao["status"] ?? "");

    // As cinco etapas oficiais do processo.
    $ordem_etapas = [
        "Solicitação",
        "Em coleta",
        "Triagem",
        "Destinação",
        "Recuperação de valor"
    ];

    // Define a aparência das etapas na linha do tempo.
    $etapas_css = [
        "etapa1" => "",
        "etapa2" => "",
        "etapa3" => "",
        "etapa4" => "",
        "etapa5" => ""
    ];

    $indice_status = array_search($status, $ordem_etapas, true);

    if ($indice_status !== false) {
        for ($i = 0; $i <= $indice_status; $i++) {
            $nome_variavel = "etapa" . ($i + 1);

            if ($i < $indice_status) {
                $etapas_css[$nome_variavel] = "concluido";
            } else {
                $etapas_css[$nome_variavel] = "atual";
            }
        }
    }

    // Busca o histórico de etapas da devolução.
    $sql_historico = "
        SELECT etapa, data_etapa
        FROM historico_devolucoes
        WHERE devolucao_id = ?
        ORDER BY data_etapa ASC
    ";

    $stmt_historico = preparar($conexao, $sql_historico);
    $stmt_historico->bind_param("i", $id);
    executar($stmt_historico);

    $resultado_historico = $stmt_historico->get_result();

    $datas_etapas = [];

    while ($registro = $resultado_historico->fetch_assoc()) {
        $etapa = trim($registro["etapa"]);

        if (in_array($etapa, $ordem_etapas, true)) {
            $datas_etapas[$etapa] = $registro["data_etapa"];
        }
    }

    /*
     * Para uma etapa pulada, utiliza apenas para exibição
     * a data da próxima etapa que possui registro.
     *
     * Se não houver etapa posterior registrada, a data não
     * será preenchida e a tela exibirá "Aguardando".
     */
    for ($i = 1; $i < count($ordem_etapas); $i++) {
        $etapa = $ordem_etapas[$i];

        if (!isset($datas_etapas[$etapa])) {
            for ($j = $i + 1; $j < count($ordem_etapas); $j++) {
                $proxima_etapa = $ordem_etapas[$j];

                if (isset($datas_etapas[$proxima_etapa])) {
                    $datas_etapas[$etapa] = $datas_etapas[$proxima_etapa];
                    break;
                }
            }
        }
    }

} catch (Throwable $erro) {
    error_log("Erro ao carregar acompanhamento da devolução: " . $erro->getMessage());

    http_response_code(500);
    die("Não foi possível carregar o acompanhamento da devolução.");
}

// Mensagem correspondente ao status atual.
$mensagens_status = [
    "Solicitação" => "A devolução foi solicitada e aguarda o início do processo.",
    "Em coleta" => "O produto está aguardando ou está em processo de coleta.",
    "Triagem" => "O produto foi recebido e está sendo analisado pela equipe da BUSCA AÍ.",
    "Destinação" => "O melhor destino para o produto está sendo definido.",
    "Recuperação de valor" => "O produto está passando pelo processo de recuperação de valor."
];

$mensagem_status = $mensagens_status[$status] ?? "A devolução está em andamento.";

function exibirData($data) {
    return formatar_data($data, "d/m/Y", "Aguardando");
}

inicio_documento("BUSCA AÍ - Acompanhamento");
cabecalho_pagina(links_empresa(), "dashboard.php");
?>

<main class="acompanhamento">

    <section class="acompanhamento-topo">
        <span class="etiqueta">ACOMPANHAMENTO</span>
        <h1>Acompanhe sua devolução</h1>
        <p>Consulte o andamento da devolução e acompanhe cada etapa do processo.</p>
    </section>

    <section class="devolucao-detalhes">
        <div class="detalhes-pedido">
            <div>
                <span>Pedido</span>
                <strong>#<?= escapar($devolucao["numero_pedido"]) ?></strong>
            </div>

            <div>
                <span>Produto</span>
                <?= escapar($devolucao["produto"]) ?>
            </div>

            <div>
                <span>Cliente</span>
                <strong><?= escapar($devolucao["nome_cliente"]) ?></strong>
            </div>

            <div>
                <span>Data da solicitação</span>
                <strong><?= escapar(exibirData($devolucao["data_solicitacao"])) ?></strong>
            </div>
        </div>

        <div class="status-atual">
            <span>Status atual</span>
            <strong><?= escapar($status) ?></strong>
            <p><?= escapar($mensagem_status) ?></p>
        </div>
    </section>

    <section class="historico-devolucao">
        <h2>Etapas da devolução</h2>

        <div class="linha-status">
            <div class="status-numero <?= escapar($etapas_css["etapa1"]) ?>">01</div>
            <div>
                <h3>Solicitação</h3>
                <p>Devolução solicitada pela empresa.</p>
                <span><?= escapar(exibirData($devolucao["data_solicitacao"])) ?></span>
            </div>
        </div>

        <div class="linha-status">
            <div class="status-numero <?= escapar($etapas_css["etapa2"]) ?>">02</div>
            <div>
                <h3>Coleta</h3>
                <p>Produto coletado no endereço informado.</p>
                <span>
                    <?= escapar(exibirData($datas_etapas["Em coleta"] ?? null)) ?>
                </span>
            </div>
        </div>

        <div class="linha-status">
            <div class="status-numero <?= escapar($etapas_css["etapa3"]) ?>">03</div>
            <div>
                <h3>Triagem</h3>
                <p>Produto em análise para identificar sua condição e melhor destino.</p>
                <span>
                    <?= escapar(exibirData($datas_etapas["Triagem"] ?? null)) ?>
                </span>
            </div>
        </div>

        <div class="linha-status">
            <div class="status-numero <?= escapar($etapas_css["etapa4"]) ?>">04</div>
            <div>
                <h3>Destinação</h3>
                <p>Definição do melhor destino para o produto.</p>
                <span>
                    <?= escapar(exibirData($datas_etapas["Destinação"] ?? null)) ?>
                </span>
            </div>
        </div>

        <div class="linha-status">
            <div class="status-numero <?= escapar($etapas_css["etapa5"]) ?>">05</div>
            <div>
                <h3>Recuperação de valor</h3>
                <p>Produto destinado à alternativa que permita recuperar seu maior valor possível.</p>
                <span>
                    <?= escapar(exibirData($datas_etapas["Recuperação de valor"] ?? null)) ?>
                </span>
            </div>
        </div>
    </section>

    <a href="devolucoes.php" class="botao-devolucao">Voltar para devoluções</a>

</main>

<?php fim_documento(); ?>
