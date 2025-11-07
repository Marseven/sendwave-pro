import api from './api';

export interface AnalyticsDashboard {
  overview: {
    sms_sent: number;
    sms_delivered: number;
    sms_failed: number;
    success_rate: number;
    total_cost: number;
    average_cost_per_sms: number;
    campaigns_executed: number;
    contacts_added: number;
  };
  trends: {
    sms_sent_change: number;
    success_rate_change: number;
    cost_change: number;
    campaigns_change: number;
  };
  providers: {
    airtel: {
      count: number;
      percentage: number;
    };
    moov: {
      count: number;
      percentage: number;
    };
  };
  campaigns: Array<{
    id: number;
    name: string;
    status: string;
    messages_sent: number;
    created_at: string;
  }>;
  cost_analysis: {
    total_cost: number;
    airtel_cost: number;
    moov_cost: number;
    average_daily_cost: number;
    highest_daily_cost: number;
    lowest_daily_cost: number;
  };
  hourly_distribution: Array<{
    hour: string;
    count: number;
  }>;
}

export interface ChartData {
  labels: string[];
  datasets: Array<{
    label: string;
    data: number[];
    backgroundColor: string;
    borderColor: string;
  }>;
}

export interface ComprehensiveReport {
  summary: AnalyticsDashboard['overview'];
  trends: AnalyticsDashboard['trends'];
  provider_breakdown: AnalyticsDashboard['providers'];
  top_campaigns: AnalyticsDashboard['campaigns'];
  cost_analysis: AnalyticsDashboard['cost_analysis'];
  daily_breakdown: Array<{
    date: string;
    sms_sent: number;
    sms_delivered: number;
    sms_failed: number;
    success_rate: number;
    airtel_count: number;
    moov_count: number;
    total_cost: number;
    average_cost_per_sms: number;
    campaigns_sent: number;
  }>;
  hourly_distribution: AnalyticsDashboard['hourly_distribution'];
  period: {
    start: string;
    end: string;
    days: number;
  };
}

const analyticsService = {
  /**
   * Get dashboard widgets data
   */
  async getDashboard(period: string = 'today'): Promise<{ data: AnalyticsDashboard; period: string }> {
    const response = await api.get('/analytics/dashboard', {
      params: { period }
    });
    return response.data;
  },

  /**
   * Get chart data
   */
  async getChartData(period: string = 'week'): Promise<ChartData> {
    const response = await api.get('/analytics/chart', {
      params: { period }
    });
    return response.data;
  },

  /**
   * Get comprehensive report
   */
  async getReport(startDate: string, endDate: string): Promise<{ data: ComprehensiveReport }> {
    const response = await api.get('/analytics/report', {
      params: {
        start_date: startDate,
        end_date: endDate
      }
    });
    return response.data;
  },

  /**
   * Export to PDF
   */
  async exportPdf(startDate: string, endDate: string): Promise<Blob> {
    const response = await api.get('/analytics/export/pdf', {
      params: {
        start_date: startDate,
        end_date: endDate
      },
      responseType: 'blob'
    });
    return response.data;
  },

  /**
   * Export to Excel
   */
  async exportExcel(startDate: string, endDate: string): Promise<Blob> {
    const response = await api.get('/analytics/export/excel', {
      params: {
        start_date: startDate,
        end_date: endDate
      },
      responseType: 'blob'
    });
    return response.data;
  },

  /**
   * Export to CSV
   */
  async exportCsv(startDate: string, endDate: string): Promise<Blob> {
    const response = await api.get('/analytics/export/csv', {
      params: {
        start_date: startDate,
        end_date: endDate
      },
      responseType: 'blob'
    });
    return response.data;
  },

  /**
   * Get provider statistics
   */
  async getProviders(period: string = 'month'): Promise<{ data: AnalyticsDashboard['providers']; period: string }> {
    const response = await api.get('/analytics/providers', {
      params: { period }
    });
    return response.data;
  },

  /**
   * Get top campaigns
   */
  async getTopCampaigns(period: string = 'month', limit: number = 5): Promise<{ data: AnalyticsDashboard['campaigns']; period: string }> {
    const response = await api.get('/analytics/top-campaigns', {
      params: { period, limit }
    });
    return response.data;
  },

  /**
   * Update analytics manually
   */
  async updateAnalytics(date?: string): Promise<{ message: string; date: string }> {
    const response = await api.post('/analytics/update', { date });
    return response.data;
  },

  /**
   * Download file helper
   */
  downloadFile(blob: Blob, filename: string) {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }
};

export default analyticsService;
