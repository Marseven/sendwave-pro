import apiClient from './api'

export interface ApiKey {
  id: number
  name: string
  key: string
  type: 'production' | 'test'
  status: 'active' | 'revoked'
  last_used_at?: string
  created_at?: string
  updated_at?: string
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

  async create(apiKey: Partial<ApiKey>): Promise<ApiKey> {
    const response = await apiClient.post('/api-keys', apiKey)
    return response.data.data || response.data
  },

  async revoke(id: number): Promise<void> {
    await apiClient.post(`/api-keys/${id}/revoke`)
  },

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/api-keys/${id}`)
  }
}
