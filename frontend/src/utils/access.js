/** Полный доступ к админ-панели (главный админ, админ, супер-доступ). */
export function hasAdminAccess(user) {
  if (!user) return false
  return Boolean(user.isAdmin || user.isMainAdmin)
}

/** Остатки на складе (iiko): администратор, управляющий, менеджер точки. */
export function canAccessStock(user) {
  if (!user) return false
  return Boolean(
    user.isAdmin || user.position === 'manager' || user.position === 'location_manager',
  )
}

/** Чеки из iiko — те же роли, что и остатки. */
export function canAccessChecks(user) {
  return canAccessStock(user)
}

/** Отчёты по продажам (iiko) — те же роли. */
export function canAccessSalesReports(user) {
  return canAccessStock(user)
}
