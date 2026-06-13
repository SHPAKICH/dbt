export const baseURL = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '')
const TOKEN_KEY = 'api_token'

export function getToken() {
  return typeof localStorage !== 'undefined' ? localStorage.getItem(TOKEN_KEY) : null
}
export function setToken(token) {
  if (typeof localStorage !== 'undefined') localStorage.setItem(TOKEN_KEY, token)
}
export function clearToken() {
  if (typeof localStorage !== 'undefined') localStorage.removeItem(TOKEN_KEY)
}

function parseFilenameFromDisposition(disposition) {
  if (!disposition) return null

  const utf8Match = disposition.match(/filename\*\s*=\s*UTF-8''([^;]+)/i)
  if (utf8Match?.[1]) {
    try {
      return decodeURIComponent(utf8Match[1])
    } catch {
      return utf8Match[1]
    }
  }

  const simpleMatch = disposition.match(/filename\s*=\s*"([^"]+)"|filename\s*=\s*([^;]+)/i)
  return simpleMatch?.[1] || simpleMatch?.[2] || null
}

function request(url, options = {}) {
  const fullUrl = url.startsWith('http') ? url : `${baseURL.replace(/\/$/, '')}${url}`
  const isFormData = options.body instanceof FormData
  const headers = {
    'Accept': 'application/json',
    ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
    ...options.headers,
  }
  const token = getToken()
  if (token) headers['Authorization'] = `Bearer ${token}`
  const body = options.body && typeof options.body === 'object' && !isFormData
    ? JSON.stringify(options.body)
    : options.body
  return fetch(fullUrl, {
    ...options,
    credentials: 'include',
    headers,
    body,
  }).then(async (res) => {
    const data = await res.json().catch(() => ({}))
    if (!res.ok) {
      if (res.status === 401) clearToken()
      const fallbackMessage = res.status === 413
        ? 'Фото слишком большого размера для сервера. Попробуйте отправить сжатое изображение.'
        : res.statusText
      const err = new Error(data.message || fallbackMessage)
      err.status = res.status
      err.data = data
      throw err
    }
    return data
  })
}

