# BUSCA AÍ

Projeto web de gerenciamento e acompanhamento de devoluções empresariais.

## Tecnologias
- PHP 8.3 com Apache
- MySQL (mysqli)
- HTML, CSS e JavaScript

## Executar localmente
Coloque o conteúdo da pasta `BUSCA-AI` na raiz pública do Apache (por exemplo, `htdocs/BUSCA-AI`) e configure o banco MySQL em `BUSCA-AI/conexao.php` ou pelas variáveis de ambiente descritas abaixo.

## Deploy no Render
O repositório inclui um `Dockerfile` na raiz. Crie/edite um Web Service usando:
- Runtime: Docker
- Dockerfile Path: `./Dockerfile`
- Docker Context: `.`
- Branch: `main`

O container publica Apache na porta 10000.

### Banco de dados
O container não inclui um servidor MySQL. É necessário provisionar um MySQL acessível pela internet (ou pela rede privada compatível) e configurar no serviço Render:

- `DB_HOST`: endereço do servidor MySQL
- `DB_PORT`: porta (normalmente 3306)
- `DB_NAME`: nome do banco
- `DB_USER`: usuário do banco
- `DB_PASSWORD`: senha do banco

Importe o esquema SQL do projeto para esse banco antes de testar login, cadastro e devoluções. Não use as credenciais root do XAMPP em produção.

## Limitação conhecida
Sem banco MySQL remoto configurado, as páginas públicas (Início, Como funciona e Sobre) podem abrir, mas as funções que consultam ou gravam dados retornarão indisponibilidade.
