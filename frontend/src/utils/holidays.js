/**
 * Официальные нерабочие праздничные дни РФ (производственный календарь).
 * Формат: { month: 1-12, day: 1-31 } или для периода { month, dayStart, dayEnd }.
 */
const HOLIDAYS = [
  { month: 1, dayStart: 1, dayEnd: 8 },   // Новогодние каникулы
  { month: 2, day: 23 },                   // День защитника Отечества
  { month: 3, day: 8 },                   // Международный женский день
  { month: 5, day: 1 },                   // Праздник Весны и Труда
  { month: 5, day: 9 },                   // День Победы
  { month: 6, day: 12 },                  // День России
  { month: 11, day: 4 },                  // День народного единства
]

/**
 * Проверяет, является ли дата (YYYY-MM-DD) официальным праздником РФ.
 * @param {string} dateStr - дата в формате YYYY-MM-DD
 * @returns {boolean}
 */
export function isHoliday(dateStr) {
  if (!dateStr || typeof dateStr !== 'string') return false
  const [y, m, d] = dateStr.split('-').map(Number)
  if (!m || !d) return false
  for (const h of HOLIDAYS) {
    if (h.day !== undefined) {
      if (h.month === m && h.day === d) return true
    } else {
      for (let day = h.dayStart; day <= h.dayEnd; day++) {
        if (h.month === m && day === d) return true
      }
    }
  }
  return false
}
