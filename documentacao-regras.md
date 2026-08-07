# Documentação de Autorização, Regras de Negócio e Interface (Timesheet)

> [!NOTE]
Este documento reflete as regras oficiais e atualizadas do sistema de Timesheet, mapeadas com base no uso do `spatie/laravel-permission` (`model_has_roles`), middlewares de rota e permissões de visibilidade. Serve como a Fonte de Verdade para permissões, visibilidade de dados e navegação.
> 

---

## 1. Mapeamento de Perfis, Dashboards e Visibilidade (RLS)

O sistema conta com 5 principais perfis de acesso centralizados na classe `App\Helpers\AcessoHelper`. A visibilidade do Dashboard e dos dados (Row Level Security) varia de acordo com o papel.

# Perfis e Permissões de Acesso (RLS)

## 1. ADMIN
- **Escopo do Dashboard (Interface):**
  - **Acesso Total (4 colunas):**
    - **Timesheet:** Apontamentos, Histórico, Aprovações
    - **Gestão:** Controle de Envios, Dashboard, Logs
    - **Configurações:** Health Check, WhatsApp, Espelho, Feriados
    - **Movimentações:** Colaboradores, Veículos, Projetos, Setores
- **Nível de Visibilidade e Acesso Geral (RLS):**
  - **Acesso Global:** Visão irrestrita de todos os dados e setores.
  - Único perfil com permissão para gerenciar cadastros base (CRUDs) e editar/excluir apontamentos de terceiros.

## 2. GERENCIAL
- **Escopo do Dashboard (Interface):**
  - **Módulo Timesheet:** Apontamentos, Histórico e Central de Aprovações.
- **Nível de Visibilidade e Acesso Geral (RLS):**
  - **Acesso Expandido (Setores):** Enxerga a si mesmo e aos colaboradores vinculados aos seus setores gerenciados/vinculados (`setoresVinculados()` N:N).

## 3. SAC
- **Escopo do Dashboard (Interface):**
  - **Módulo Timesheet Restrito:** Apontamentos e Histórico (sem atalho de Aprovações).
- **Nível de Visibilidade e Acesso Geral (RLS):**
  - **Acesso Expandido (Operacional):** Mesma visibilidade de dados do Gerencial (equipes/setores vinculados), atuando como apoio ao lançamento de horas.
  - Possui permissão para Rateios (`podeFazerRateio()`).

## 4. COORDENADOR
- **Escopo do Dashboard (Interface):**
  - **Módulo Timesheet Restrito:** Apontamentos e Histórico.
- **Nível de Visibilidade e Acesso Geral (RLS):**
  - **Acesso Padrão + Projetos:** Enxerga a si mesmo e apontamentos vinculados aos projetos/clientes que coordena (`colaborador_projeto_gerenciado` e `colaborador_cliente_gerenciado`).
  - Possui permissão para Rateios (`podeFazerRateio()`).

## 5. OPERACIONAL
- **Escopo do Dashboard (Interface):**
  - **Módulo Básico:** Apontamento de Horas e Histórico Próprio.
- **Nível de Visibilidade e Acesso Geral (RLS):**
  - **Acesso Restrito:** Colaborador padrão de campo. Enxerga estritamente seus próprios dados.

---

## 2. Regras de Timesheet (Apontamentos e Edição)

### A. Fluxo de Criação (Lançamentos por Terceiros)

A permissão de lançamento é regida por `AcessoHelper::podeLancarPorTerceiros($user)`:

1. **Lançamento Próprio:** Liberado para todos os perfis ativos.
2. **Lançamento para Terceiros:**
    - **ADMIN:** Pode selecionar qualquer colaborador cadastrado na empresa.
    - **GERENCIAL / SAC:** Podem selecionar colaboradores que integram seus **Setores Vinculados**.
    - **COORDENADOR / OPERACIONAL:** O campo de seleção de colaborador fica **invisível/desativado** (retorna apenas o próprio ID). O backend (`ApontamentoRequest`) força silenciosamente o `$colaborador_id` para o ID do usuário autenticado, prevenindo bypass via API/Postman.

### B. Trava Assimétrica de Edição e Exclusão

- **ADMIN:** Único perfil autorizado a editar e excluir apontamentos de terceiros, podendo também ignorar o limite máximo de edições por registro.
- **DEMAIS PERFIS (Gerentes, SAC, Coordenadores, Operacional):** Restritos estritamente a editar ou excluir **seus próprios** apontamentos (`$apontamento->colaborador_id === $user->colaborador->id`). Gerentes e SAC **não podem editar** apontamentos que lançaram em nome de seus subordinados.

---

## 3. Fluxo de Aprovação, Ajustes e Trava de Autoaprovação

### A. Status dos Registros

Os apontamentos passam pelos seguintes estados no model `Apontamento`:

- **`STATUS_APROVACAO_CHOICES`:** `EM_ANALISE` (padrão), `APROVADO`, `REJEITADO`, `SOLICITACAO_AJUSTE`.
- **`STATUS_AJUSTE_CHOICES`:** `PENDENTE`, `APROVADO`.

### B. Regra de Autoaprovação (Anti-Self-Approval)

- Aprovações são exclusivas para perfis com privilégios de gestão (`isGerente()` ou `isAdmin()`).
- **Trava de Segurança:** A query base da Central de Aprovações omite automaticamente os apontamentos do próprio gestor logado, **impedindo que qualquer usuário aprove as próprias horas**.

---

## 4. Cadastros Gerais e Configurações (CRUDs Base)

Os módulos de movimentação e cadastros do sistema (Colaboradores, Projetos, Veículos e Setores) são protegidos estritamente pelo middleware `role:ADMIN`. Perfis Gerenciais, SAC, Coordenadores e Operacionais não possuem acesso a essas rotas.

---

## 5. Autenticação, SSO e Provisionamento

1. **SSO / ERP:** A autenticação e a gestão de credenciais são externas (ERP / Active Directory).
2. **Provisionamento:** Usuários são ativados via token externo. A atribuição das funções Spatie (`roles`) é feita administrativamente no momento da primeira ativação.