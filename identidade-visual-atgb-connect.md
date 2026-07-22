# Identidade Visual — ATGB Connect

> **Documento de Especificações Técnicas**  
> **Autor:** [ATGB SISTEMAS](https://atgbsistemas.com.br)  
> **Versão:** 1.0.0  
> **Data:** 13/03/2026  
> **Sistema:** ATGB Connect — Plataforma de Gestão Integrada

---

## 1. Visão Geral

O ATGB Connect é uma plataforma web de gestão integrada que utiliza um **sistema de temas dual** (escuro/claro) implementado via **CSS Custom Properties** (variáveis CSS). O tema escuro é o padrão, inspirado na paleta **Slate** do Tailwind CSS, transmitindo uma identidade profissional e tecnológica.

### 1.1 Princípios de Design

- **Consistência** — Todos os componentes usam exclusivamente CSS variables para cores, garantindo coerência entre páginas
- **Acessibilidade** — Contraste WCAG AA, suporte a `prefers-reduced-motion`, `prefers-contrast: high`, focus-visible, skip-links e labels `sr-only`
- **Responsividade** — Layout adaptativo para Desktop (1920px+), Laptop (1024px), Tablet (768px) e Mobile (375px)
- **Performance** — Transições suaves com `transition: 0.2s ease`, sem animações pesadas
- **Segurança** — `noindex, nofollow` em páginas internas, sanitização de inputs com `htmlspecialchars()`

---

## 2. Paleta de Cores

### 2.1 Tema Escuro (Padrão) — `:root`

| Variável               | Valor HEX     | Uso                                      |
|------------------------|---------------|------------------------------------------|
| `--bg-primary`         | `#0f172a`     | Fundo principal da página (body)         |
| `--bg-secondary`       | `#1e293b`     | Fundo de cards, modais, sidebar          |
| `--bg-tertiary`        | `#334155`     | Fundo de inputs, headers de modais       |
| `--bg-card`            | `rgba(15,23,42,0.5)` | Cards com transparência          |
| `--border-color`       | `#334155`     | Bordas de inputs, cards, separadores     |
| `--text-primary`       | `#ffffff`     | Títulos, texto principal                 |
| `--text-secondary`     | `#94a3b8`     | Labels, texto auxiliar                   |
| `--text-tertiary`      | `#64748b`     | Breadcrumbs, dicas, placeholders         |
| `--accent-primary`     | `#3b82f6`     | Ícones de destaque, links, focus rings   |
| `--accent-secondary`   | `#06b6d4`     | Gradientes secundários                   |
| `--success-bg`         | `rgba(16,185,129,0.18)` | Fundo de alertas de sucesso    |
| `--success-text`       | `#34d399`     | Texto de sucesso                         |
| `--warning-bg`         | `rgba(251,191,36,0.18)` | Fundo de alertas de atenção    |
| `--warning-text`       | `#fbbf24`     | Texto de atenção                         |
| `--info-bg`            | `rgba(59,130,246,0.18)` | Fundo de alertas informativos  |
| `--info-text`          | `#93c5fd`     | Texto informativo                        |

### 2.2 Tema Claro — `[data-theme="light"]`

| Variável               | Valor HEX     | Uso                                      |
|------------------------|---------------|------------------------------------------|
| `--bg-primary`         | `#f8fafc`     | Fundo principal da página                |
| `--bg-secondary`       | `#ffffff`     | Fundo de cards, modais, sidebar          |
| `--bg-tertiary`        | `#f1f5f9`     | Fundo de inputs, headers de modais       |
| `--bg-card`            | `#ffffff`     | Cards                                    |
| `--bg-hover`           | `#f8fafc`     | Hover em linhas de tabela, itens         |
| `--bg-active`          | `#e2e8f0`     | Item ativo em sidebar                    |
| `--border-color`       | `#e2e8f0`     | Bordas padrão                            |
| `--border-strong`      | `#cbd5e1`     | Bordas com mais destaque                 |
| `--text-primary`       | `#0f172a`     | Títulos, texto principal                 |
| `--text-secondary`     | `#1e293b`     | Labels, texto auxiliar                   |
| `--text-tertiary`      | `#475569`     | Breadcrumbs, dicas                       |
| `--text-muted`         | `#64748b`     | Placeholders, textos inativos            |
| `--accent-primary`     | `#2563eb`     | Links, ícones de destaque                |
| `--accent-hover`       | `#1d4ed8`     | Hover em botões primários                |
| `--success-bg`         | `#dcfce7`     | Fundo de sucesso                         |
| `--success-text`       | `#166534`     | Texto de sucesso                         |
| `--warning-bg`         | `#fef3c7`     | Fundo de atenção                         |
| `--warning-text`       | `#92400e`     | Texto de atenção                         |
| `--info-bg`            | `#dbeafe`     | Fundo informativo                        |
| `--info-text`          | `#1e40af`     | Texto informativo                        |

### 2.3 Cores de Acento Personalizáveis

O sistema permite que o usuário escolha entre **10 cores de acento**, persistidas via `localStorage`:

| Nome     | Primary   | Secondary |
|----------|-----------|-----------|
| Blue     | `#3b82f6` | `#06b6d4` |
| Purple   | `#a855f7` | `#d946ef` |
| Green    | `#10b981` | `#14b8a6` |
| Orange   | `#f97316` | `#fb923c` |
| Pink     | `#ec4899` | `#f472b6` |
| Red      | `#ef4444` | `#f87171` |
| Yellow   | `#eab308` | `#fbbf24` |
| Indigo   | `#6366f1` | `#818cf8` |
| Teal     | `#14b8a6` | `#2dd4bf` |
| Cyan     | `#06b6d4` | `#22d3ee` |

### 2.4 Cores Funcionais (Status)

| Contexto        | Classe Tailwind     | Cor           | Uso                        |
|-----------------|---------------------|---------------|----------------------------|
| Sucesso         | `bg-green-600`      | `#16a34a`     | Botões de salvar, confirmar|
| Informação      | `bg-blue-600`       | `#2563eb`     | Botões primários, links    |
| Alerta          | `bg-yellow-500`     | `#eab308`     | Avisos, pendências         |
| Perigo          | `bg-red-600`        | `#dc2626`     | Excluir, erros             |
| Roxo            | `bg-purple-600`     | `#9333ea`     | Propostas, orçamentos      |

---

## 3. Tipografia

### 3.1 Fonte Principal

O sistema utiliza a **stack de fontes padrão do Tailwind CSS** (sem fonte customizada externa), priorizando a fonte nativa do sistema operacional:

```
font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 
             "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

### 3.2 Hierarquia de Tamanhos

| Elemento         | Classe Tailwind  | Tamanho    | Peso        | Uso                        |
|------------------|------------------|------------|-------------|----------------------------|
| Título de página | `text-2xl`       | 1.5rem     | `font-bold` | H1 principal da página     |
| Título de seção  | `text-xl`        | 1.25rem    | `font-bold` | H2 de seções               |
| Título de modal  | `text-lg`        | 1.125rem   | `font-bold` | Header de modais           |
| Título de card   | `text-sm`        | 0.875rem   | `font-semibold` | Subtítulos de seção   |
| Label de campo   | `text-xs`        | 0.75rem    | `font-medium` | Labels de inputs/selects |
| Texto de ajuda   | `text-xs`        | 0.75rem    | normal      | Dicas, contadores          |
| Corpo de texto   | `text-sm`        | 0.875rem   | normal      | Texto geral, tabelas       |

### 3.3 Responsividade Tipográfica

| Breakpoint      | h1            | h2           | h3           |
|-----------------|---------------|--------------|--------------|
| ≥ 1920px        | 1.1rem base   | —            | —            |
| 768px – 1023px  | 1.5rem        | 1.25rem      | —            |
| 576px – 767px   | 1.25rem       | 1.125rem     | —            |
| ≤ 575px         | 1.125rem      | 1rem         | 0.875rem     |
| ≤ 375px         | 1rem          | —            | —            |

---

## 4. Logotipos

| Arquivo              | Tema    | Uso                                  |
|----------------------|---------|--------------------------------------|
| `assets/logowhite.png` | Escuro | Logo branco para fundos escuros      |
| `assets/logo.png`      | Claro  | Logo escuro para fundos claros       |
| `assets/favico.png`    | Ambos  | Favicon do navegador                 |

A troca de logo é automática via classe `.theme-logo` e o `ThemeManager` JavaScript.

---

## 5. Componentes de Interface

### 5.1 Layout de Página (Full-Width)

Todas as páginas de assistência técnica usam **tela inteira** (`$no_sidebar = true`):

```php
$no_sidebar = true; // Forçar tela cheia sem sidebar
```

```html
<body class="no-sidebar" style="background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%); color: var(--text-primary);">
    <div class="w-full h-screen">
        <main class="w-full flex flex-col overflow-hidden">
```

### 5.2 Header

```html
<header style="background: var(--bg-secondary); border-color: var(--border-color);" 
        class="px-8 py-4 flex items-center justify-between border-b">
    <!-- Breadcrumb -->
    <div style="color: var(--text-secondary);" class="text-sm flex items-center gap-2">
        <span style="color: var(--text-tertiary);">Módulo</span>
        <span class="mx-2">/</span>
        <span style="color: var(--accent-primary);">Página Atual</span>
    </div>
    <!-- Usuário + Theme Toggle -->
</header>
```

### 5.3 Cards

```html
<div style="background: var(--bg-card); border-color: var(--border-color);" 
     class="rounded-lg p-6 border">
```

- **Border radius:** `rounded-lg` (0.5rem) para cards, `rounded-xl` (0.75rem) para modais
- **Padding:** `p-6` (1.5rem) padrão
- **Sombra interativa:** `box-shadow: 0 10px 25px rgba(15, 23, 42, 0.35)`

### 5.4 Modais

#### Estrutura padrão:

```html
<!-- Overlay -->
<div id="nomeModal" class="fixed inset-0 hidden z-50 flex items-center justify-center p-4" 
     style="background: rgba(0,0,0,0.6);">
    <!-- Container -->
    <div style="background: var(--bg-secondary); border-color: var(--border-color); max-height: 90vh;" 
         class="w-full max-w-2xl rounded-xl shadow-2xl border overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b" 
             style="border-color: var(--border-color); background: var(--bg-tertiary);">
            <h3 class="text-lg font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <i class="fas fa-icon" style="color: var(--accent-primary);"></i>
                Título do Modal
            </h3>
            <button class="p-2 rounded-lg hover:bg-slate-700/30 transition" style="color: var(--text-tertiary);">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <!-- Conteúdo -->
        <div class="p-6">...</div>
        <!-- Footer com botões -->
        <div class="flex justify-end gap-3 px-6 py-4 border-t" style="border-color: var(--border-color);">
            <button class="px-4 py-2 text-sm font-medium rounded-lg border hover:bg-slate-700/30 transition" 
                    style="border-color: var(--border-color); color: var(--text-secondary);">Cancelar</button>
            <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save mr-1"></i> Salvar
            </button>
        </div>
    </div>
</div>
```

#### Regras de z-index:

| Componente            | z-index |
|-----------------------|---------|
| Sidebar               | 40      |
| Modal principal        | 50      |
| Modal sobre modal (follow-up) | 60 |
| Modal sobre modal (e-mail)    | 70 |
| Atalhos de teclado     | 100     |

#### Tamanhos de modal:

| Tipo              | Classe          | Largura máxima |
|-------------------|-----------------|----------------|
| Pequeno (e-mail)  | `max-w-md`      | 28rem (448px)  |
| Médio (detalhes)  | `max-w-2xl`     | 42rem (672px)  |
| Grande (edição)   | `max-w-3xl`     | 48rem (768px)  |
| Extra (follow-up) | `max-w-4xl`     | 56rem (896px)  |

### 5.5 Inputs e Formulários

#### Input padrão:

```html
<label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">
    Nome do Campo
</label>
<input type="text" 
       class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" 
       style="background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary);">
```

#### Select padrão:

```html
<select class="w-full px-3 py-2 rounded-lg border text-sm" 
        style="background: var(--bg-secondary); border-color: var(--border-color); color: var(--text-primary);">
```

#### Textarea padrão:

```html
<textarea rows="3" 
          class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" 
          style="background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary);">
</textarea>
```

#### Campo somente leitura:

```html
<input type="text" readonly 
       class="w-full px-3 py-2 rounded-lg border text-sm" 
       style="background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary);">
```

#### Regras:
- **Fundo de input editável:** `var(--bg-tertiary)` ou `var(--bg-secondary)`
- **Fundo de input readonly:** `var(--bg-tertiary)`
- **Border radius:** `rounded-lg` (0.5rem)
- **Padding:** `px-3 py-2`
- **Fonte:** `text-sm` (0.875rem)
- **Focus:** `focus:ring-2 focus:ring-blue-500`
- **Mobile (≤ 575px):** `min-height: 44px` e `font-size: 16px` (previne zoom no iOS)

### 5.6 Botões

#### Botão primário:

```html
<button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
    <i class="fas fa-save mr-1"></i> Salvar
</button>
```

#### Botão secundário (cancelar/fechar):

```html
<button class="px-4 py-2 text-sm font-medium rounded-lg border hover:bg-slate-700/30 transition" 
        style="border-color: var(--border-color); color: var(--text-secondary);">
    Cancelar
</button>
```

#### Botão de ação (sucesso):

```html
<button class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
    <i class="fas fa-plus mr-1"></i> Adicionar
</button>
```

#### Botão perigo:

```html
<button class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
    <i class="fas fa-trash mr-1"></i> Excluir
</button>
```

#### Regras:
- **Border radius:** `rounded-lg` (0.5rem)
- **Padding:** `px-4 py-2` (interno), `px-6 py-2` (ações principais de página)
- **Fonte:** `text-sm font-medium`
- **Transição:** `transition` (0.2s ease padrão)
- **Ícones:** Font Awesome 6, sempre com `mr-1` antes do texto
- **Disabled:** `disabled:opacity-50 disabled:cursor-not-allowed`

### 5.7 Tabelas

```html
<table class="w-full text-sm">
    <thead>
        <tr style="background: var(--bg-tertiary);">
            <th class="py-3 px-3 text-left text-xs font-semibold uppercase" 
                style="color: var(--text-secondary);">Coluna</th>
        </tr>
    </thead>
    <tbody>
        <tr class="border-b hover:bg-slate-800/30 transition" 
            style="border-color: var(--border-color);">
            <td class="py-3 px-3" style="color: var(--text-primary);">Valor</td>
        </tr>
    </tbody>
</table>
```

### 5.8 Alertas / Mensagens

```html
<!-- Sucesso -->
<div class="p-4 bg-green-500/10 border border-green-500/50 rounded-lg flex items-start gap-3">
    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
    <p class="text-green-200 text-sm">Mensagem de sucesso</p>
</div>

<!-- Erro -->
<div class="p-4 bg-red-500/10 border border-red-500/50 rounded-lg flex items-start gap-3">
    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
    <p class="text-red-200 text-sm">Mensagem de erro</p>
</div>
```

### 5.9 Separadores de Seção (dentro de modais/formulários)

```html
<div class="md:col-span-2 mt-4 mb-1">
    <div class="border-t pt-4" style="border-color: var(--border-color);">
        <h4 class="text-sm font-semibold flex items-center gap-2" style="color: var(--text-primary);">
            <i class="fas fa-icon text-blue-500"></i> Título da Seção
        </h4>
        <p class="text-xs mt-1" style="color: var(--text-tertiary);">Descrição auxiliar</p>
    </div>
</div>
```

### 5.10 Toggle de Tema

```html
<button id="themeToggle" class="theme-toggle" title="Alternar tema (Alt+T)">
    <div class="theme-toggle-slider">
        <i class="fas fa-moon"></i>
    </div>
</button>
```

- **Largura:** 60px, **Altura:** 30px
- **Slider:** 24px circular com `var(--accent-primary)`
- **Animação:** bounce de 0.35s ao alternar
- **Atalho:** `Alt+T`

---

## 6. Ícones

### 6.1 Biblioteca

**Font Awesome 6** — carregado localmente via `assets/css/fontawesome.css` para evitar bloqueio de Tracking Prevention.

### 6.2 Ícones de Contexto

| Contexto          | Ícone                   | Cor                    |
|-------------------|-------------------------|------------------------|
| Ticket            | `fa-ticket-alt`         | `var(--accent-primary)` |
| Equipamento       | `fa-tools` / `fa-cog`   | `var(--accent-primary)` |
| Assistência       | `fa-building`           | `var(--accent-primary)` |
| Manutenção        | `fa-wrench`             | `var(--accent-primary)` |
| Acompanhamento    | `fa-clipboard-list`     | `text-green-500`       |
| E-mail            | `fa-envelope`           | `var(--accent-primary)` |
| Salvar            | `fa-save`               | Herda do botão         |
| Editar            | `fa-edit`               | `text-blue-400`        |
| Excluir           | `fa-trash`              | `text-red-400`         |
| Fechar            | `fa-times`              | `var(--text-tertiary)` |
| Busca             | `fa-search`             | Herda do contexto      |
| Filtros           | `fa-filter`             | Herda do contexto      |
| PDF               | `fa-file-pdf`           | Herda do botão         |
| Enviar            | `fa-paper-plane`        | Herda do botão         |
| Voltar            | `fa-arrow-left`         | Herda do contexto      |
| Envio externo     | `fa-arrow-right`        | `text-blue-500`        |
| Retorno externo   | `fa-arrow-left`         | `text-green-500`       |
| Orçamento         | `fa-dollar-sign`        | `text-purple-500`      |

---

## 7. Espaçamento e Grid

### 7.1 Grid de Formulário

```html
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Campos de formulário -->
</div>
```

- **Gap padrão:** `gap-4` (1rem)
- **Campos full-width:** `md:col-span-2`

### 7.2 Espaçamentos Padrão

| Elemento                | Valor     |
|-------------------------|-----------|
| Padding de página       | `p-8` (2rem) |
| Padding de card/modal   | `p-6` (1.5rem) |
| Padding de header modal | `px-6 py-4` |
| Gap entre campos        | `gap-4` (1rem) |
| Gap entre botões        | `gap-3` (0.75rem) |
| Margem de seção         | `mb-6` (1.5rem) |

---

## 8. Responsividade

### 8.1 Breakpoints

| Nome              | Largura           | Comportamento                           |
|-------------------|-------------------|-----------------------------------------|
| Extra Large       | ≥ 1920px          | Container 1800px                        |
| Large             | 1440px – 1919px   | Container 1400px                        |
| Medium-Large      | 1280px – 1439px   | Container 1200px                        |
| Medium (Laptops)  | 1024px – 1279px   | Container 960px, sidebar 220px          |
| Tablet Landscape  | 768px – 1023px    | Sidebar 200px, grid 2 colunas, scroll-x em tabelas |
| Tablet Portrait   | 576px – 767px     | Sidebar oculta (280px slide), grid 1 coluna |
| Mobile            | ≤ 575px           | Sidebar oculta (85vw), inputs 44px min, font 16px |
| Mobile Small      | ≤ 375px           | Container 0.5rem padding, text 0.75rem  |

### 8.2 Regras Mobile

- Inputs: `min-height: 44px` e `font-size: 16px` (previne zoom no iOS)
- Botões: `width: 100%` com `padding: 0.75rem`
- Safe area: `env(safe-area-inset-*)` para dispositivos com notch
- Orientação landscape: sidebar e header compactos quando `max-height: 500px`

---

## 9. Acessibilidade

| Recurso                        | Implementação                                              |
|--------------------------------|------------------------------------------------------------|
| Focus visible                  | `outline: 2px solid var(--accent-primary); offset: 2px`   |
| Skip link                      | `.skip-link` oculto, aparece com foco                     |
| Screen reader only             | Classe `.sr-only` para labels invisíveis                  |
| Reduced motion                 | `@media (prefers-reduced-motion: reduce)` desliga animações|
| High contrast                  | `@media (prefers-contrast: high)` aumenta contraste       |
| Campos obrigatórios            | `<span class="text-red-500">*</span>` + `.required-field::after` |
| Tema Alt+T                     | Atalho de teclado para trocar tema                        |

---

## 10. Arquivos de Estilo e Scripts

| Arquivo                         | Função                                       |
|---------------------------------|----------------------------------------------|
| `assets/css/tailwind.min.css`   | Framework CSS utilitário (Tailwind CSS)       |
| `assets/css/fontawesome.css`    | Ícones Font Awesome 6 (local)                |
| `assets/css/dashboard.css`      | Variáveis de tema, classes utilitárias, responsividade |
| `assets/js/theme-manager.js`    | Gerenciamento de tema (escuro/claro) e cor de acento |
| `assets/js/dashboard.js`        | Scripts globais do dashboard                 |

---

## 11. Anti-Padrões (O que NÃO usar)

| Evitar                                        | Usar em vez disso                                    |
|-----------------------------------------------|------------------------------------------------------|
| `bg-white dark:bg-gray-800`                   | `style="background: var(--bg-secondary);"`           |
| `text-gray-900 dark:text-white`               | `style="color: var(--text-primary);"`                |
| `text-gray-700 dark:text-gray-300`            | `style="color: var(--text-secondary);"`              |
| `border-gray-300 dark:border-gray-600`        | `style="border-color: var(--border-color);"`         |
| `bg-gray-50 dark:bg-gray-700`                 | `style="background: var(--bg-tertiary);"`            |
| `bg-black bg-opacity-50` (overlay)            | `style="background: rgba(0,0,0,0.6);"`              |
| `text-gray-400` (ícone fechar)                | `style="color: var(--text-tertiary);"`               |
| `rounded-md` em modais                        | `rounded-xl` para container, `rounded-lg` para inputs|
| `class="text-blue-500"` para ícone de título  | `style="color: var(--accent-primary);"`              |

---

## 12. Modelo de Página Padrão

```php
<?php
/**
 * ATGB SISTEMAS - Sistema CONNECT
 * [Nome da Página]
 * @author ATGB SISTEMAS (https://atgbsistemas.com.br)
 */
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
require_once 'config/load-config.php';

$no_sidebar = true;
$user_name = $_SESSION['user_name'] ?? 'Usuário';
$user_email = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[Título] - CONNECT</title>
    <link rel="icon" type="image/png" href="assets/favico.png">
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="assets/js/theme-manager.js"></script>
</head>
<body class="no-sidebar" style="background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%); color: var(--text-primary);">
    <div class="w-full h-screen">
        <main class="w-full flex flex-col overflow-hidden">
            <!-- Header -->
            <header style="background: var(--bg-secondary); border-color: var(--border-color);" 
                    class="px-8 py-4 flex items-center justify-between border-b">
                <!-- breadcrumb + user info -->
            </header>
            <!-- Conteúdo -->
            <div class="flex-1 overflow-auto p-8">
                <!-- Conteúdo da página -->
            </div>
        </main>
    </div>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
```

---

## 13. Versionamento

O controle de versão é mantido no arquivo `VERSION` na raiz do projeto e incrementado após cada alteração significativa conforme o workflow `atualizar-version.md`.

---

## 14. Componentes Específicos de Navegação (Dashboard)

Para manter a consistência visual nos módulos internos do sistema (como Dashboard de Clientes, Suporte, Obras, etc.), a estrutura HTML a seguir deve ser replicada rigorosamente para que a IDE compreenda o padrão de menus flutuantes.

---

## 14.1 Header Clean de Módulo (Com Navegação e Perfil)

O cabeçalho superior possui largura máxima `max-w-7xl`, background com gradiente + blur e abriga:

- Botão de voltar;
- Identificação do módulo;
- Controle de tema;
- Informações do usuário;
- Botão de logout.

## HTML

```html
<header class="header-gradient border-b theme-border sticky top-0 z-50 backdrop-blur-lg safe-area-padding">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="home.php" class="p-2 rounded-lg hover:bg-slate-700/50 theme-text-muted hover:text-white transition" title="Voltar ao Início">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-cyan-700 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-white text-lg"></i>
                    </div>

                    <div>
                        <h1 class="text-lg font-bold text-white">Clientes</h1>
                        <p class="text-xs theme-text-muted">Gestão de Clientes e Obras</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-4">

                <button id="themeToggle"
                        class="theme-toggle hidden sm:block"
                        title="Alternar tema">
                    <div class="theme-toggle-slider">
                        <i class="fas fa-moon"></i>
                    </div>
                </button>

                <div class="text-right hidden sm:block">
                    <p class="text-sm font-medium">
                        Gabriel Ferreira De Paula
                    </p>

                    <p class="text-xs theme-text-muted">
                        gpaula@atgbsistemas.com.br
                    </p>
                </div>

                <a href="logout.php"
                   class="p-2 rounded-lg hover:bg-red-500/20 theme-text-muted hover:text-red-400 transition"
                   title="Sair">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </a>

            </div>
        </div>
    </div>
</header>
```

---

## 14.2 Hero Section (Título Flutuante)

O título central da página utiliza a classe `.welcome-container`, responsável pela animação vertical de flutuação.

## HTML

```html
<div class="welcome-container text-center mb-6 sm:mb-8 lg:mb-12">

    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-2 sm:mb-4 theme-text-primary">
        <i class="fas fa-users text-cyan-500 mr-3"></i>
        Módulo <span class="text-cyan-500">Clientes</span>
    </h1>

    <p class="text-base sm:text-lg lg:text-xl theme-text-secondary">
        Gestão de Clientes, Códigos de Obra e Materiais
    </p>

</div>
```

---

## 14.3 Grid e Cards de Acesso (`.module-card`)

Este é o padrão obrigatório para os cards de navegação dos módulos internos.

### Regras

- Utilizar sempre a classe `.module-card`;
- O ícone deve utilizar `.module-icon`;
- A cor principal deve ser alterada dinamicamente através das classes do Tailwind.

### Exemplos

| Contexto | Classe |
|----------|--------|
| Clientes | `text-cyan-500` |
| Financeiro | `text-yellow-500` |
| Obras | `text-orange-500` |
| Suporte | `text-green-500` |
| RH | `text-purple-500` |

---

## HTML

```html
<div class="mb-8">

    <h2 class="text-lg font-bold mb-4 flex items-center gap-2 theme-text-primary">
        <i class="fas fa-users text-cyan-500"></i>
        Gestão de Clientes
    </h2>

    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">

        <a href="lista-clientes.php"
           class="module-card relative theme-bg-card rounded-xl border theme-border p-4 lg:p-6 hover:border-cyan-500/50 transition group">

            <div class="relative z-10">

                <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-cyan-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                    <i class="fas fa-list text-cyan-500 text-xl lg:text-2xl"></i>
                </div>

                <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">
                    Consulta de Clientes
                </h3>

                <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">
                    Visualizar e pesquisar todos os clientes cadastrados no sistema
                </p>

                <div class="flex items-center text-cyan-500 group-hover:opacity-80 transition">
                    <span class="text-xs lg:text-sm font-medium">
                        Acessar
                    </span>

                    <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                </div>

            </div>

        </a>

        <a href="dados-faturamento.php"
           class="module-card relative theme-bg-card rounded-xl border theme-border p-4 lg:p-6 hover:border-yellow-500/50 transition group">

            <div class="relative z-10">

                <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-yellow-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                    <i class="fas fa-file-invoice-dollar text-yellow-500 text-xl lg:text-2xl"></i>
                </div>

                <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">
                    Dados de Faturamento
                </h3>

                <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">
                    Gerenciar dados de faturamento por cliente
                </p>

                <div class="flex items-center text-yellow-500 group-hover:opacity-80 transition">

                    <span class="text-xs lg:text-sm font-medium">
                        Acessar
                    </span>

                    <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>

                </div>

            </div>

        </a>

    </div>

</div>
```

---

# 14.4 Botão de Ação Inferior (Voltar Geral)

## HTML

```html
<div class="text-center mt-8">

    <a href="home.php"
       class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg font-medium transition">

        <i class="fas fa-arrow-left"></i>

        Voltar ao Início

    </a>

</div>
```

---

# 15. Estilos e Animações Obrigatórias (`<style>`)

As regras abaixo **devem obrigatoriamente** ser injetadas pela IDE ou pela página para garantir:

- animação do Hero;
- efeito pseudo-3D dos cards;
- hover avançado;
- responsividade;
- utilitários auxiliares.

## CSS

```css
/* ==========================================================
   Card de Navegação
   ========================================================== */

.module-card {
    transition: all 0.3s ease;
    transform-style: preserve-3d;
}

.module-card:hover:not(.disabled) {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0,0,0,.3);
}

