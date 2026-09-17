# 4. Visualizações e Performance

Devido ao grande volume de dados acumulados pelo tráfego diário de apontamentos de toda a operação, o Timesheet implementa as seguintes diretrizes para garantir que as visualizações (Dashboards e Histórico) mantenham-se rápidas independentemente do tamanho da base.

## 1. Regras de Paginação Segura
Foram abolidas da arquitetura abordagens que carregam coleções inteiras do banco de dados na memória do servidor (como o uso indiscriminado de `->get()` ou `->all()` em tabelas densas).
Nas listagens principais, adota-se o padrão nativo de Paginação, por exemplo:
`->paginate(50)->withQueryString()`
O método `withQueryString()` garante que quaisquer parâmetros de busca aplicados pelo usuário (ex: filtragem de `data_inicio` a `data_fim` ou buscar por `projeto`) se mantenham ativos nas requisições ao longo das páginas.

## 2. Eager Loading, N+1 e Bloqueio Global (Lazy Loading)
Para evitar o clássico problema "N+1 Queries" durante as renderizações da camada de View (Blade), todas as requisições densas utilizam *Eager Loading* de relacionamentos através do Eloquent (ex: `with(['colaborador', 'projeto', 'notificacoes'])`). Controladores de alto tráfego como `ColaboradorController`, `ErpObraManualController` e `ApontamentoController` possuem Eager Loading customizado (ex: filtragem de notificações não lidas diretamente na query).
Deste modo, ao listar 50 registros que dependem de dados aninhados, o Laravel consolida a requisição em consultas otimizadas ao invés de centenas de queries.

**Prevenção Automática em Desenvolvimento:**
Para forçar a adoção do Eager Loading e evitar regressões, o `AppServiceProvider` injeta globalmente a instrução:
`\Illuminate\Database\Eloquent\Model::preventLazyLoading(! app()->isProduction());`
Isso garante que, fora do ambiente de produção, qualquer tentativa de ler um relacionamento que não foi carregado previamente gere uma exceção fatal, barrando códigos de baixa performance antes do deploy.

## 3. Observabilidade e Tratamento de Erros (Sentry)
A aplicação possui integração avançada com o Sentry para rastreamento de falhas, gargalos de performance e queries lentas em produção.
- **Otimização de Logs:** O canal de log do Sentry (`logs_channel_level`) foi calibrado para `warning`, evitando overhead excessivo de requisições por logs de debug em produção.
- **Blindagem do Cron:** As rotinas automatizadas no Scheduler e no Console possuem captura passiva de falhas para o Sentry, garantindo observabilidade contínua nas engrenagens de backend.

## 4. Otimizações de Banco de Dados e Compatibilidade PHP 8
O acesso mais agressivo da aplicação reside no cruzamento das colunas de Histórico por Período de Data e por Identificador do Funcionário.
Para garantir I/O eficiente:
- A tabela `apontamentos` possui um índice dedicado na coluna `data_apontamento`.
- Além disso, um **índice composto** (`colaborador_id`, `data_apontamento`) foi desenhado.

**Modernização e PHP 8:**
O backend evita funções nativas legadas do PHP depreciadas ou removidas no PHP 8. Operações temporais em APIs (ex: `CalendarioApiController`) utilizam ativamente o objeto `Carbon\Carbon` nativo do Laravel (ex: `daysInMonth`) em substituição a abordagens arcaicas (`cal_days_in_month`), garantindo segurança, coesão e tipagem correta.
