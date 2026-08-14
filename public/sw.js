const CACHE_NAME = 'timesheet-cache-v1';

// Recursos estáticos principais e a rota do formulário
const ASSETS_TO_CACHE = [
    '/',
    '/apontamentos/novo',
    '/js/offline-sync.js',
    '/manifest.json',
    '/favicon.ico'
];

// Evento de Instalação: Cache "Pre-fetch"
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[ServiceWorker] Fazendo pre-fetch dos assets essenciais');
            // Usamos try/catch no addAll para evitar que um único erro quebre a instalação inteira
            return cache.addAll(ASSETS_TO_CACHE).catch(err => console.warn('[ServiceWorker] Aviso no addAll:', err));
        })
    );
    self.skipWaiting();
});

// Evento de Ativação: Limpeza de caches antigos
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[ServiceWorker] Removendo cache antigo:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Evento de Interceptação de Requisições (Fetch)
self.addEventListener('fetch', (event) => {
    // Ignoramos requisições não-GET (POST, PUT, DELETE), pois nosso localStorage já lida com elas
    if (event.request.method !== 'GET') return;

    // Se a requisição for para uma página HTML (Navegação)
    if (event.request.headers.get('accept').includes('text/html')) {
        // Estratégia: Network First, falling back to cache
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    // Atualiza o cache dinamicamente com o novo HTML, se for uma resposta válida (200)
                    if (response && response.status === 200 && response.type === 'basic') {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Fallback para o cache se a rede cair. Se não achar a exata requisição, tenta a rota /apontamentos/novo
                    return caches.match(event.request).then((cachedResponse) => {
                        return cachedResponse || caches.match('/apontamentos/novo');
                    });
                })
        );
    } else {
        // Estratégia: Cache First para Assets (CSS, JS, Imagens, Webfonts)
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                
                // Se não estiver no cache, busca na rede e guarda (Cache dinâmico)
                return fetch(event.request).then((response) => {
                    // Salva a resposta de assets (basic = local, cors = cdn permitida)
                    if (response && response.status === 200 && (response.type === 'basic' || response.type === 'cors')) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                }).catch(() => {
                    // Não há fallback específico para uma imagem/fonte que falhou e não estava no cache
                    console.log('[ServiceWorker] Falha ao buscar asset na rede:', event.request.url);
                });
            })
        );
    }
});
