# 📊 Ciclo de Vida dos Apontamentos (Timesheet)
**Guia Executivo e Fluxo Operacional**

---

## 1. Introdução ao Ecossistema

O módulo de Apontamentos é o coração do sistema e atua como a **Fonte da Verdade** absoluta para os cálculos de jornada de trabalho da empresa.

- **Nascimento do Dado:** Todo apontamento nasce no Timesheet. Ele exige amarração rigorosa com projetos operacionais, códigos de clientes, e (quando aplicável) rastreamento de veículos.
- **Snapshot Imutável:** Quando um registro é gerado, o sistema congela o estado do colaborador naquele exato milissegundo (incluindo o seu nível e cargo). Isso garante a **Integridade de Custos de Homem-Hora (HH)**. Se um júnior for promovido a sênior no mês seguinte, as horas passadas não sofrerão inflação de custo, pois a auditoria trava o contexto da época.
- **Auditoria Contínua:** Qualquer edição dispara o gatilho do Observer, salvando o estado anterior de forma intocável na tabela de históricos.

---

## 2. Regras de Negócio, Plantões e Escalas

Para mitigar fraudes e horas indevidas, o preenchimento da folha passa por travas e cruzamentos rigorosos em tempo real com o ERP.

### A Janela Oficial e Ocorrências
- **Horário Estrito (17h - 07:30h):** Por padrão, plantões e sobreavisos só podem ser reportados dentro desta janela no dia de trabalho útil. 
- **Matemática da Madrugada:** Se um colaborador lança horas às `02:00` da manhã, a inteligência do motor temporal deduz que o turno pertence ao dia anterior, cruzando os dados corretamente sem gerar falha de escopo.

### Bypass de Finais de Semana e Feriados
- A trava de horário é temporariamente suspensa aos sábados, domingos e feriados, estendendo a liberação para as 24 horas do dia.
- **Validação Geográfica:** Feriados são cruzados contra o calendário local oficial da cidade-base do colaborador, impedindo que feriados de outra localidade afetem a operação.
- **Condição Crítica:** Mesmo em finais de semana e feriados, o usuário **precisa constar na escala** oficial do ERP para que as ocorrências sejam liberadas.

---

## 3. Múltiplas Frentes e Rateio de Horas

Muitas vezes, fiscais e supervisores não dedicam o seu turno a uma única obra. O motor de **Rateio de Apontamentos** automatiza o faturamento fracionado.

- **Fluxo Único de Entrada:** O colaborador lança o turno integral (ex: das 08h às 17h) e sinaliza múltiplas obras no mesmo formulário.
- **Fatiamento Automático:** O backend intercepta o envio, divide matematicamente a carga horária pelo número de centros de custo, e desmembra silenciosamente o lançamento em sub-apontamentos individuais (ex: 3 horas exatas faturadas para cada um dos 3 clientes atendidos no dia).
- **Consistência:** Isso mantém a usabilidade limpa para quem lança e a precisão impecável para quem fatura.

---

## 4. Segurança, Privilégios e Insecure Direct Object Reference (IDOR)

A aplicação blinda os apontamentos através de um modelo de **Row Level Security (Visibilidade Isolada)**.

- **Lançamento Próprio vs. Terceiros:** Apenas perfis com cargo de gestão (Admin, Gerencial, SAC) podem lançar horas em nome da sua equipe. Para os demais perfis, a API injeta silenciosamente o próprio ID do usuário nos <i>payloads</i> de requisição, anulando manipulações do <i>frontend</i>.
- **Proteção IDOR e Edição Assimétrica:** A matemática é simples: **Nenhum usuário pode editar ou apagar um registro de outro colaborador.** 
  - Até mesmo Gerentes e SAC são impedidos de alterar lançamentos que efetuaram em nome da equipe.
  - Apenas Administradores de alto nível possuem o poder de sobrepor essa trava em casos excepcionais e de força maior.

---

## 5. Lifecycle e Central de Aprovação

O fechamento de um apontamento exige validação cruzada, garantindo que o sistema atue como juiz e carrasco contra anomalias.

- **Status Padrão:** Todo lançamento nasce como `EM ANÁLISE`.
- **Fila de Aprovação (Anti-Self-Approval):** Os registros sobem para a tela de avaliação dos gestores, **porém**, a query de banco de dados aplica a trava Anti-Autoaprovação. É impossível que um coordenador ou gerente visualize (e consequentemente aprove) suas próprias horas.
- **Aprovação / Rejeição:** Um gestor valida e o status avança para `APROVADO` ou cai para `REJEITADO`.
- **Limites e Solicitação de Ajuste:** Caso um colaborador erre demasiadamente e atinja o limite máximo de edições em um apontamento que ainda não foi aprovado, a edição é bloqueada. O <i>status</i> muda para `SOLICITAÇÃO DE AJUSTE`, transferindo a responsabilidade da alteração final obrigatoriamente para as mãos da liderança.
