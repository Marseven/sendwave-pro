import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import {
  authApi,
  contactsApi,
  campaignsApi,
  templatesApi,
  subAccountsApi,
  apiKeysApi
} from './api';

// Types
export interface User {
  id: string;
  name: string;
  email: string;
  role: string;
  avatar: string;
}

export interface Contact {
  id: string;
  name: string;
  phone: string;
  group: string;
  status: 'Actif' | 'En Attente' | 'Inactif';
  last_connection: string;
}

export interface Campaign {
  id: string;
  name: string;
  status: 'Actif' | 'Terminé' | 'Planifié';
  messages_sent: number;
  delivery_rate: number;
  ctr: number;
  created_at: string;
}

export interface MessageTemplate {
  id: string;
  name: string;
  content: string;
  category: string;
  updated_at: string;
}

export interface SubAccount {
  id: string;
  name: string;
  email: string;
  status: 'Actif' | 'Inactif';
  last_connection: string;
}

export interface ApiKey {
  id: string;
  name: string;
  key: string;
  created_at: string;
  last_used: string;
}

interface AppState {
  // Auth
  user: User | null;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => Promise<void>;
  loadUser: () => Promise<void>;

  // Contacts
  contacts: Contact[];
  loadContacts: () => Promise<void>;
  addContact: (contact: Omit<Contact, 'id'>) => Promise<void>;
  updateContact: (id: string, contact: Partial<Contact>) => Promise<void>;
  deleteContact: (id: string) => Promise<void>;

  // Campaigns
  campaigns: Campaign[];
  loadCampaigns: () => Promise<void>;
  addCampaign: (campaign: Omit<Campaign, 'id'>) => Promise<void>;
  updateCampaign: (id: string, campaign: Partial<Campaign>) => Promise<void>;
  deleteCampaign: (id: string) => Promise<void>;

  // Templates
  templates: MessageTemplate[];
  loadTemplates: () => Promise<void>;
  addTemplate: (template: Omit<MessageTemplate, 'id' | 'updated_at'>) => Promise<void>;
  updateTemplate: (id: string, template: Partial<MessageTemplate>) => Promise<void>;
  deleteTemplate: (id: string) => Promise<void>;

  // Sub Accounts
  subAccounts: SubAccount[];
  loadSubAccounts: () => Promise<void>;
  addSubAccount: (subAccount: Omit<SubAccount, 'id'>) => Promise<void>;
  updateSubAccount: (id: string, subAccount: Partial<SubAccount>) => Promise<void>;
  deleteSubAccount: (id: string) => Promise<void>;

  // API Keys
  apiKeys: ApiKey[];
  loadApiKeys: () => Promise<void>;
  addApiKey: (apiKey: Omit<ApiKey, 'id' | 'created_at' | 'last_used'>) => Promise<void>;
  revokeApiKey: (id: string) => Promise<void>;
}

