import apiClient from './api'

export interface Message {
  id: number
  recipient_name?: string
  recipient_phone: string
  content: string
  type: 'immediate' | 'campaign'
  status: 'delivered' | 'pending' | 'failed'
  sent_at: string
  cost: number
  campaign_name?: string
  provider?: string
  error_message?: string
}

export interface MessageFilters {
  search?: string
  status?: string
  type?: string
  dateFrom?: string
  dateTo?: string
  page?: number
  perPage?: number
}

export interface MessageStats {
  total: number
  delivered: number
  pending: number
  failed: number
  totalCost: number
}

export const messageHistoryService = {
  /**
   * Récupérer l'historique des messages
   */
  async getAll(filters?: MessageFilters): Promise<Message[]> {
    const response = await apiClient.get('/messages/history', { params: filters })
    return response.data.data || response.data
  },

  /**
   * Récupérer un message par ID
   */
  async getById(id: number): Promise<Message> {
    const response = await apiClient.get(`/messages/${id}`)
    return response.data.data || response.data
  },

  /**
   * Obtenir les statistiques des messages
   */
  async getStats(): Promise<MessageStats> {
    const response = await apiClient.get('/messages/stats')
    return response.data.data || response.data
  },

  /**
   * Exporter l'historique des messages en CSV
   */
  async exportToCsv(filters?: MessageFilters): Promise<Blob> {
    const response = await apiClient.get('/messages/export', {
      params: filters,
      responseType: 'blob'
    })
    return response.data
  }
}
