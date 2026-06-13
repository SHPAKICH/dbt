/** Типы складских документов iikoCloud (движение товаров). */

export type IikoDocumentType =
  | 'IncomingInvoice'
  | 'Writeoff'
  | 'WasteDocument'
  | 'InventoryDocument'
  | 'SalesDocument'
  | 'OutcomingInvoice'

export type IikoDocumentStatusUi = 'posted' | 'draft' | 'deleted' | 'unknown'

export interface IikoDocumentItem {
  productId: string
  productName?: string
  amount: number
  price?: number
  sum?: number
}

export interface IikoDocument {
  id: string
  number: string
  date: string
  documentType: IikoDocumentType | string
  status: string
  comment?: string
  sum: number
  storeId?: string
  storeFromId?: string
  storeToId?: string
  storeFromName?: string
  storeToName?: string
  items: IikoDocumentItem[]
}

export type IikoDocumentFilterType = 'writeoff' | 'sales' | 'inventory' | 'incoming' | ''

export interface IikoDocumentsListParams {
  fromDate: string
  toDate: string
  documentTypes?: IikoDocumentFilterType[]
  statuses?: IikoDocumentStatusUi[]
  storeId?: string
  page?: number
  pageSize?: number
}

export const DOCUMENT_TYPE_LABELS: Record<string, string> = {
  IncomingInvoice: 'Приход',
  Writeoff: 'Списание',
  WasteDocument: 'Списание',
  InventoryDocument: 'Инвентаризация',
  SalesDocument: 'Реализация',
  OutcomingInvoice: 'Реализация',
}

export const DOCUMENT_FILTER_OPTIONS = [
  { value: '', label: 'Все типы' },
  { value: 'incoming', label: 'Приход' },
  { value: 'sales', label: 'Реализация' },
  { value: 'writeoff', label: 'Списание' },
  { value: 'inventory', label: 'Инвентаризация' },
] as const

export const STATUS_FILTER_OPTIONS = [
  { value: '', label: 'Все статусы' },
  { value: 'posted', label: 'Проведён' },
  { value: 'draft', label: 'Черновик' },
  { value: 'deleted', label: 'Удалён' },
] as const

export function documentTypeLabel(type: string): string {
  return DOCUMENT_TYPE_LABELS[type] || type
}

export function statusLabel(status: string): string {
  const map: Record<string, string> = {
    posted: 'Проведён',
    draft: 'Черновик',
    deleted: 'Удалён',
    unknown: '—',
  }
  return map[status] || status
}
