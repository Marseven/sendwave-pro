import axios from 'axios';

// Utiliser l'URL relative en production, localhost en dev
const API_BASE_URL = import.meta.env.VITE_API_URL ||
  (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost:8888/sendwave-pro/public/api'
    : '/api');

// Instance Axios configurée
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Intercepteur pour ajouter le token à chaque requête
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les erreurs
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token invalide ou expiré
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Types
export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  role?: string;
}

export interface AuthResponse {
  access_token: string;
  token_type: string;
  user: any;
}

// Auth API
export const authApi = {
  login: async (credentials: LoginCredentials): Promise<AuthResponse> => {
    const { data } = await api.post('/auth/login', credentials);
    return data;
  },

  register: async (userData: RegisterData): Promise<AuthResponse> => {
    const { data } = await api.post('/auth/register', userData);
    return data;
  },

  logout: async (): Promise<void> => {
    await api.post('/auth/logout');
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
  },

  me: async () => {
    const { data } = await api.get('/auth/me');
    return data;
  },
};

// Contacts API
export const contactsApi = {
  getAll: async () => {
    const { data } = await api.get('/contacts');
    return data;
  },

  getOne: async (id: string) => {
    const { data } = await api.get(`/contacts/${id}`);
    return data;
  },

  create: async (contact: any) => {
    const { data } = await api.post('/contacts', contact);
    return data;
  },

  update: async (id: string, contact: any) => {
    const { data } = await api.put(`/contacts/${id}`, contact);
    return data;
  },

  delete: async (id: string) => {
    const { data } = await api.delete(`/contacts/${id}`);
    return data;
  },
};

// Campaigns API
export const campaignsApi = {
  getAll: async () => {
    const { data } = await api.get('/campaigns');
    return data;
  },

  getOne: async (id: string) => {
    const { data } = await api.get(`/campaigns/${id}`);
    return data;
  },

  create: async (campaign: any) => {
    const { data } = await api.post('/campaigns', campaign);
    return data;
  },

  update: async (id: string, campaign: any) => {
    const { data } = await api.put(`/campaigns/${id}`, campaign);
    return data;
  },

  delete: async (id: string) => {
    const { data } = await api.delete(`/campaigns/${id}`);
    return data;
  },
};

// Templates API
export const templatesApi = {
  getAll: async () => {
    const { data } = await api.get('/templates');
    return data;
  },

  getOne: async (id: string) => {
    const { data } = await api.get(`/templates/${id}`);
    return data;
  },

  create: async (template: any) => {
    const { data } = await api.post('/templates', template);
    return data;
  },

  update: async (id: string, template: any) => {
    const { data } = await api.put(`/templates/${id}`, template);
    return data;
  },

  delete: async (id: string) => {
    const { data } = await api.delete(`/templates/${id}`);
    return data;
  },
};

// Sub Accounts API
export const subAccountsApi = {
  getAll: async () => {
    const { data } = await api.get('/sub-accounts');
    return data;
  },

  create: async (subAccount: any) => {
    const { data} = await api.post('/sub-accounts', subAccount);
    return data;
  },

  update: async (id: string, subAccount: any) => {
    const { data } = await api.put(`/sub-accounts/${id}`, subAccount);
    return data;
  },

  delete: async (id: string) => {
    const { data } = await api.delete(`/sub-accounts/${id}`);
    return data;
  },
};

// API Keys API
export const apiKeysApi = {
  getAll: async () => {
    const { data } = await api.get('/api-keys');
    return data;
  },

  create: async (apiKey: any) => {
    const { data } = await api.post('/api-keys', apiKey);
    return data;
  },

  delete: async (id: string) => {
    const { data } = await api.delete(`/api-keys/${id}`);
    return data;
  },
};

export default api;
