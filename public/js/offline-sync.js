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
    const url = form.getAttribute("action") || window.location.href;
    const method = (form.querySelector('input[name="_method"]')?.value || form.getAttribute("method") || "POST").toUpperCase();
    
    // Coleta os dados convertendo FormData para Objeto JSON
    const formData = new FormData(form);
    const dados = parseFormData(formData);
    
    // Mostra o loader
    const pageLoader = document.getElementById("page-loader");
    if(pageLoader) {
        pageLoader.classList.remove('hidden');
        pageLoader.style.opacity = '1';
    }

    // 1. Tratamento Offline Explícito
    if (!navigator.onLine) {
        salvarOffline(dados, url, method);
        mostrarAlerta("Sem internet. Apontamento salvo no dispositivo e será sincronizado depois.", "warning");
        finalizarFluxoUI(form, true);
        return;
    }

    // 2. Tentativa Online com prevenção de Lie-Fi (Conexão instável)
    try {
        const response = await fetch(url, {
            method: method === 'PUT' || method === 'PATCH' ? 'POST' : method, // Fetch lida com PUT/PATCH enviando _method no body via Laravel, mas para JSON puro no fetch, podemos enviar method diretamente, porém o Laravel aceita method override via header X-HTTP-Method-Override
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest", // Força Laravel a responder com JSON
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                ...(method === 'PUT' || method === 'PATCH' ? { "X-HTTP-Method-Override": method } : {})
            },
            body: JSON.stringify(dados)
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
        salvarOffline(dados, url, method);
        mostrarAlerta("Conexão instável. Apontamento salvo no dispositivo e será sincronizado depois.", "warning");
        finalizarFluxoUI(form, true);
    }
}

// Converte FormData para JSON (trata inputs do tipo array)
function parseFormData(formData) {
    const dados = {};
    for (const [key, value] of formData.entries()) {
        // LocalStorage não suporta bem arquivos (Blob). Ignoramos se houver file vazio.
        if (value instanceof File && value.name === '') continue; 

        // Se a chave terminar em [], é um array (ex: name="projetos[]")
        const isArrayKey = key.endsWith('[]');
        const cleanKey = isArrayKey ? key.slice(0, -2) : key;

        if (isArrayKey || dados[cleanKey]) {
            if (!Array.isArray(dados[cleanKey])) {
                dados[cleanKey] = dados[cleanKey] ? [dados[cleanKey]] : [];
            }
            dados[cleanKey].push(value);
        } else {
            dados[cleanKey] = value;
        }
    }
    return dados;
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
            // Injeta flags para o Laravel saber que é um sync retroativo
            const payload = {
                ...item.dados,
                _is_offline_sync: true,
                _timestamp_original: item.timestamp_original
            };
            
            const response = await fetch(item.url, {
                method: item.method === 'PUT' || item.method === 'PATCH' ? 'POST' : item.method,
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    ...(item.method === 'PUT' || item.method === 'PATCH' ? { "X-HTTP-Method-Override": item.method } : {})
                },
                body: JSON.stringify(payload)
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
