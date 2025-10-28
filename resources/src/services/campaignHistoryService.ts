import apiClient from './api'

export interface Campaign {
  id: number
  name: string
  message: string
  status: 'completed' | 'scheduled' | 'cancelled' | 'failed'
  sent_at?: string
  scheduled_at?: string
  recipients_count: number
  sms_count: number
  delivery_rate: number
  cost: number
  groups?: { id: number; name: string }[]
}

export interface CampaignFilters {
  search?: string
  status?: string
  dateFrom?: string
  dateTo?: string
  page?: number
  perPage?: number
}

export const campaignHistoryService = {
  /**
   * Récupérer l'historique des campagnes
   */
  async getAll(filters?: CampaignFilters): Promise<Campaign[]> {
    const response = await apiClient.get('/campaigns/history', { params: filters })
    return response.data.data || response.data
  },

  /**
   * Récupérer une campagne par ID
   */
  async getById(id: number): Promise<Campaign> {
    const response = await apiClient.get(`/campaigns/${id}`)
    return response.data.data || response.data
  },

  /**
   * Obtenir les statistiques des campagnes
   */
  async getStats(): Promise<{
    total: number
    completed: number
    scheduled: number
    failed: number
    totalCost: number
    totalRecipients: number
  }> {
    const response = await apiClient.get('/campaigns/stats')
    return response.data.data || response.data
  }
}