.module-card::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 1rem;

    background: linear-gradient(
        135deg,
        rgba(255,255,255,.1) 0%,
        transparent 50%
    );

    pointer-events: none;
}

/* ==========================================================
   Ícones
   ========================================================== */

.module-icon {
    transition: transform .3s ease;
}

.module-card:hover:not(.disabled) .module-icon {
    transform: scale(1.1) rotate(5deg);
}

/* ==========================================================
   Estado Desabilitado
   ========================================================== */

.module-card.disabled {
    opacity: .5;
    cursor: not-allowed;
}

/* ==========================================================
   Header
   ========================================================== */

.header-gradient {
    background: linear-gradient(
        135deg,
        rgba(30,41,59,.95) 0%,
        rgba(15,23,42,.98) 100%
    );
}

/* ==========================================================
   Hero Animation
   ========================================================== */

@keyframes float {

    0%,100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-10px);
    }

}

.welcome-container {
    animation: float 3s ease-in-out infinite;
}

/* ==========================================================
   Utilitário
   ========================================================== */

.line-clamp-2 {

    display: -webkit-box;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

    overflow: hidden;

}

/* ==========================================================
   Responsividade
   ========================================================== */

@media (max-width:767px){

    .module-card{
        padding:1rem!important;
    }

    .module-card:hover:not(.disabled){
        transform:translateY(-4px) scale(1.01);
    }

    .module-icon{
        width:2.5rem!important;
        height:2.5rem!important;
    }

    .module-icon i{
        font-size:1rem!important;
    }

    .module-card h3{
        font-size:.9rem!important;
    }

    .module-card p{
        font-size:.75rem!important;
    }

}

@media (max-width:575px){

    .welcome-title{
        font-size:1.25rem!important;
    }

    .welcome-subtitle{
        font-size:.875rem!important;
    }

}
```

---

## Resumo da Estrutura Obrigatória

Toda página de módulo deve seguir a seguinte ordem estrutural:

1. `Header`
2. `Hero Section`
3. Grid de módulos (`.module-card`)
4. Botão "Voltar ao Início"
5. `Footer`
6. CSS obrigatório contendo animações, efeitos 3D e responsividade.


> **Documento mantido por:** [ATGB SISTEMAS](https://atgbsistemas.com.br)  
> **Última atualização:** 02/07/2026