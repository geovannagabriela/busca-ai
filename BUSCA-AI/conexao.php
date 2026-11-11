<?php
/**
 * Adaptador de compatibilidade MySQLi -> PostgreSQL via PDO.
 * Mantém a interface usada pelas páginas existentes para evitar reescrever
 * todas as telas durante a migração do banco.
 *
 * Requer DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASSWORD no ambiente.
 */
if (!defined("MYSQLI_ASSOC")) {
    define("MYSQLI_ASSOC", PDO::FETCH_ASSOC);
}

class mysqli_result
{
    private array $rows;
    public int $num_rows;

    public function __construct(array $rows)
    {
        $this->rows = array_values($rows);
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc(): ?array
    {
        return array_shift($this->rows) ?: null;
    }

    public function fetch_all(int $mode = MYSQLI_ASSOC): array
    {
        $rows = $this->rows;
        $this->rows = [];
        return $rows;
    }

    public function free(): void
    {
        $this->rows = [];
        $this->num_rows = 0;
    }
}

class mysqli_stmt
{
    private PDOStatement $statement;
    private mysqli $connection;
    private array $parameters = [];
    private bool $returnsId = false;
    public int $errno = 0;
    public string $error = "";

    public function __construct(mysqli $connection, string $sql)
    {
        $this->connection = $connection;

        // O código legado lê insert_id após cadastrar uma devolução.
        if (preg_match('/^\s*INSERT\s+INTO\s+devolucoes\b/i', $sql)) {
            $sql = rtrim(trim($sql), ";") . " RETURNING id";
            $this->returnsId = true;
        }

        $this->statement = $connection->pdo->prepare($sql);
    }

    public function bind_param(string $types, &...$vars): bool
    {
        $this->parameters = [];
        foreach ($vars as &$value) {
            $this->parameters[] = $value;
        }
        return true;
    }

    public function execute(): bool
    {
        try {
            $ok = $this->statement->execute($this->parameters);
            if ($this->returnsId) {
                $id = $this->statement->fetchColumn();
                $this->connection->insert_id = $id !== false ? (int) $id : 0;
            }
            $this->errno = 0;
            $this->error = "";
            return $ok;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->errno = (($e->errorInfo[0] ?? $e->getCode()) === "23505") ? 1062 : 1;
            $this->connection->error = $this->error;
            $this->connection->errno = $this->errno;
            return false;
        }
    }

    public function get_result(): mysqli_result
    {
        return new mysqli_result($this->statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function close(): void
    {
        $this->statement->closeCursor();
    }
}

class mysqli
{
    public PDO $pdo;
    public int $insert_id = 0;
    public int $errno = 0;
    public string $error = "";

    public function __construct()
    {
        $host = getenv("DB_HOST") ?: "";
        $port = getenv("DB_PORT") ?: "5432";
        $database = getenv("DB_NAME") ?: "postgres";
        $user = getenv("DB_USER") ?: "";
        $password = getenv("DB_PASSWORD") ?: "";
        $sslmode = getenv("DB_SSLMODE") ?: "require";

        if ($host === "" || $user === "" || $password === "") {
            error_log("BUSCA AÍ: variáveis de conexão PostgreSQL ausentes.");
            http_response_code(503);
            exit("Banco de dados ainda não conectado. A configuração do Supabase está pendente.");
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode={$sslmode}";

        try {
            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ]);
            $this->pdo->exec("SET TIME ZONE 'America/Sao_Paulo'");
            $this->pdo->exec("SET application_name = 'busca-ai-render'");
        } catch (PDOException $e) {
            error_log("BUSCA AÍ: falha ao conectar ao PostgreSQL: " . $e->getMessage());
            http_response_code(503);
            exit("Serviço temporariamente indisponível. Verifique a conexão com o banco.");
        }
    }

    public function prepare(string $sql)
    {
        try {
            return new mysqli_stmt($this, $sql);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->errno = 1;
            return false;
        }
    }

    public function query(string $sql)
    {
        try {
            $statement = $this->pdo->query($sql);
            return new mysqli_result($statement->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->errno = 1;
            return false;
        }
    }

    public function close(): void
    {
        unset($this->pdo);
    }
}

$conexao = new mysqli();
