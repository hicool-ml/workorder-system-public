// =============================================================
// Service Worker - 工单系统 PWA
// 功能：离线缓存骨架页、推送通知、后台同步
// =============================================================

const CACHE_VERSION = 'v1.0.0';
const STATIC_CACHE = `workorder-static-${CACHE_VERSION}`;
const RUNTIME_CACHE = `workorder-runtime-${CACHE_VERSION}`;

// 需要预缓存的静态资源（离线时可用的骨架页）
const PRECACHE_URLS = [
    '/',
    '/offline.html',
];

// ====================== 安装：预缓存 ======================
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
            .catch(() => {})
    );
});

// ====================== 激活：清理旧缓存 ======================
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => {
                return Promise.all(
                    keys
                        .filter((key) => !key.includes(CACHE_VERSION))
                        .map((key) => caches.delete(key))
                );
            })
            .then(() => self.clients.claim())
    );
});

// ====================== 请求拦截：缓存策略 ======================
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // 只处理 GET 请求
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // 同源请求：网络优先，失败回退缓存
    if (url.origin === self.location.origin) {
        // API 请求不缓存
        if (url.pathname.startsWith('/api/')) return;

        event.respondWith(
            fetch(request)
                .then((response) => {
                    // 成功则缓存副本
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(RUNTIME_CACHE).then((cache) => {
                            cache.put(request, clone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // 网络失败，尝试缓存
                    return caches.match(request).then((cached) => {
                        if (cached) return cached;
                        // 页面请求失败时返回离线页
                        if (request.mode === 'navigate') {
                            return caches.match('/offline.html');
                        }
                    });
                })
        );
    }
});

// ====================== 推送通知 ======================
self.addEventListener('push', (event) => {
    let data = { title: '工单通知', body: '您有新的通知', icon: '/icons/icon-192.png' };

    try {
        if (event.data) {
            data = event.data.json();
        }
    } catch (e) {
        if (event.data) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/icons/icon-192.png',
        badge: '/icons/badge-72.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/',
            workorderId: data.workorder_id || null,
        },
        tag: data.tag || 'workorder-notification',
        renotify: true,
        requireInteraction: data.important || false,
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// ====================== 通知点击 ======================
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // 如果已有打开的窗口，聚焦它并跳转
                for (const client of clientList) {
                    if ('focus' in client) {
                        client.navigate(targetUrl);
                        return client.focus();
                    }
                }
                // 否则打开新窗口
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
    );
});

// ====================== 消息通信 ======================
self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
