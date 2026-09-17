# 1. Arquitetura e Integração

O Sistema de Timesheet foi desenhado para atuar em conjunto com o ERP matriz corporativo. Entender o fluxo de dados e os limites de onde o sistema atua como dono da informação é fundamental para o desenvolvimento e manutenção das integrações.

## 1. Fluxo de Dados e Topologia (Master vs Slave)

A arquitetura do banco de dados e as integrações via API obedecem a uma regra estrita de "Fonte da Verdade":

### Onde o Timesheet é a Fonte da Verdade (Master)
O sistema é o proprietário **exclusivo** das informações relacionadas ao trabalho diário. Todo dado que nasce no Timesheet, pertence ao Timesheet.
- **Apontamentos**: Registros de entrada, saída, local, projeto associado, veículos utilizados e horas de plantão/sobreaviso.
- **Auditoria de Apontamentos**: O histórico de edições e o versionamento de apontamentos alterados.
- **Autorização (Spatie)**: Embora o ERP possa definir o cargo, a matriz de acessos detalhada no Timesheet (quem pode aprovar o quê, visualizações cruzadas) é governada internamente no Laravel pelo pacote `spatie/laravel-permission`.

### Onde o Timesheet é Repositório (Slave)
O sistema atua como consumidor e espelho de dados estruturais que nascem e são mantidos pelo ERP, incluindo:
- **Usuários e Colaboradores**: O cadastro de pessoas é feito no ERP.
- **Estruturas Base**: Projetos, Obras, Setores, Clientes e Centros de Custo são sincronizados para permitir a amarração dos apontamentos.
*Nota*: Para garantir a integridade, o cadastro destas entidades é bloqueado no Timesheet (ou feito de maneira apenas emergencial e provisória), exigindo rotinas diárias/noturnas de Sync (via Jobs no Laravel).

## 2. Fluxo de Login e Integração de Usuários (SSO)

Para evitar duplicidade de senhas e atrito com os funcionários operacionais, a autenticação foi terceirizada para o ERP e opera de forma exclusiva sem senhas locais (Passwordless).
O fluxo de Single Sign-On (SSO) foi atualizado para um modelo de 2 passos e adota a técnica de **Just-In-Time (JIT) Provisioning**:

1. **Acesso**: O colaborador tenta acessar o Timesheet. Se não estiver logado, é redirecionado para a tela de interceptação que encaminha a autenticação para o ERP.
2. **Autenticação (2 Passos)**: O ERP valida as credenciais e retorna um *Ticket* seguro ao endpoint de Callback do Timesheet (`/auth/sso/callback`).
3. **Validação Rigorosa de Ticket (API-to-API)**: O `SsoController` no Laravel entra em contato *server-side* com o ERP enviando o Ticket. 
   - **Blindagem de Payload:** O sistema realiza uma checagem restrita para garantir que o payload não esteja vazio ou malformado (evitando falhas silenciosas 500 ou injeções nulas).
   - Se válido e os dados vitais (`email`, `id_usuario`) estiverem presentes, o ERP retorna o Payload seguro contendo: `id_usuario`, `nome`, `email`, `nivel_acesso`, e `tangerino_employee_id`.
4. **JIT Provisioning (Criação/Atualização)**:
   - Se o usuário não existir na base do Timesheet (`firstOrNew` pelo `email`), ele é criado na hora, recebendo os dados do Payload.
   - O Controller **sempre** atualiza as informações vitais (`connect_user_id`, `solides_id`) para espelhar as alterações no ERP. O `solides_id` é mapeado primariamente a partir da chave `tangerino_employee_id` recebida no ERP, garantindo cruzamento com os sistemas legados de RH.
   - Os níveis de acesso (`nivel_acesso`) vindos do ERP são traduzidos (`nivel_planejamento`) e espelhados imediatamente convertendo-os para Roles do Spatie via `$user->syncRoles()`. Caso venha vazio na primeira vez, uma fallback role de `OPERACIONAL` é atribuída automaticamente e travas de segurança do Spatie são garantidas.
5. **Sessão Segura**: O usuário é logado no Laravel utilizando a façade nativa (`Auth::login($user)`) e redirecionado ao Hub de Gestão (Painel), tudo de forma transparente em milissegundos.

## 3. Modelo de Dados (Diagrama)

Para ilustrar de forma visual a estrutura descrita nesta documentação (Master vs Slave, amarrações de Colaboradores, Apontamentos e Histórico), você pode consultar o diagrama atualizado da nossa base de dados.

O diagrama detalha os principais relacionamentos:
- Vínculo do **User** com o **Colaborador**.
- Relação **Master** do **Apontamento** com Setores, Obras, e Veículos.
- O espelho N:N de **Setores Vinculados**.

![Diagrama do Banco de Dados](diagram-database.png)