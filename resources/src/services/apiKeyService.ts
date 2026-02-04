import apiClient from './api'

export interface ApiKey {
  id: number
  name: string
  key: string
  full_key?: string | null
  type: 'production' | 'test'
  status: 'active' | 'revoked'
  sub_account_id: number | null
  sub_account_name?: string | null
  permissions: string[]
  rate_limit: number
  allowed_ips?: string[] | null
  last_used_at?: string | null
  created_at: string
}

export interface CreateApiKeyRequest {
  name: string
  sub_account_id: number
  type?: 'production' | 'test'
  permissions?: string[]
  rate_limit?: number
  allowed_ips?: string[]
}

export const apiKeyService = {
  async getAll(): Promise<ApiKey[]> {
    const response = await apiClient.get('/api-keys')
    return response.data.data || response.data
  },

  async getById(id: number): Promise<ApiKey> {
    const response = await apiClient.get(`/api-keys/${id}`)
    return response.data.data || response.data
  },

  async create(apiKey: CreateApiKeyRequest): Promise<ApiKey> {
    const response = await apiClient.post('/api-keys', apiKey)
    return response.data.data || response.data
  },

  async update(id: number, data: Partial<CreateApiKeyRequest>): Promise<ApiKey> {
    const response = await apiClient.put(`/api-keys/${id}`, data)
    return response.data.data || response.data
  },

  async revoke(id: number): Promise<ApiKey> {
    const response = await apiClient.post(`/api-keys/${id}/revoke`)
    return response.data.data || response.data
  },

  async regenerate(id: number): Promise<ApiKey> {
    const response = await apiClient.post(`/api-keys/${id}/regenerate`)
    return response.data.data || response.data
  },

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/api-keys/${id}`)
  }
}
