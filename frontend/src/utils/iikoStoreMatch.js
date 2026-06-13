/** Нормализация строки для сравнения названий точки и склада iiko. */
export function normalizeMatchText(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/ё/g, 'е')
    .replace(/[^a-zа-я0-9\s]/gi, ' ')
    .replace(/\s+/g, ' ')
    .trim()
}

function tokenOverlapScore(a, b) {
  const aw = a.split(' ').filter((w) => w.length >= 2)
  const bw = new Set(b.split(' ').filter((w) => w.length >= 2))
  if (!aw.length || !bw.size) return 0
  let hit = 0
  for (const w of aw) {
    if (bw.has(w)) hit += 10 + w.length
  }
  return hit
}

function resolveSearchName(locationName, iikoName) {
  const explicit = String(iikoName || '').trim()
  if (explicit) return explicit
  return String(locationName || '').trim()
}

/**
 * Подбор склада iiko по названию точки в нашей системе.
 *
 * @param {Array<{ id: string, name?: string }>} stores
 * @param {string} locationName — например «Меркурий»
 * @param {string|null|undefined} iikoName — явное название в iiko из таблицы locations
 * @returns {string|null} id склада iiko
 */
export function pickIikoStoreForLocation(stores, locationName, iikoName = null) {
  if (!Array.isArray(stores) || !stores.length) {
    return null
  }

  const search = resolveSearchName(locationName, iikoName)
  if (!search) return null

  const searchNorm = normalizeMatchText(search)

  for (const store of stores) {
    const name = normalizeMatchText(store.name)
    if (name && name === searchNorm) {
      return store.id
    }
  }

  const warehouseStores = stores.filter((s) => normalizeMatchText(s.name).includes('склад'))
  const candidates = warehouseStores.length ? warehouseStores : stores

  let bestId = null
  let bestScore = 0

  for (const store of candidates) {
    const name = normalizeMatchText(store.name)
    if (!name) continue

    let score = 0
    if (name === searchNorm) {
      score += 5000
    }
    if (name.includes(searchNorm)) {
      score += 1000 + searchNorm.length * 10
    }
    if (searchNorm.includes(name)) {
      score += 800 + name.length * 8
    }
    score += tokenOverlapScore(searchNorm, name)
    if (searchNorm.length >= 3) {
      const searchCompact = searchNorm.replace(/\s/g, '')
      const nameCompact = name.replace(/\s/g, '')
      if (nameCompact.includes(searchCompact)) {
        score += 200 + searchCompact.length * 5
      }
    }

    if (score > bestScore) {
      bestScore = score
      bestId = store.id
    }
  }

  return bestScore >= 30 ? bestId : null
}

/**
 * Подбор подразделения iiko (точка продаж) по названию точки в нашей системе.
 *
 * @param {Array<{ id: string, name?: string }>} departments
 * @param {string} locationName
 * @param {string|null|undefined} iikoName — явное название в iiko из таблицы locations
 * @returns {string|null}
 */
export function pickIikoDepartmentForLocation(departments, locationName, iikoName = null) {
  if (!Array.isArray(departments) || !departments.length) {
    return null
  }

  const search = resolveSearchName(locationName, iikoName)
  if (!search) return null

  const searchNorm = normalizeMatchText(search)

  for (const dep of departments) {
    const name = normalizeMatchText(dep.name)
    if (name && name === searchNorm) {
      return dep.id
    }
  }

  let bestId = null
  let bestScore = 0

  for (const dep of departments) {
    const name = normalizeMatchText(dep.name)
    if (!name) continue

    let score = 0
    if (name === searchNorm) {
      score += 5000
    }
    if (name.includes(searchNorm)) {
      score += 1000 + searchNorm.length * 10
    }
    if (searchNorm.includes(name)) {
      score += 800 + name.length * 8
    }
    score += tokenOverlapScore(searchNorm, name)
    if (searchNorm.length >= 3) {
      const searchCompact = searchNorm.replace(/\s/g, '')
      const nameCompact = name.replace(/\s/g, '')
      if (nameCompact.includes(searchCompact)) {
        score += 200 + searchCompact.length * 5
      }
    }

    if (score > bestScore) {
      bestScore = score
      bestId = dep.id
    }
  }

  return bestScore >= 30 ? bestId : null
}
