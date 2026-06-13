/**
 * Converts a card styleConfig JSON object into an inline CSS style object
 * for zero-cost client-side rendering. No server rendering needed.
 */

export function cardConfigToStyle(cfg) {
  if (!cfg || typeof cfg !== 'object') return {}
  const s = {}

  // Background
  const bg = cfg.bg
  if (bg) {
    if (bg.type === 'gradient' && bg.gradient) {
      const g = bg.gradient
      const stops = (g.stops || []).map(st => `${st.color} ${st.pos}%`).join(', ')
      if (g.type === 'radial') {
        s.background = `radial-gradient(circle, ${stops})`
      } else {
        s.background = `linear-gradient(${g.angle ?? 135}deg, ${stops})`
      }
    } else if (bg.type === 'image' && bg.image?.url) {
      const img = bg.image
      const parts = [`url(${img.url})`]
      s.backgroundImage = parts.join(', ')
      s.backgroundSize = img.size || 'cover'
      s.backgroundPosition = img.position || 'center'
      s.backgroundRepeat = img.repeat || 'no-repeat'
      if (bg.color) {
        s.backgroundColor = bg.color
      }
    } else if (bg.color) {
      s.background = bg.color
    }
  }

  // Border
  const border = cfg.border
  if (border) {
    if (border.width > 0) {
      s.border = `${border.width}px ${border.style || 'solid'} ${border.color || '#d4af37'}`
    }
    if (border.radius != null) {
      s.borderRadius = `${border.radius}px`
    }
  }

  // Box shadow
  const shadow = cfg.shadow
  if (shadow?.enabled) {
    s.boxShadow = `${shadow.x ?? 0}px ${shadow.y ?? 4}px ${shadow.blur ?? 12}px ${shadow.spread ?? 0}px ${shadow.color || 'rgba(0,0,0,0.15)'}`
  }

  // Padding
  const pad = cfg.padding
  if (pad) {
    s.padding = `${pad.top ?? 12}px ${pad.right ?? 16}px ${pad.bottom ?? 12}px ${pad.left ?? 16}px`
  }

  return s
}

export function cardNameStyle(cfg) {
  if (!cfg?.name) return {}
  const n = cfg.name
  const s = {}
  if (n.color) s.color = n.color
  if (n.fontSize) s.fontSize = `${n.fontSize}px`
  if (n.fontWeight) s.fontWeight = n.fontWeight
  if (n.fontFamily && n.fontFamily !== 'inherit') s.fontFamily = n.fontFamily
  if (n.textShadow) s.textShadow = n.textShadow
  if (n.letterSpacing) s.letterSpacing = `${n.letterSpacing}px`
  return s
}

export function cardStatusStyle(cfg) {
  if (!cfg?.status) return {}
  const st = cfg.status
  const s = {}
  if (st.color) s.color = st.color
  if (st.fontSize) s.fontSize = `${st.fontSize}px`
  if (st.fontWeight) s.fontWeight = st.fontWeight
  return s
}

export function cardAvatarStyle(cfg) {
  if (!cfg?.avatar) return {}
  const a = cfg.avatar
  const s = {}
  if (a.size) { s.width = `${a.size}px`; s.height = `${a.size}px` }
  if (a.borderWidth) s.border = `${a.borderWidth}px solid ${a.borderColor || '#fff'}`
  if (a.borderRadius != null) s.borderRadius = `${a.borderRadius}%`
  if (a.shadow) s.boxShadow = a.shadow
  return s
}

export function cardEffectClass(cfg) {
  if (!cfg?.effect || cfg.effect === 'none') return ''
  return `card-effect-${cfg.effect}`
}

export const defaultStyleConfig = {
  bg: { type: 'gradient', color: '#1a1a2e', gradient: { type: 'linear', angle: 135, stops: [{ color: '#667eea', pos: 0 }, { color: '#764ba2', pos: 100 }] }, image: { url: '', size: 'cover', position: 'center', repeat: 'no-repeat' } },
  border: { width: 0, style: 'solid', color: '#d4af37', radius: 12 },
  shadow: { enabled: true, x: 0, y: 4, blur: 16, spread: 0, color: 'rgba(0,0,0,0.2)' },
  name: { color: '#ffffff', fontSize: 15, fontWeight: '600', fontFamily: 'inherit', textShadow: '', letterSpacing: 0 },
  status: { color: 'rgba(255,255,255,0.8)', fontSize: 13, fontWeight: 'normal' },
  avatar: { size: 44, borderWidth: 2, borderColor: 'rgba(255,255,255,0.5)', borderRadius: 50, shadow: '' },
  padding: { top: 16, right: 16, bottom: 16, left: 16 },
  effect: 'none',
}

export const effectOptions = [
  { value: 'none', label: 'Без эффекта' },
  { value: 'shimmer', label: 'Мерцание' },
  { value: 'glow', label: 'Свечение' },
  { value: 'pulse', label: 'Пульсация' },
  { value: 'shine', label: 'Блик' },
]

export const gradientPresets = [
  { name: 'Фиолетовый', stops: [{ color: '#667eea', pos: 0 }, { color: '#764ba2', pos: 100 }] },
  { name: 'Закат', stops: [{ color: '#f093fb', pos: 0 }, { color: '#f5576c', pos: 100 }] },
  { name: 'Океан', stops: [{ color: '#4facfe', pos: 0 }, { color: '#00f2fe', pos: 100 }] },
  { name: 'Лес', stops: [{ color: '#38ef7d', pos: 0 }, { color: '#11998e', pos: 100 }] },
  { name: 'Золото', stops: [{ color: '#f7971e', pos: 0 }, { color: '#ffd200', pos: 100 }] },
  { name: 'Рассвет', stops: [{ color: '#ffecd2', pos: 0 }, { color: '#fcb69f', pos: 100 }] },
  { name: 'Космос', stops: [{ color: '#0f0c29', pos: 0 }, { color: '#302b63', pos: 50 }, { color: '#24243e', pos: 100 }] },
  { name: 'Огонь', stops: [{ color: '#f83600', pos: 0 }, { color: '#f9d423', pos: 100 }] },
  { name: 'Северное сияние', stops: [{ color: '#43e97b', pos: 0 }, { color: '#38f9d7', pos: 100 }] },
  { name: 'Ночной', stops: [{ color: '#141e30', pos: 0 }, { color: '#243b55', pos: 100 }] },
  { name: 'Розовый', stops: [{ color: '#ee9ca7', pos: 0 }, { color: '#ffdde1', pos: 100 }] },
  { name: 'Карамель', stops: [{ color: '#a18cd1', pos: 0 }, { color: '#fbc2eb', pos: 100 }] },
]

export const fontFamilyOptions = [
  { value: 'inherit', label: 'Системный' },
  { value: "'Inter', sans-serif", label: 'Inter' },
  { value: "'Georgia', serif", label: 'Georgia' },
  { value: "'Courier New', monospace", label: 'Courier' },
  { value: "'Segoe UI', sans-serif", label: 'Segoe UI' },
  { value: "'Trebuchet MS', sans-serif", label: 'Trebuchet' },
]
