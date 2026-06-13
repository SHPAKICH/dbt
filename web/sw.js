// Service Worker для PWA и push-уведомлений
const CACHE_NAME = 'dbt-v1';

function resolveSafeAppUrl(rawUrl) {
  if (!rawUrl || typeof rawUrl !== 'string') return null;
  try {
    const parsed = new URL(rawUrl, self.location.origin);
    if (!['http:', 'https:'].includes(parsed.protocol)) return null;
    if (parsed.origin !== self.location.origin) return null;
    return parsed.pathname + parsed.search + parsed.hash;
  } catch (e) {
    return null;
  }
}

self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {
  let data = { title: 'DBT', body: 'Уведомление' };
  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data.body = event.data.text();
    }
  }
  const options = {
    body: data.body || '',
    icon: '/favicon.ico',
    badge: '/favicon.ico',
    tag: data.tag || 'dbt-notification',
    requireInteraction: !!data.requireInteraction,
    data: data.url ? { url: data.url } : {},
  };
  event.waitUntil(
    self.registration.showNotification(data.title || 'DBT', options)
  );
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  const safeUrl = resolveSafeAppUrl(event.notification.data && event.notification.data.url);
  if (safeUrl) {
    event.waitUntil(
      self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
        for (const client of clientList) {
          if (client.url === '/' || client.url.indexOf(self.location.origin) === 0) {
            client.navigate(safeUrl);
            return client.focus();
          }
        }
        if (self.clients.openWindow) {
          return self.clients.openWindow(safeUrl);
        }
      })
    );
  }
});
