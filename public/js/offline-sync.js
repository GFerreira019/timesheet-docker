document.addEventListener("DOMContentLoaded", () => {
    // Seleciona o formulário de apontamento
    const formApontamento = document.getElementById("apontamentoForm");
    
    if (formApontamento) {
        formApontamento.addEventListener("submit", handleFormSubmit);
    }

    // Tenta sincronizar qualquer apontamento que ficou na fila na carga da página
    sincronizarFila();
});

// Listener para quando o dispositivo reconecta (WiFi/4G volta)
window.addEventListener("online", () => {
    console.log("Conexão restabelecida. Tentando sincronizar apontamentos...");
    sincronizarFila();
});

async function handleFormSubmit(event) {
    event.preventDefault(); // Previne o reload padrão da página
    
    const form = event.target;

    // 1. Bloqueio de Duplo Clique (Double Submit Prevention)
    const submitBtn = form.querySelector('button[type="submit"]');
    let originalBtnText = '';
    if (submitBtn) {
        originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Salvando...';
    }

    const url = form.getAttribute("action") || window.location.href;
    const method = (form.querySelector('input[name="_method"]')?.value || form.getAttribute("method") || "POST").toUpperCase();
    
    const formData = new FormData(form);
    const dadosUrlEncoded = new URLSearchParams(formData).toString();
    
    // Mostra o loader
    const pageLoader = document.getElementById("page-loader");
    if(pageLoader) {
        pageLoader.classList.remove('hidden');
        pageLoader.style.opacity = '1';
    }

    // Função auxiliar para reabilitar o botão em caso de erro
    const reabilitarBotao = () => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    };

    // 1. Tratamento Offline Explícito
    if (!navigator.onLine) {
        salvarOffline(dadosUrlEncoded, url, method);
        mostrarAlerta("Sem internet. Apontamento salvo no dispositivo e será sincronizado depois.", "warning");
        finalizarFluxoUI(form, true);
        return;
    }

    // 2. Tentativa Online com prevenção de Lie-Fi (Conexão instável)
    try {
        const response = await fetch(url, {
            method: method === 'PUT' || method === 'PATCH' ? 'POST' : method,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                ...(method === 'PUT' || method === 'PATCH' ? { "X-HTTP-Method-Override": method } : {})
            },
            body: dadosUrlEncoded
        });

        if (response.ok || response.status === 201) {
            mostrarAlerta("Apontamento salvo com sucesso!", "success");
            finalizarFluxoUI(form, true); 
        } 
        else if (response.status >= 500) {
            // Servidor caiu ou timeout de gateway. Tratamos como instabilidade.
            throw new Error("Instabilidade no servidor (Erro 5xx)");
        } 
        else if (response.status === 422) {
            // Erro de validação do Laravel Request
            const erros = await response.json();
            console.error("Erros de validação:", erros);
            exibirErrosValidacao(erros);
            
            reabilitarBotao(); // Reabilita para o usuário arrumar e tentar novamente
            
            if(pageLoader) {
                pageLoader.classList.add('hidden');
            }
        } 
        else {
            throw new Error(`Erro inesperado: HTTP ${response.status}`);
        }

    } catch (error) {
        // Caiu aqui = falha na rede (net::ERR_INTERNET_DISCONNECTED) ou timeout forçado
        console.warn("Falha de comunicação, jogando para a fila offline. Erro:", error);
        console.error("Motivo do Catch:", error.message);
        
        reabilitarBotao(); // Reabilita em caso de erro da própria rede ou timeout
        
        salvarOffline(dadosUrlEncoded, url, method);
        mostrarAlerta("Conexão instável. Apontamento salvo no dispositivo e será sincronizado depois.", "warning");
        finalizarFluxoUI(form, true);
    }
}



// A Fila no LocalStorage
function salvarOffline(dados, url, method) {
    let fila = JSON.parse(localStorage.getItem("fila_apontamentos") || "[]");
    
    const item = {
        id_local: 'sync_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9),
        url: url,
        method: method,
        dados: dados,
        // CRÍTICO: Armazena a hora real em que o cara clicou em salvar
        timestamp_original: new Date().toISOString() 
    };
    
    fila.push(item);
    localStorage.setItem("fila_apontamentos", JSON.stringify(fila));
}

