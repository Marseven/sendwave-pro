import apiClient from './api'

export interface Campaign {
  id: number
  name: string
  description?: string
  message: string
  status: 'draft' | 'scheduled' | 'active' | 'completed' | 'paused'
  send_date?: string
  send_time?: string
  template_id?: number
  recipient_type: 'all' | 'group' | 'import'
  group_id?: number
  sent?: number
  delivered?: number
  created_at?: string
  updated_at?: string
}

export const campaignService = {
  async getAll(): Promise<Campaign[]> {
    const response = await apiClient.get('/campaigns')
    return response.data.data || response.data
  },

  async getById(id: number): Promise<Campaign> {
    const response = await apiClient.get(`/campaigns/${id}`)
    return response.data.data || response.data
  },

  async create(campaign: Partial<Campaign>): Promise<Campaign> {
    const response = await apiClient.post('/campaigns', campaign)
    return response.data.data || response.data
  },

  async update(id: number, campaign: Partial<Campaign>): Promise<Campaign> {
    const response = await apiClient.put(`/campaigns/${id}`, campaign)
    return response.data.data || response.data
  },

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/campaigns/${id}`)
  },

  async launch(id: number): Promise<Campaign> {
    const response = await apiClient.post(`/campaigns/${id}/launch`)
    return response.data.data || response.data
  },

  async pause(id: number): Promise<Campaign> {
    const response = await apiClient.post(`/campaigns/${id}/pause`)
    return response.data.data || response.data
  },

  async getStats(): Promise<any> {
    const response = await apiClient.get('/campaigns/stats')
    return response.data
  },

  async createSchedule(campaignId: number, scheduleData: {
    frequency: 'daily' | 'weekly' | 'monthly'
    time: string
    days_of_week?: number[] | null
    day_of_month?: number | null
    start_date: string
    end_date?: string | null
  }): Promise<any> {
    const response = await apiClient.post(`/campaigns/${campaignId}/schedule`, scheduleData)
    return response.data.data || response.data
  },

  async getSchedule(campaignId: number): Promise<any> {
    const response = await apiClient.get(`/campaigns/${campaignId}/schedule`)
    return response.data.data || response.data
  },

  async deleteSchedule(campaignId: number): Promise<void> {
    await apiClient.delete(`/campaigns/${campaignId}/schedule`)
  }
}