export const useAppStore = create<AppState>()(
  persist(
    (set, get) => ({
      // Auth
      user: null,
      isAuthenticated: !!localStorage.getItem('auth_token'),

      login: async (email: string, password: string) => {
        try {
          const response = await authApi.login({ email, password });
          localStorage.setItem('auth_token', response.access_token);
          set({
            user: response.user,
            isAuthenticated: true
          });
          return true;
        } catch (error) {
          console.error('Login error:', error);
          return false;
        }
      },

      logout: async () => {
        try {
          await authApi.logout();
        } catch (error) {
          console.error('Logout error:', error);
        } finally {
          set({
            user: null,
            isAuthenticated: false,
            contacts: [],
            campaigns: [],
            templates: [],
            subAccounts: [],
            apiKeys: []
          });
        }
      },

      loadUser: async () => {
        try {
          const user = await authApi.me();
          set({ user, isAuthenticated: true });
        } catch (error) {
          console.error('Load user error:', error);
          set({ user: null, isAuthenticated: false });
        }
      },

      // Contacts
      contacts: [],

      loadContacts: async () => {
        try {
          const contacts = await contactsApi.getAll();
          set({ contacts });
        } catch (error) {
          console.error('Load contacts error:', error);
        }
      },

      addContact: async (contact) => {
        try {
          const newContact = await contactsApi.create(contact);
          set(state => ({ contacts: [...state.contacts, newContact] }));
        } catch (error) {
          console.error('Add contact error:', error);
          throw error;
        }
      },

      updateContact: async (id, updatedContact) => {
        try {
          const contact = await contactsApi.update(id, updatedContact);
          set(state => ({
            contacts: state.contacts.map(c =>
              c.id === id ? contact : c
            )
          }));
        } catch (error) {
          console.error('Update contact error:', error);
          throw error;
        }
      },

      deleteContact: async (id) => {
        try {
          await contactsApi.delete(id);
          set(state => ({
            contacts: state.contacts.filter(c => c.id !== id)
          }));
        } catch (error) {
          console.error('Delete contact error:', error);
          throw error;
        }
      },

      // Campaigns
      campaigns: [],

      loadCampaigns: async () => {
        try {
          const campaigns = await campaignsApi.getAll();
          set({ campaigns });
        } catch (error) {
          console.error('Load campaigns error:', error);
        }
      },

      addCampaign: async (campaign) => {
        try {
          const newCampaign = await campaignsApi.create(campaign);
          set(state => ({ campaigns: [...state.campaigns, newCampaign] }));
        } catch (error) {
          console.error('Add campaign error:', error);
          throw error;
        }
      },

      updateCampaign: async (id, updatedCampaign) => {
        try {
          const campaign = await campaignsApi.update(id, updatedCampaign);
          set(state => ({
            campaigns: state.campaigns.map(c =>
              c.id === id ? campaign : c
            )
          }));
        } catch (error) {
          console.error('Update campaign error:', error);
          throw error;
        }
      },

      deleteCampaign: async (id) => {
        try {
          await campaignsApi.delete(id);
          set(state => ({
            campaigns: state.campaigns.filter(c => c.id !== id)
          }));
        } catch (error) {
          console.error('Delete campaign error:', error);
          throw error;
        }
      },

      // Templates
      templates: [],

      loadTemplates: async () => {
        try {
          const templates = await templatesApi.getAll();
          set({ templates });
        } catch (error) {
          console.error('Load templates error:', error);
        }
      },

      addTemplate: async (template) => {
        try {
          const newTemplate = await templatesApi.create(template);
          set(state => ({ templates: [...state.templates, newTemplate] }));
        } catch (error) {
          console.error('Add template error:', error);
          throw error;
        }
      },

      updateTemplate: async (id, updatedTemplate) => {
        try {
          const template = await templatesApi.update(id, updatedTemplate);
          set(state => ({
            templates: state.templates.map(t =>
              t.id === id ? template : t
            )
          }));
        } catch (error) {
          console.error('Update template error:', error);
          throw error;
        }
      },

      deleteTemplate: async (id) => {
        try {
          await templatesApi.delete(id);
          set(state => ({
            templates: state.templates.filter(t => t.id !== id)
          }));
        } catch (error) {
          console.error('Delete template error:', error);
          throw error;
        }
      },

      // Sub Accounts
      subAccounts: [],

      loadSubAccounts: async () => {
        try {
          const subAccounts = await subAccountsApi.getAll();
          set({ subAccounts });
        } catch (error) {
          console.error('Load sub accounts error:', error);
        }
      },

      addSubAccount: async (subAccount) => {
        try {
          const newSubAccount = await subAccountsApi.create(subAccount);
          set(state => ({ subAccounts: [...state.subAccounts, newSubAccount] }));
        } catch (error) {
          console.error('Add sub account error:', error);
          throw error;
        }
      },

      updateSubAccount: async (id, updatedSubAccount) => {
        try {
          const subAccount = await subAccountsApi.update(id, updatedSubAccount);
          set(state => ({
            subAccounts: state.subAccounts.map(s =>
              s.id === id ? subAccount : s
            )
          }));
        } catch (error) {
          console.error('Update sub account error:', error);
          throw error;
        }
      },

      deleteSubAccount: async (id) => {
        try {
          await subAccountsApi.delete(id);
          set(state => ({
            subAccounts: state.subAccounts.filter(s => s.id !== id)
          }));
        } catch (error) {
          console.error('Delete sub account error:', error);
          throw error;
        }
      },

      // API Keys
      apiKeys: [],

      loadApiKeys: async () => {
        try {
          const apiKeys = await apiKeysApi.getAll();
          set({ apiKeys });
        } catch (error) {
          console.error('Load API keys error:', error);
        }
      },

      addApiKey: async (apiKey) => {
        try {
          const newApiKey = await apiKeysApi.create(apiKey);
          set(state => ({ apiKeys: [...state.apiKeys, newApiKey] }));
        } catch (error) {
          console.error('Add API key error:', error);
          throw error;
        }
      },

      revokeApiKey: async (id) => {
        try {
          await apiKeysApi.delete(id);
          set(state => ({
            apiKeys: state.apiKeys.filter(k => k.id !== id)
          }));
        } catch (error) {
          console.error('Revoke API key error:', error);
          throw error;
        }
      },
    }),
    {
      name: 'sms-app-storage',
      partialize: (state) => ({
        user: state.user,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);
