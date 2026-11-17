# SGOF — Sistema de Gestão de Operações Funerárias

Sistema web interno da **Concessionária Reviver** para acompanhar cada serviço funerário do primeiro atendimento até a finalização: cadastro do falecido e do contratante, logística (translados, laboratório, velório, sepultamento/cremação), agenda do dia, monitoramento da frota e comunicação entre as equipes.

> Projeto privado. Contém regras de negócio e estrutura de dados de clientes — não publicar.

---

## Funcionalidades

| Módulo | O que faz |
|---|---|
| **Central de Operações** | Abertura de novos atendimentos e triagem rápida dos serviços ativos em cards, com busca, filtro por unidade e por fase, indicadores de pendência e avanço de fase. |
| **Kanban Operacional** | Mesmos serviços organizados por fase (Atendimento → Conferência → Logística → Em Movimento → Laboratório → Ornamentação → Capela → Velório → Sepultamento → Finalizados). |
| **Lista de Serviços** | Todos os atendimentos, inclusive finalizados, com busca por falecido, CPF, contratante ou número da OS. |
| **Processo (OS)** | Tela completa de um atendimento: dados do falecido, contratante, serviço, logística e linha do tempo. Edição por blocos com permissão por perfil. |
| **Agenda Inteligente** | Calendário mensal + lista do dia. Cada serviço gera eventos de 1º translado, 2º translado, velório e sepultamento/cremação, cada um com as informações daquele momento. Impressão da lista do dia. |
| **Dashboard Gerencial** | Indicadores e gráficos por período e unidade: serviços por dia, por fase, categoria, tipo e unidade; próximas 48 h; itens que precisam de atenção. |
| **Monitoramento de Frota** | Mapa com a posição dos veículos via integração com a Cobli, vinculando motorista à OS em andamento. |
| **Notificações** | Avisos de novo serviço e mudança de fase, com sino na barra lateral e página de histórico. |
| **Conversas** | Chat em grupos entre usuários, com indicação de leitura e de "digitando". |
| **Configurações** | Usuários (perfil, unidade, situação), veículos e vínculo com a Cobli, chave da integração, histórico de alterações. |
| **Meu Perfil** | Troca de nome e senha pelo próprio usuário. |

---

## Perfis e acesso

O banco guarda apenas o **perfil** do usuário (coluna `usuarios.perfil`). O nível de acesso é derivado no código (`views/components/sidebar.php`).

| Perfil | Vê unidades | Principais permissões |
|---|---|---|
| Administrador | Todas | Tudo, incluindo Configurações e override de fase. |
| Coordenador | Todas | Todas as abas, exceto Configurações. Edita e exclui OS. |
| Logistica | Todas | Avança fase, edita o bloco **Logística** da OS (fase, motorista, laboratório, translados, despachante). |
| Colaborador | Própria unidade | Mesmas abas da Logística; edita a OS exceto o bloco Logística. |
| Corretor | Própria unidade | Abre atendimentos; edita a OS exceto o bloco Logística. |
| Supervisor | Própria unidade | Nível 2 (mesmas abas da Logística). |
| Motorista | Própria unidade | Vê a partir de "Em Movimento"; não vê CPF, contratante nem valores. |
| Laboratorio | Própria unidade | Vê a partir de "Laboratório"; não vê CPF, contratante nem valores. |
| Floricultura, Despachante | Própria unidade | Nível 1: Central e Kanban. |

A situação da conta (ativo/inativo) fica em `usuarios.status_sessao`.

---

## Tecnologias

- **PHP 8.x** com PDO (sem framework)
- **MySQL / MariaDB**
- HTML, CSS e JavaScript puros
- [Chart.js](https://www.chartjs.org/) 4 (dashboard), [FullCalendar](https://fullcalendar.io/) 6 (agenda), [Leaflet](https://leafletjs.com/) 1.9 + OpenStreetMap (frota), [Phosphor Icons](https://phosphoricons.com/)
- Integração REST com a API da **Cobli** (rastreamento de frota)

---

## Estrutura de pastas

```
sgof_logistica/
├── app/
│   ├── Api/            # Endpoints chamados pelo front (JSON)
│   ├── Controllers/    # Regras de negócio e acesso ao banco
│   ├── Helpers/        # Funções de apoio (ex.: notificações)
│   └── Services/       # Integrações externas (CobliService)
├── config/
│   ├── database.php         # Conexão com o banco (NÃO versionado)
│   ├── database.exemplo.php # Modelo para criar o database.php
│   └── CobliConfig.php      # Lê a chave da Cobli salva no banco
├── sql/                # Scripts de criação e migrações
├── views/
│   ├── assets/css/     # Estilos por módulo
│   ├── assets/js/      # Scripts compartilhados
│   ├── components/     # Sidebar e componentes reutilizáveis
│   └── *.php           # Páginas do sistema
└── login.php
```

---

## Instalação

### 1. Banco de dados

1. Crie um banco MySQL/MariaDB (collation `utf8mb4_unicode_ci`).
2. Importe o dump base do sistema.
3. Rode as migrações da pasta `sql/` **na ordem**, no phpMyAdmin:
   - `notificacoes.sql`
   - `migracao_coordenador.sql`
   - `migracao_status_usuarios.sql`
   - `migracao_colaborador.sql`
   - `migracao_novos_campos.sql`
   - `migracao_velorio_ornamentacao.sql`
   - `migracao_coroa_unidades.sql`
   - `migracao_segundo_translado.sql`

   Cada script tem comentários indicando os passos que precisam de ajuste manual (ids de unidades, por exemplo).

### 2. Configuração

```bash
cp config/database.exemplo.php config/database.php
```

Edite `config/database.php` com host, nome do banco, usuário e senha.

### 3. Servidor

- Aponte o servidor web para a pasta `sgof_logistica/`.
- PHP 8.0 ou superior com as extensões `pdo_mysql`, `mbstring` e `curl`.
- Linux diferencia maiúsculas de minúsculas nos nomes de arquivo: mantenha os nomes exatamente como estão no repositório.

### 4. Integração com a Cobli (opcional)

A chave da API **não fica no código**. Ela é salva pelo próprio sistema em **Configurações → Veículos → Salvar chave**, na tabela `integracoes_config` (`nome_servico = 'Cobli'`). Use **Testar conexão** para validar.

---

## Convenções do projeto

- Caminhos de `require` sempre com `__DIR__`, nunca relativos ao diretório atual.
- Todo endpoint em `app/Api/` começa com `session_start()`, confere login e perfil, e responde JSON com a chave `status` (`success` | `error`) e `message`.
- Permissões são verificadas **duas vezes**: na tela (para mostrar ou esconder o botão) e no endpoint (para recusar a ação). A tela sozinha nunca é a trava.
- Datas são gravadas em `DATETIME` no fuso do servidor; o front formata sem usar `new Date()` para não deslocar horários.
- Textos de interface em português do Brasil, com acentuação; valores internos (fases, perfis) sem acento, como estão no banco.

---

## Segurança

- `config/database.php` está no `.gitignore` e nunca deve ser commitado.
- Senhas de usuário são gravadas com `password_hash()`.
- Páginas de diagnóstico (`views/teste_sessao.php`, `views/diagnostico_cobli.php`) são temporárias: apague do servidor após o uso.
- O repositório deve permanecer **privado**.

---

## Autor

Marcos Nascimento — Área de Inovação, Concessionária Reviver.
