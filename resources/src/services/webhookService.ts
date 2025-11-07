import apiClient from './api';

export interface Webhook {
  id: number;
  name: string;
  url: string;
  events: string[];
  is_active: boolean;
  secret?: string;
  success_count: number;
  failure_count: number;
  last_triggered_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface WebhookFormData {
  name: string;
  url: string;
  events: string[];
  is_active?: boolean;
}

export interface WebhookLog {
  id: number;
  webhook_id: number;
  event: string;
  payload: any;
  response_code: number;
  response_body: string;
  success: boolean;
  created_at: string;
}

export interface WebhookStats {
  total_calls: number;
  success_count: number;
  failure_count: number;
  success_rate: number;
  last_24h_calls: number;
}

export const webhookService = {
  /**
   * Get all webhooks
   */
  async getAll(): Promise<Webhook[]> {
    const response = await apiClient.get('/webhooks');
    return response.data.data || response.data;
  },

  /**
   * Get single webhook
   */
  async getById(id: number): Promise<Webhook> {
    const response = await apiClient.get(`/webhooks/${id}`);
    return response.data.data || response.data;
  },

  /**
   * Create new webhook
   */
  async create(data: WebhookFormData): Promise<Webhook> {
    const response = await apiClient.post('/webhooks', data);
    return response.data.data || response.data;
  },

  /**
   * Update webhook
   */
  async update(id: number, data: Partial<WebhookFormData>): Promise<Webhook> {
    const response = await apiClient.put(`/webhooks/${id}`, data);
    return response.data.data || response.data;
  },

  /**
   * Delete webhook
   */
  async delete(id: number): Promise<void> {
    await apiClient.delete(`/webhooks/${id}`);
  },

  /**
   * Toggle webhook active status
   */
  async toggle(id: number): Promise<Webhook> {
    const response = await apiClient.post(`/webhooks/${id}/toggle`);
    return response.data.data || response.data;
  },

  /**
   * Test webhook
   */
  async test(id: number): Promise<{ success: boolean; response_code: number; response_body: string }> {
    const response = await apiClient.post(`/webhooks/${id}/test`);
    return response.data;
  },

  /**
   * Get webhook logs
   */
  async getLogs(id: number, params?: { limit?: number; success?: boolean }): Promise<WebhookLog[]> {
    const response = await apiClient.get(`/webhooks/${id}/logs`, { params });
    return response.data.data || response.data;
  },

  /**
   * Get webhook stats
   */
  async getStats(id: number): Promise<WebhookStats> {
    const response = await apiClient.get(`/webhooks/${id}/stats`);
    return response.data.data || response.data;
  },

  /**
   * Get available webhook events
   */
  async getEvents(): Promise<string[]> {
    const response = await apiClient.get('/webhooks/events');
    return response.data.data || response.data;
  }
};

export default webhookService;
