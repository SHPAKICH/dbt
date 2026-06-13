const STORAGE_KEY = 'dbt_iiko_settings'
const RESERVES_KEY = 'dbt_iiko_reserves'

export function loadIikoSettings() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return { baseUrl: '', login: '', password: '', defaultStoreId: '' }
    }
    const s = JSON.parse(raw)
    return {
      baseUrl: s.baseUrl || '',
      login: s.login || '',
      password: s.password || '',
      defaultStoreId: s.defaultStoreId || '',
    }
  } catch {
    return { baseUrl: '', login: '', password: '', defaultStoreId: '' }
  }
}

export function saveIikoSettings(patch) {
  const current = loadIikoSettings()
  const next = { ...current, ...patch }
  if (patch.password === '') {
    next.password = current.password
  }
  localStorage.setItem(
    STORAGE_KEY,
    JSON.stringify({
      baseUrl: (next.baseUrl || '').trim(),
      login: (next.login || '').trim(),
      password: next.password,
      defaultStoreId: next.defaultStoreId || '',
    }),
  )
  return next
}

export function iikoSettingsComplete(s = loadIikoSettings()) {
  return Boolean(s.baseUrl && s.login && s.password)
}

export function getIikoCredentialsPayload() {
  const s = loadIikoSettings()
  if (!iikoSettingsComplete(s)) {
    return null
  }
  return {
    baseUrl: s.baseUrl,
    login: s.login,
    password: s.password,
  }
}

export function loadReserves() {
  try {
    const raw = localStorage.getItem(RESERVES_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

export function saveReserve(entry) {
  const list = loadReserves()
  const idx = list.findIndex(
    (r) => r.storeId === entry.storeId && r.productId === entry.productId,
  )
  const row = {
    storeId: entry.storeId,
    productId: entry.productId,
    reserve: Number(entry.reserve) || 0,
    productName: entry.productName,
    productCode: entry.productCode,
    productNum: entry.productNum,
    unit: entry.unit,
    multiplicity: entry.multiplicity,
    notes: entry.notes,
  }
  if (idx >= 0) {
    list[idx] = { ...list[idx], ...row }
  } else {
    list.push(row)
  }
  localStorage.setItem(RESERVES_KEY, JSON.stringify(list))
  return list
}

export function reservesForStore(storeId) {
  return loadReserves().filter((r) => r.storeId === storeId)
}
