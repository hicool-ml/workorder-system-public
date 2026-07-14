// =============================================================
// PWA 注册 + 推送通知 + 未读闪烁
// =============================================================

(function() {
    'use strict';

    // ---------- Service Worker 注册 ----------
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then((reg) => {
                    console.log('[PWA] Service Worker 注册成功', reg.scope);

                    // 检查是否有新版本
                    reg.addEventListener('updatefound', () => {
                        const newWorker = reg.installing;
                        if (newWorker) {
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // 有新版本，通知 SW 立即激活
                                    newWorker.postMessage({ type: 'SKIP_WAITING' });
                                }
                            });
                        }
                    });
                })
                .catch((err) => console.warn('[PWA] SW 注册失败', err));
        });

        // SW 更新后自动刷新
        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (!refreshing) {
                refreshing = true;
                window.location.reload();
            }
        });
    }

    // ---------- 推送通知 ----------

    /**
     * 请求通知权限
     */
    window.pwaEnableNotifications = async function() {
        if (!('Notification' in window)) {
            alert('当前浏览器不支持通知功能');
            return false;
        }

        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            console.log('[PWA] 通知权限已授予');

            // 订阅推送（如果支持 PushManager）
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                try {
                    const reg = await navigator.serviceWorker.ready;
                    // VAPID key 在生产环境中由服务器提供
                    // 这里只做权限管理，实际推送依赖服务端
                    const subscription = await reg.pushManager.getSubscription();
                    if (!subscription) {
                        console.log('[PWA] 尚未订阅推送服务');
                    }
                } catch (e) {
                    console.warn('[PWA] 推送订阅失败', e);
                }
            }

            // 发送一条欢迎通知
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.ready;
                reg.showNotification('通知已开启', {
                    body: '您将收到工单相关的系统通知',
                    icon: '/icons/icon-192.png',
                    badge: '/icons/badge-72.png',
                    tag: 'welcome',
                });
            }

            return true;
        } else if (permission === 'denied') {
            alert('通知权限被拒绝，请在浏览器设置中手动开启');
            return false;
        }

        return false;
    };

    // ---------- 标题闪烁（未读通知提醒） ----------

    let originalTitle = null;
    let blinkTimer = null;
    let unreadCount = 0;

    /**
     * 开始标题闪烁
     */
    window.pwaStartTitleBlink = function(count) {
        unreadCount = count;
        if (blinkTimer) return; // 已经在闪
        if (originalTitle === null) originalTitle = document.title;

        let toggle = false;
        blinkTimer = setInterval(() => {
            if (document.visibilityState === 'visible') {
                pwaStopTitleBlink();
                return;
            }
            toggle = !toggle;
            document.title = toggle
                ? '(' + unreadCount + ') 新通知'
                : originalTitle;
        }, 1000);
    };

    /**
     * 停止标题闪烁
     */
    window.pwaStopTitleBlink = function() {
        if (blinkTimer) {
            clearInterval(blinkTimer);
            blinkTimer = null;
        }
        if (originalTitle !== null) {
            document.title = originalTitle;
        }
    };

    // 页面重新可见时停止闪烁
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            pwaStopTitleBlink();
        }
    });

    // ---------- 轮询未读通知（离线 PWA 通知） ----------

    let pollTimer = null;

    function startNotificationPoll() {
        if (pollTimer) clearInterval(pollTimer);

        // 每 30 秒检查未读通知
        pollTimer = setInterval(async () => {
            try {
                const resp = await fetch('/api/notifications/unread-count', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!resp.ok) return;

                const data = await resp.json();
                const count = data.count || 0;

                // 更新通知角标
                updateBadges(count);

                // 如果有未读且页面不可见，闪烁标题
                if (count > 0 && document.visibilityState !== 'visible') {
                    pwaStartTitleBlink(count);
                }
            } catch (e) {
                // 静默失败
            }
        }, 30000);
    }

    /**
     * 更新所有通知角标
     */
    function updateBadges(count) {
        const badges = document.querySelectorAll('[id^="notif-badge"]');
        badges.forEach((badge) => {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        });
    }

    // 启动轮询
    window.addEventListener('load', startNotificationPoll);
})();
