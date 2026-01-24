import apiClient from './api'

export interface Contact {
  id: number
  name: string
  email: string
  phone: string
  status: 'active' | 'inactive'
  custom_fields?: Record<string, any>
  created_at?: string
  updated_at?: string
}

export const contactService = {
  async getAll(): Promise<Contact[]> {
    const response = await apiClient.get('/contacts')
    return response.data.data || response.data
  },

  async getById(id: number): Promise<Contact> {
    const response = await apiClient.get(`/contacts/${id}`)
    return response.data.data || response.data
  },

  async create(contact: Partial<Contact>): Promise<Contact> {
    const response = await apiClient.post('/contacts', contact)
    return response.data.data || response.data
  },

  async update(id: number, contact: Partial<Contact>): Promise<Contact> {
    const response = await apiClient.put(`/contacts/${id}`, contact)
    return response.data.data || response.data
  },

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/contacts/${id}`)
  },

  async import(file: File): Promise<any> {
    const formData = new FormData()
    formData.append('file', file)
    const response = await apiClient.post('/contacts/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  },

  async exportCsv(group?: string, status?: string): Promise<Blob> {
    const params: Record<string, string> = { format: 'csv' }
    if (group) params.group = group
    if (status) params.status = status

    const response = await apiClient.get('/contacts/export', {
      params,
      responseType: 'blob'
    })
    return response.data
  },

  downloadFile(blob: Blob, filename: string) {
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  }
}
