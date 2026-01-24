import apiClient from './api'

export interface Template {
  id: number
  user_id?: number
  name: string
  category: string
  message: string
  icon?: string
  status: 'active' | 'inactive'
  is_public?: boolean
  uses?: number
  usage_count?: number
  length?: number
  created_at?: string
  updated_at?: string
}

// Helper to map frontend fields to backend fields
const mapToBackend = (template: Partial<Template>) => {
  const { message, ...rest } = template
  return {
    ...rest,
    content: message // Backend expects 'content' instead of 'message'
  }
}

// Helper to map backend fields to frontend fields
const mapToFrontend = (data: any): Template => {
  const { content, ...rest } = data
  return {
    ...rest,
    message: content // Frontend uses 'message' instead of 'content'
  }
}

export const templateService = {
  async getAll(): Promise<Template[]> {
    const response = await apiClient.get('/templates')
    const data = response.data.data || response.data
    return data.map(mapToFrontend)
  },

  async getById(id: number): Promise<Template> {
    const response = await apiClient.get(`/templates/${id}`)
    const data = response.data.data || response.data
    return mapToFrontend(data)
  },

  async create(template: Partial<Template>): Promise<Template> {
    const backendData = mapToBackend(template)
    const response = await apiClient.post('/templates', backendData)
    const data = response.data.data || response.data
    return mapToFrontend(data)
  },

  async update(id: number, template: Partial<Template>): Promise<Template> {
    const backendData = mapToBackend(template)
    const response = await apiClient.put(`/templates/${id}`, backendData)
    const data = response.data.data || response.data
    return mapToFrontend(data)
  },

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/templates/${id}`)
  },

  async togglePublic(id: number): Promise<Template> {
    const response = await apiClient.post(`/templates/${id}/toggle-public`)
    const data = response.data.data || response.data
    return mapToFrontend(data)
  }
}
