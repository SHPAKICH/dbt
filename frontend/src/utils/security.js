import DOMPurify from 'dompurify'

const HTML_SANITIZE_OPTIONS = {
  USE_PROFILES: { html: true },
  FORBID_TAGS: ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'link', 'meta', 'base'],
  FORBID_ATTR: ['style'],
}

export function sanitizeHtml(html) {
  return DOMPurify.sanitize(String(html || ''), HTML_SANITIZE_OPTIONS)
}

export function sanitizeAppRedirect(redirect) {
  if (typeof redirect !== 'string') return '/'
  const trimmed = redirect.trim()
  if (!trimmed.startsWith('/') || trimmed.startsWith('//') || /[\r\n]/.test(trimmed)) return '/'
  return trimmed
}

export function sanitizeTrustedUrl(rawUrl, allowedOrigins = []) {
  if (typeof rawUrl !== 'string' || !rawUrl.trim()) return ''
  const fallbackOrigin = typeof window !== 'undefined' ? window.location.origin : 'http://localhost'

  try {
    const url = new URL(rawUrl, fallbackOrigin)
    if (!['http:', 'https:'].includes(url.protocol)) return ''

    const trustedOrigins = new Set([fallbackOrigin])
    for (const originCandidate of allowedOrigins) {
      if (!originCandidate) continue
      try {
        trustedOrigins.add(new URL(originCandidate, fallbackOrigin).origin)
      } catch {
        // Ignore malformed configured origins.
      }
    }

    return trustedOrigins.has(url.origin) ? url.toString() : ''
  } catch {
    return ''
  }
}

export function sanitizePushPath(rawUrl) {
  if (typeof rawUrl !== 'string') return ''
  const trimmed = rawUrl.trim()
  if (!trimmed) return ''
  return sanitizeAppRedirect(trimmed) === trimmed ? trimmed : ''
}
