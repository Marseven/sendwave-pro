import apiClient from './api'

export interface SubAccount {
  id: number
  account_id: number | null
  name: string
  email: string
  role: string
  status: 'active' | 'suspended' | 'inactive'
  is_default: boolean
  sms_credits: number
  budget_used: number
  monthly_budget: number | null
  sms_credit_limit: number | null
  sms_used: number
  remaining_credits: number | null
  permissions?: string[]
  last_connection?: string | null
  created_at?: string
  updated_at?: string
}

export interface TransferCreditsPayload {
  from_sub_account_id: number
  to_sub_account_id: number
  amount: number
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

  async create(account: Partial<SubAccount> & { password?: string }): Promise<SubAccount> {
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

  async addCredits(id: number, amount: number): Promise<any> {
    const response = await apiClient.post(`/sub-accounts/${id}/credits`, { amount })
    return response.data.data || response.data
  },

  async transferCredits(payload: TransferCreditsPayload): Promise<any> {
    const response = await apiClient.post('/sub-accounts/transfer-credits', payload)
    return response.data.data || response.data
  },

  async suspend(id: number): Promise<void> {
    await apiClient.post(`/sub-accounts/${id}/suspend`)
  },

  async activate(id: number): Promise<void> {
    await apiClient.post(`/sub-accounts/${id}/activate`)
  },
}
