# Sistema de Timesheet Integrado

Bem-vindo ao repositório oficial do Sistema de Timesheet (Controle de Apontamentos e Ponto), uma solução moderna desenvolvida para simplificar e garantir a conformidade (CLT) da jornada de trabalho dos colaboradores, de maneira integrada ao ERP corporativo.

## 🎯 Visão Geral

O projeto consiste em uma aplicação web focada no gerenciamento de horas, apontamentos de projetos/obras, ocorrências (plantões) e auditoria de jornadas. 
Ao invés de operar como uma ilha de dados, este sistema atua em simbiose com o ERP principal da empresa. Ele consome dados de usuários e estruturas (Setores, Obras) do ERP e atua como a **Fonte da Verdade (Master)** no que tange aos registros de trabalho diário dos colaboradores.

## 🛠 Stack Tecnológica

- **Framework Core**: Laravel 11 (PHP 8)
- **Frontend / UI**: Blade Templates, TailwindCSS, Livewire (para dashboards dinâmicos) e JavaScript.
- **Banco de Dados**: SQL Relacional.
- **Autenticação & Autorização**: Single Sign-On (SSO) com JWT/Tickets de sessão, complementado pelo `spatie/laravel-permission` (RBAC).
- **Monitoramento de Erros**: Sentry (`sentry/sentry-laravel`).
- **Performance & Cache**: Driver de Cache configurado como `file` para rápida resposta na avaliação de jornadas (TTL em Plantões), Eager Loading robusto e índices compostos no banco de dados.

## 📚 Índice da Documentação

A documentação detalhada foi dividida em módulos para facilitar a consulta por novos desenvolvedores, auditores e arquitetos. Acesse os manuais na pasta `/docs`:

1. [Arquitetura e Integração de Dados](docs/01-arquitetura-e-dados.md) - Fluxo de dados, master/slave e JIT Provisioning.
2. [Níveis de Acesso e Segurança](docs/02-acesso-e-seguranca.md) - Hierarquias, proteção IDOR e mitigação de vulnerabilidades.
3. [Regras de Negócio de Apontamentos](docs/03-regras-apontamento.md) - Lógica de plantões, recálculo noturno e regras da CLT.
4. [Visualizações e Performance](docs/04-visualizacao-performance.md) - Filtros, Eager Loading e paginação de alta capacidade.
5. [Ciclo de Vida do Apontamento](docs/diagrama-ciclo-vida-apontamento.md) - Diagrama de fluxo de criação, ajuste e aprovação/reprovação.