export const api = {
  auth: {
    login(body) {
      return request('/api/v1/auth/login', { method: 'POST', body })
    },
    logout() {
      return request('/api/v1/auth/logout', { method: 'POST' })
    },
    user() {
      return request('/api/v1/auth/user')
    },
    forgotPassword(body) {
      return request('/api/v1/auth/forgot-password', { method: 'POST', body })
    },
    validateResetToken(token) {
      return request(`/api/v1/auth/validate-reset-token?token=${encodeURIComponent(token)}`)
    },
    resetPassword(body) {
      return request('/api/v1/auth/reset-password', { method: 'POST', body })
    },
  },
  profile: {
    get() {
      return request('/api/v1/profile')
    },
    telegramStatus() {
      return request('/api/v1/profile/telegram')
    },
    telegramLink() {
      return request('/api/v1/profile/telegram-link', { method: 'POST' })
    },
    telegramUnlink() {
      return request('/api/v1/profile/telegram-unlink', { method: 'POST' })
    },
    /** URL для загрузки файла аватара (нужен fetch с Authorization) */
    avatarFileUrl() {
      return `${baseURL}/api/v1/profile/avatar-file`
    },
    update(body) {
      return request('/api/v1/profile/update', { method: 'POST', body })
    },
    uploadAvatar(file) {
      const form = new FormData()
      form.append('avatarFile', file)
      return request('/api/v1/profile/avatar', {
        method: 'POST',
        body: form,
        headers: {}, // без Content-Type — браузер подставит multipart boundary
      })
    },
    getCards() {
      return request('/api/v1/profile/cards')
    },
    cardSelect(templateId) {
      return request('/api/v1/profile/cards/select', { method: 'POST', body: { templateId } })
    },
    cardRemove() {
      return request('/api/v1/profile/cards/remove', { method: 'POST' })
    },
    cardVisibility(templateId, visible) {
      return request('/api/v1/profile/cards/visibility', { method: 'POST', body: { templateId, visible } })
    },
  },
  schedule: {
    locations() {
      return request('/api/v1/schedule/locations')
    },
    grid(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/schedule/grid' + (q ? '?' + q : ''))
    },
    updateCell(body) {
      return request('/api/v1/schedule/update-cell', { method: 'POST', body })
    },
    helpers(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/schedule/helpers' + (q ? '?' + q : ''))
    },
    sendInvite(body) {
      return request('/api/v1/schedule/send-invite', { method: 'POST', body })
    },
    myInvites() {
      return request('/api/v1/schedule/my-invites')
    },
    acceptInvite(body) {
      return request('/api/v1/schedule/accept-invite', { method: 'POST', body })
    },
    declineInvite(body) {
      return request('/api/v1/schedule/decline-invite', { method: 'POST', body })
    },
  },
  availability: {
    my(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/availability/my' + (q ? '?' + q : ''))
    },
    team(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/availability/team' + (q ? '?' + q : ''))
    },
    updateCell(body) {
      return request('/api/v1/availability/update-cell', { method: 'POST', body })
    },
  },
  guru: {
    dashboard() {
      return request('/api/v1/guru/dashboard')
    },
    search(q, limit) {
      const params = new URLSearchParams({ q })
      if (limit) params.set('limit', String(limit))
      return request('/api/v1/guru/search?' + params.toString())
    },
    tests(params = {}) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/guru/tests' + (q ? '?' + q : ''))
    },
    test(id) {
      return request(`/api/v1/guru/tests/${id}`)
    },
    testQuestions(id) {
      return request(`/api/v1/guru/tests/${id}/questions`)
    },
    testSubmit(body) {
      return request('/api/v1/guru/test/submit', { method: 'POST', body })
    },
    testLeaderboard(id) {
      return request(`/api/v1/guru/tests/${id}/leaderboard`)
    },
    testResults(id) {
      return request(`/api/v1/guru/tests/${id}/results`)
    },
    result(id) {
      return request(`/api/v1/guru/result/${id}`)
    },
    cards(params = {}) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/guru/cards' + (q ? '?' + q : ''))
    },
    createCard(formData) {
      return request('/api/v1/guru/cards/create', { method: 'POST', body: formData })
    },
    card(id) {
      return request(`/api/v1/guru/cards/${id}`)
    },
    updateCard(id, formData) {
      const token = getToken()
      const base = baseURL.replace(/\/$/, '')
      return fetch(base + `/api/v1/guru/cards/${id}/update`, {
        method: 'POST',
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        body: formData,
        credentials: 'include',
      }).then(async (res) => {
        const data = await res.json().catch(() => ({}))
        if (!res.ok) throw Object.assign(new Error(data.message || res.statusText), { data })
        return data
      })
    },
    deleteCard(id) {
      return request(`/api/v1/guru/cards/${id}`, { method: 'DELETE' })
    },
    createTest(formData) {
      return request('/api/v1/guru/tests/create', { method: 'POST', body: formData })
    },
    getTestQuestionsEdit(id) {
      return request(`/api/v1/guru/tests/${id}/questions-edit`)
    },
    updateTest(id, formData) {
      const token = getToken()
      const base = baseURL.replace(/\/$/, '')
      return fetch(base + `/api/v1/guru/tests/${id}/update`, {
        method: 'POST',
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        body: formData,
        credentials: 'include',
      }).then(async (res) => {
        const data = await res.json().catch(() => ({}))
        if (!res.ok) throw Object.assign(new Error(data.message || res.statusText), { data })
        return data
      })
    },
    deleteTest(id) {
      return request(`/api/v1/guru/tests/${id}`, { method: 'DELETE' })
    },
    lessons(params = {}) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/guru/lessons' + (q ? '?' + q : ''))
    },
    lesson(id) {
      return request(`/api/v1/guru/lessons/${id}`)
    },
    createLesson(body) {
      return request('/api/v1/guru/lessons/create', { method: 'POST', body })
    },
    updateLesson(id, body) {
      return request(`/api/v1/guru/lessons/${id}`, { method: 'PUT', body })
    },
    uploadLessonImage(file) {
      const formData = new FormData()
      formData.append('file', file)
      return request('/api/v1/guru/lessons/upload-image', { method: 'POST', body: formData })
    },
    lessonMarkRead(id) {
      return request(`/api/v1/guru/lessons/${id}/mark-read`, { method: 'POST' })
    },
    trainingProgress(params = {}) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/guru/training-progress' + (q ? '?' + q : ''))
    },
  },
  admin: {
    dashboard() {
      return request('/api/v1/admin/dashboard')
    },
    users() {
      return request('/api/v1/admin/users')
    },
    createUser(body) {
      return request('/api/v1/admin/users', { method: 'POST', body })
    },
    user(id) {
      return request(`/api/v1/admin/users/${id}`)
    },
    updateUser(id, body) {
      return request(`/api/v1/admin/users/${id}`, { method: 'PUT', body })
    },
    deleteUser(id) {
      return request(`/api/v1/admin/users/${id}`, { method: 'DELETE' })
    },
    locations() {
      return request('/api/v1/admin/locations')
    },
    analyticsLocations() {
      return request('/api/v1/admin/analytics-locations')
    },
    createLocation(body) {
      return request('/api/v1/admin/locations', { method: 'POST', body })
    },
    location(id) {
      return request(`/api/v1/admin/locations/${id}`)
    },
    updateLocation(id, body) {
      return request(`/api/v1/admin/locations/${id}`, { method: 'PUT', body })
    },
    deleteLocation(id) {
      return request(`/api/v1/admin/locations/${id}`, { method: 'DELETE' })
    },
    pushStats() {
      return request('/api/v1/admin/push/stats')
    },
    downloadDbBackup() {
      const token = getToken()
      const base = baseURL.replace(/\/$/, '')
      return fetch(base + '/api/v1/admin/db-backup', {
        method: 'POST',
        headers: {
          Accept: 'application/octet-stream',
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        credentials: 'include',
      }).then(async (res) => {
        if (!res.ok) {
          const data = await res.json().catch(() => ({}))
          throw Object.assign(new Error(data.message || res.statusText), { data, status: res.status })
        }

        const blob = await res.blob()
        const disposition = res.headers.get('Content-Disposition')
        const filename = parseFilenameFromDisposition(disposition) || `db_backup_${Date.now()}.sql.gz`

        return { blob, filename }
      })
    },
    pushSend(body) {
      return request('/api/v1/admin/push/send', { method: 'POST', body })
    },
    news() {
      return request('/api/v1/admin/news')
    },
    createNews(body) {
      return request('/api/v1/admin/news', { method: 'POST', body })
    },
    updateNews(id, body) {
      return request(`/api/v1/admin/news/${id}`, { method: 'PUT', body })
    },
    deleteNews(id) {
      return request(`/api/v1/admin/news/${id}`, { method: 'DELETE' })
    },
    profileCards() {
      return request('/api/v1/admin/profile-cards')
    },
    profileCard(id) {
      return request(`/api/v1/admin/profile-cards/${id}`)
    },
    createProfileCard(body) {
      return request('/api/v1/admin/profile-cards', { method: 'POST', body })
    },
    updateProfileCard(id, body) {
      return request(`/api/v1/admin/profile-cards/${id}`, { method: 'PUT', body })
    },
    deleteProfileCard(id) {
      return request(`/api/v1/admin/profile-cards/${id}`, { method: 'DELETE' })
    },
    uploadProfileCardBg(file) {
      const formData = new FormData()
      formData.append('file', file)
      const token = getToken()
      const base = baseURL.replace(/\/$/, '')
      return fetch(base + '/api/v1/admin/profile-cards/upload-bg', {
        method: 'POST',
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        body: formData,
        credentials: 'include',
      }).then(async (res) => {
        const data = await res.json().catch(() => ({}))
        if (!res.ok) throw Object.assign(new Error(data.message || res.statusText), { data })
        return data
      })
    },
    uploadNewsImage(file) {
      const formData = new FormData()
      formData.append('file', file)
      const token = getToken()
      const base = baseURL.replace(/\/$/, '')
      return fetch(base + '/api/v1/admin/news/upload-image', {
        method: 'POST',
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        body: formData,
        credentials: 'include',
      }).then(async (res) => {
        const data = await res.json().catch(() => ({}))
        if (!res.ok) throw Object.assign(new Error(data.message || res.statusText), { data })
        return data
      })
    },
    positionRates() {
      return request('/api/v1/admin/position-rates')
    },
    updatePositionRates(body) {
      return request('/api/v1/admin/position-rates', { method: 'PUT', body })
    },
    kroBands() {
      return request('/api/v1/admin/kro-bands')
    },
    updateKroBands(body) {
      return request('/api/v1/admin/kro-bands', { method: 'PUT', body })
    },
    locationKro(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/admin/location-kro' + (q ? '?' + q : ''))
    },
    setLocationKro(body) {
      return request('/api/v1/admin/location-kro', { method: 'POST', body })
    },
  },
  supply: {
    locations() {
      return request('/api/v1/supply/locations')
    },
    products() {
      return request('/api/v1/supply/products')
    },
    createOrder(body) {
      return request('/api/v1/supply/orders', { method: 'POST', body })
    },
    order(id) {
      return request(`/api/v1/supply/orders/${id}`)
    },
    getExportUrl(orderId) {
      const base = import.meta.env.VITE_API_URL || (typeof window !== 'undefined' ? window.location.origin : '')
      return `${base.replace(/\/$/, '')}/api/v1/supply/orders/${orderId}/export`
    },
  },
  daily: {
    locations() {
      return request('/api/v1/daily/locations')
    },
    users() {
      return request('/api/v1/daily/users')
    },
    reports(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/daily/reports' + (q ? '?' + q : ''))
    },
    update(body) {
      return request('/api/v1/daily/update', { method: 'POST', body })
    },
    getExportUrl(locationId, month) {
      const base = import.meta.env.VITE_API_URL || (typeof window !== 'undefined' ? window.location.origin : '')
      return `${base.replace(/\/$/, '')}/api/v1/daily/export?location_id=${locationId}&month=${encodeURIComponent(month)}`
    },
  },
  writeOff: {
    locations() {
      return request('/api/v1/write-off/locations')
    },
    entries(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/write-off/entries' + (q ? '?' + q : ''))
    },
    save(body) {
      return request('/api/v1/write-off/save', { method: 'POST', body })
    },
  },
  push: {
    subscribe(body) {
      return request('/api/v1/push/subscribe', { method: 'POST', body })
    },
    unsubscribe(endpoint) {
      return request('/api/v1/push/unsubscribe', { method: 'POST', body: { endpoint } })
    },
  },
  news: {
    list() {
      return request('/api/v1/news')
    },
    get(id) {
      return request(`/api/v1/news/${id}`)
    },
  },
  payroll: {
    locations() {
      return request('/api/v1/payroll/locations')
    },
    kro(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/payroll/kro' + (q ? '?' + q : ''))
    },
    setKro(body) {
      return request('/api/v1/payroll/kro', { method: 'POST', body })
    },
    calculations(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/payroll/calculations' + (q ? '?' + q : ''))
    },
    myDaily(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/payroll/my-daily' + (q ? '?' + q : ''))
    },
    formula() {
      return request('/api/v1/payroll/formula')
    },
  },
  shiftTasks: {
    locations() {
      return request('/api/v1/shift-tasks/locations')
    },
    tasks(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/shift-tasks/tasks?' + q)
    },
    create(body) {
      return request('/api/v1/shift-tasks/create', { method: 'POST', body })
    },
    complete(formData) {
      return request('/api/v1/shift-tasks/complete', { method: 'POST', body: formData, headers: {} })
    },
    remove(id) {
      return request(`/api/v1/shift-tasks/delete?id=${id}`, { method: 'DELETE' })
    },
    history(params) {
      const q = new URLSearchParams(params).toString()
      return request('/api/v1/shift-tasks/history?' + q)
    },
  },
  photoReport: {
    locations() {
      return request('/api/v1/photo-report/locations')
    },
    items(reportType) {
      return request(`/api/v1/photo-report/items?report_type=${reportType}`)
    },
    photos(locationId, reportType, reportDate) {
      const q = new URLSearchParams({
        location_id: String(locationId),
        report_type: reportType,
        report_date: reportDate,
      }).toString()
      return request('/api/v1/photo-report/photos?' + q)
    },
    upload(formData) {
      return request('/api/v1/photo-report/upload', { method: 'POST', body: formData, headers: {} })
    },
    deletePhoto(id) {
      return request(`/api/v1/photo-report/delete?id=${id}`, { method: 'DELETE' })
    },
    overview(reportDate) {
      const q = new URLSearchParams({ report_date: reportDate }).toString()
      return request('/api/v1/photo-report/overview?' + q)
    },
  },
  iikoStock: {
    test(iiko) {
      return request('/api/v1/iiko-stock/test', { method: 'POST', body: { iiko } })
    },
    stores(body) {
      return request('/api/v1/iiko-stock/stores', { method: 'POST', body })
    },
    balance(body) {
      return request('/api/v1/iiko-stock/balance', { method: 'POST', body })
    },
    productDocuments(body) {
      return request('/api/v1/iiko-stock/product-documents', { method: 'POST', body })
    },
    cloudTest(body) {
      return request('/api/v1/iiko-stock/cloud-test', { method: 'POST', body })
    },
    cloudOrganizations(body) {
      return request('/api/v1/iiko-stock/cloud-organizations', { method: 'POST', body })
    },
    cloudStores(body) {
      return request('/api/v1/iiko-stock/cloud-stores', { method: 'POST', body })
    },
    documentsList(body) {
      return request('/api/v1/iiko-stock/documents-list', { method: 'POST', body })
    },
    documentById(body) {
      return request('/api/v1/iiko-stock/document-by-id', { method: 'POST', body })
    },
  },
  iikoChecks: {
    departments(body) {
      return request('/api/v1/iiko-checks/departments', { method: 'POST', body })
    },
    list(body) {
      return request('/api/v1/iiko-checks/list', { method: 'POST', body })
    },
    detail(body) {
      return request('/api/v1/iiko-checks/detail', { method: 'POST', body })
    },
    shiftSummary(body) {
      return request('/api/v1/iiko-checks/shift-summary', { method: 'POST', body })
    },
  },
  iikoSalesReports: {
    departments(body) {
      return request('/api/v1/iiko-sales-reports/departments', { method: 'POST', body })
    },
    nomenclature(body) {
      return request('/api/v1/iiko-sales-reports/nomenclature', { method: 'POST', body })
    },
    report(body) {
      return request('/api/v1/iiko-sales-reports/report', { method: 'POST', body })
    },
  },
  accounting: {
    /** Список всех активных локаций для левой панели чата */
    locations() {
      return request('/api/v1/accounting/locations')
    },
    /** Последнее сообщение по каждой локации (для превью в сайдбаре) */
    lastMessages() {
      return request('/api/v1/accounting/last-messages')
    },
    /** Сообщения конкретной локации */
    messages(locationId, params = {}) {
      const q = new URLSearchParams({ location_id: locationId, ...params }).toString()
      return request('/api/v1/accounting/messages?' + q)
    },
    /** Отправка сообщения (FormData: text, location_id, files[]) */
    sendMessage(formData) {
      return request('/api/v1/accounting/messages', {
        method: 'POST',
        body: formData,
        headers: {},
      })
    },
    /** Поставить/снять реакцию на сообщение */
    reactMessage(body) {
      return request('/api/v1/accounting/react', { method: 'POST', body })
    },
  },
}