// O Motor de Sincronização
async function sincronizarFila() {
    if (!navigator.onLine) return;
    
    let fila = JSON.parse(localStorage.getItem("fila_apontamentos") || "[]");
    if (fila.length === 0) return;
    
    let itensSincronizados = [];
    
    for (const item of fila) {
        try {
            // Como item.dados agora é uma string urlencoded, injetamos as flags reconstruindo os parâmetros
            let urlParams;
            if (typeof item.dados === 'string') {
                urlParams = new URLSearchParams(item.dados);
            } else {
                // Fallback de transição: caso o LocalStorage ainda tenha itens antigos como Objeto
                urlParams = new URLSearchParams();
                for (const key in item.dados) {
                    if (Array.isArray(item.dados[key])) {
                        item.dados[key].forEach(val => urlParams.append(key + '[]', val));
                    } else {
                        urlParams.append(key, item.dados[key]);
                    }
                }
            }
            
            urlParams.append('_is_offline_sync', 'true');
            urlParams.append('_timestamp_original', item.timestamp_original);
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(item.url, {
                method: item.method === 'PUT' || item.method === 'PATCH' ? 'POST' : item.method,
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                    ...(item.method === 'PUT' || item.method === 'PATCH' ? { "X-HTTP-Method-Override": item.method } : {})
                },
                body: urlParams.toString()
            });

            // Se salvou com sucesso OU se o Laravel recusou de vez (Ex: 422 validação impossível de passar)
            if (response.ok || response.status === 201 || response.status === 422) {
                itensSincronizados.push(item.id_local);
            }
        } catch (error) {
            console.error(`Falha ao sincronizar item ${item.id_local}. Ele continuará na fila.`, error);
        }
    }
    
    // Limpa os itens que deram certo
    if (itensSincronizados.length > 0) {
        fila = fila.filter(i => !itensSincronizados.includes(i.id_local));
        localStorage.setItem("fila_apontamentos", JSON.stringify(fila));
        
        if (fila.length === 0) {
            mostrarAlerta("Seus apontamentos pendentes foram sincronizados com sucesso!", "success");
            // Só recarrega se estivermos na página de apontamentos ou histórico
            if (window.location.pathname.includes('/apontamentos') || window.location.pathname.includes('/historico')) {
                setTimeout(() => window.location.reload(), 1500);
            }
        }
    }
}

// --- Funções Auxiliares de UI ---
function mostrarAlerta(mensagem, tipo) {
    // Tenta usar Toastify se existir
    if (typeof Toastify !== 'undefined') {
        Toastify({
            text: mensagem,
            duration: 3000,
            close: true,
            gravity: "top", 
            position: "right",
            style: {
                background: tipo === 'success' ? "#10b981" : (tipo === 'warning' ? "#f59e0b" : "#ef4444"),
            }
        }).showToast();
    } else {
        alert(mensagem); 
    }
}

function exibirErrosValidacao(erros) {
    const container = document.getElementById('validation-errors-container');
    if (!container) {
        mostrarAlerta("Verifique os dados informados.", "error");
        return;
    }

    let html = `
        <div class="mb-5 px-4 py-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl text-sm">
            <p class="font-bold mb-1">Corrija os erros abaixo:</p>
            <ul class="list-disc list-inside space-y-1">
    `;

    // A resposta do Laravel Request (422) geralmente vem em json.errors
    const mensagens = erros.errors || erros;
    for (const campo in mensagens) {
        if (mensagens.hasOwnProperty(campo)) {
            const erroMensagem = Array.isArray(mensagens[campo]) ? mensagens[campo][0] : mensagens[campo];
            html += `<li>${erroMensagem}</li>`;
        }
    }

    html += `</ul></div>`;
    container.innerHTML = html;
    
    // Scroll para o topo para ver o erro
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function finalizarFluxoUI(form, recarregar = false) {
    if (recarregar) {
        // Usa o redirect do backend se houver ou dá reload na página
        window.location.href = '/historico'; // Default redirect para histórico (onde o backend costuma jogar)
    } else {
        form.reset();
        const pageLoader = document.getElementById("page-loader");
        if(pageLoader) {
            pageLoader.classList.add('hidden');
        }
    }
}
