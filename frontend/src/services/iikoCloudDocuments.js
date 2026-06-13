import { api } from '@/api/client'
import { getIikoCloudPayload } from '@/utils/iikoSettings'

/**
 * @param {import('@/types/iikoDocument').IikoDocumentsListParams} params
 */
export async function fetchDocumentsList(params) {
  const cloud = getIikoCloudPayload()
  if (!cloud) {
    throw new Error('Настройте iikoCloud в разделе «Настройки»')
  }
  const body = {
    iikoCloud: cloud,
    organizationId: cloud.organizationId,
    fromDate: params.fromDate,
    toDate: params.toDate,
    page: params.page ?? 0,
    pageSize: params.pageSize ?? 50,
  }
  if (params.storeId) body.storeId = params.storeId
  if (params.documentTypes?.length) {
    body.documentTypes = params.documentTypes.filter(Boolean)
  }
  if (params.statuses?.length) {
    body.statuses = params.statuses.filter(Boolean)
  }
  return api.iikoStock.documentsList(body)
}

/**
 * @param {string} documentId
 * @param {string} documentType
 */
export async function fetchDocumentById(documentId, documentType) {
  const cloud = getIikoCloudPayload()
  if (!cloud) {
    throw new Error('Настройте iikoCloud в разделе «Настройки»')
  }
  return api.iikoStock.documentById({
    iikoCloud: cloud,
    organizationId: cloud.organizationId,
    documentId,
    documentType,
  })
}

export async function fetchCloudStores() {
  const cloud = getIikoCloudPayload()
  if (!cloud) {
    throw new Error('Настройте iikoCloud в разделе «Настройки»')
  }
  return api.iikoStock.cloudStores({
    iikoCloud: cloud,
    organizationId: cloud.organizationId,
  })
}
