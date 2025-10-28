import apiClient from './api'

export interface SubAccount {
  id: number
  name: string
  status: 'active' | 'inactive'
  credits_remaining: number
  credits_used_this_month: number
  delivery_rate: number
  created_at?: string
  updated_at?: string
}

export const subAccountService = {
  async getAll(): Promise<SubAccount[]> {
    const response = await apiClient.get('/sub-accounts')
    return response.data.data || response.data
  },

  async getById(id: number): Promise<SubAccount> {
    const response = await apiClient.get(`/sub-accounts/${id}`)
    return response.data.data || response.data
  },

  async create(account: Partial<SubAccount>): Promise<SubAccount> {
    const response = await apiClient.post('/sub-accounts', account)
    return response.data.data || response.data
  },

  async update(id: number, account: Partial<SubAccount>): Promise<SubAccount> {
    const response = await apiClient.put(`/sub-accounts/${id}`, account)
    return response.data.data || response.data
  },

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/sub-accounts/${id}`)
  },

  async recharge(id: number, amount: number): Promise<SubAccount> {
    const response = await apiClient.post(`/sub-accounts/${id}/recharge`, { amount })
    return response.data.data || response.data
  }
}
