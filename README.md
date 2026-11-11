# BUSCA AÍ

Projeto web de gerenciamento e acompanhamento de devoluções empresariais.

## Tecnologias
- PHP 8.3 com Apache
- PostgreSQL no Supabase (PDO_PGSQL)
- HTML, CSS e JavaScript

## Estrutura
O aplicativo completo fica em `BUSCA-AI/`. As páginas HTML, PHP, imagens, CSS e JavaScript são servidas pelo Apache no Render. O front-end público publicado na Vercel continua separado e aponta os acessos PHP para o serviço Render.

## Deploy atual e migração
O serviço Render `busca-ai-php` continua apontando para a branch `main`. A migração PostgreSQL está isolada na branch `deploy/supabase-postgres` e ainda não foi mesclada nem publicada. O projeto Vercel continua servindo o front-end.

## Banco Supabase
O banco PostgreSQL do Supabase é independente do MySQL do InfinityFree. Ele contém as tabelas:
- `empresas`
- `usuarios_buscaai`
- `devolucoes`
- `historico_devolucoes`
- `mensagens_contato`

Foi feita uma cópia dos dados exportados do InfinityFree para o Supabase, preservando os IDs:
- `empresas`: 4 registros
- `usuarios_buscaai`: 1 registro
- `devolucoes`: 3 registros
- `historico_devolucoes`: 6 registros
- `mensagens_contato`: 0 registros

As chaves estrangeiras foram conferidas: não há devoluções sem empresa nem históricos sem devolução. O banco MySQL do InfinityFree não foi alterado; ele permanece independente e acessível.

## Variáveis de conexão no Render
Quando a migração estiver aprovada nos testes, configurar no serviço Render `busca-ai-php` as variáveis abaixo usando a connection string **Session pooler** do Supabase:

- `DB_HOST`: host do Session pooler
- `DB_PORT`: `5432`
- `DB_NAME`: `postgres`
- `DB_USER`: usuário do Session pooler (formato `postgres.<project-ref>`)
- `DB_PASSWORD`: senha do banco PostgreSQL, inserida diretamente no painel do Render
- `DB_SSLMODE`: `require`

Nunca coloque a senha no GitHub, em arquivos públicos ou no chat. Não substitua as variáveis de produção antes de validar o backend.

## Segurança e validação
As cinco tabelas estão com Row Level Security habilitado. O backend deve acessar o PostgreSQL no servidor Render; credenciais não devem ser expostas no navegador. Antes de mesclar e publicar, validar conexão e os fluxos de cadastro, login empresarial e interno, dashboard, solicitação e acompanhamento de devoluções, atualização de status, histórico, perfil, alteração de senha e contato.

## Ambiente local
O ambiente XAMPP atual usa MySQL e não é compatível diretamente com a conexão PostgreSQL desta branch. A migração não altera o banco local nem o banco MySQL do InfinityFree.
