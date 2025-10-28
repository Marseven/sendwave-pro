import { create } from 'zustand';
import { persist } from 'zustand/middleware';

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
  lastConnection: string;
}

export interface Campaign {
  id: string;
  name: string;
  status: 'Actif' | 'Terminé' | 'Planifié';
  messagesSent: number;
  deliveryRate: number;
  ctr: number;
  createdAt: string;
}

export interface MessageTemplate {
  id: string;
  name: string;
  content: string;
  category: string;
  lastModified: string;
}

export interface SubAccount {
  id: string;
  name: string;
  email: string;
  status: 'Actif' | 'Inactif';
  lastConnection: string;
}

export interface ApiKey {
  id: string;
  name: string;
  key: string;
  createdAt: string;
  lastUsed: string;
}

interface AppState {
  // Auth
  user: User | null;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => void;

  // Contacts
  contacts: Contact[];
  addContact: (contact: Omit<Contact, 'id'>) => void;
  updateContact: (id: string, contact: Partial<Contact>) => void;
  deleteContact: (id: string) => void;

  // Campaigns
  campaigns: Campaign[];
  addCampaign: (campaign: Omit<Campaign, 'id'>) => void;
  updateCampaign: (id: string, campaign: Partial<Campaign>) => void;
  deleteCampaign: (id: string) => void;

  // Templates
  templates: MessageTemplate[];
  addTemplate: (template: Omit<MessageTemplate, 'id'>) => void;
  updateTemplate: (id: string, template: Partial<MessageTemplate>) => void;
  deleteTemplate: (id: string) => void;

  // Sub Accounts
  subAccounts: SubAccount[];
  addSubAccount: (subAccount: Omit<SubAccount, 'id'>) => void;
  updateSubAccount: (id: string, subAccount: Partial<SubAccount>) => void;
  deleteSubAccount: (id: string) => void;

  // API Keys
  apiKeys: ApiKey[];
  addApiKey: (apiKey: Omit<ApiKey, 'id'>) => void;
  revokeApiKey: (id: string) => void;
}

const generateId = () => Math.random().toString(36).substring(2) + Date.now().toString(36);

// Mock data
const initialContacts: Contact[] = [
  {
    id: '1',
    name: 'Pierre Ndong',
    phone: '+241 66 12 34 56',
    group: 'Clients',
    status: 'Actif',
    lastConnection: '2023-11-01'
  },
  {
    id: '2',
    name: 'Marie Mboumba',
    phone: '+241 74 98 76 54',
    group: 'Prospects',
    status: 'En Attente',
    lastConnection: '2023-10-29'
  },
  {
    id: '3',
    name: 'Jean-Luc Nguema',
    phone: '+241 62 11 22 33',
    group: 'Clients',
    status: 'Actif',
    lastConnection: '2023-10-28'
  },
  {
    id: '4',
    name: 'Fatima Bongo',
    phone: '+241 77 33 44 55',
    group: 'Partenaires',
    status: 'Actif',
    lastConnection: '2023-10-25'
  },
  {
    id: '5',
    name: 'Serge Moukagni',
    phone: '+241 65 55 66 77',
    group: 'Clients',
    status: 'Inactif',
    lastConnection: '2023-09-15'
  }
];

const initialCampaigns: Campaign[] = [
  {
    id: '1',
    name: 'Vente de Vacances 2023',
    status: 'Terminé',
    messagesSent: 500000,
    deliveryRate: 99.1,
    ctr: 4.5,
    createdAt: '2023-07-20'
  },
  {
    id: '2',
    name: 'Lancement Nouveau Produit',
    status: 'Actif',
    messagesSent: 120000,
    deliveryRate: 97.8,
    ctr: 3.2,
    createdAt: '2023-10-15'
  },
  {
    id: '3',
    name: 'Programme de Fidélité Client',
    status: 'Planifié',
    messagesSent: 0,
    deliveryRate: 0,
    ctr: 0,
    createdAt: '2023-11-01'
  },
  {
    id: '4',
    name: 'Vente Flash Week-end',
    status: 'Terminé',
    messagesSent: 300000,
    deliveryRate: 98.5,
    ctr: 5.1,
    createdAt: '2023-09-28'
  }
];

const initialTemplates: MessageTemplate[] = [
  {
    id: '1',
    name: 'Offre de Bienvenue',
    content: 'Bienvenue chez [Nom de la Banque] ! Ouvrez un compte et recevez un bonus de 30 000 F CFA.',
    category: 'Marketing',
    lastModified: '2024-01-20'
  },
  {
    id: '2',
    name: 'Prêt Personnel Exclusif',
    content: 'Besoin de fonds ? Profitez de notre prêt personnel à taux réduit. Demandez en ligne !',
    category: 'Promo',
    lastModified: '2024-01-15'
  },
  {
    id: '3',
    name: 'Nouvelle Carte Bancaire',
    content: 'Votre nouvelle carte bancaire est en route. Activez-la dès réception pour profiter de tous les avantages.',
    category: 'Notification',
    lastModified: '2024-01-10'
  },
  {
    id: '4',
    name: 'Conseils d\'Épargne',
    content: 'Optimisez votre épargne avec nos conseils personnalisés. Rendez-vous sur votre espace client pour en savoir plus.',
    category: 'Information',
    lastModified: '2023-12-25'
  },
  {
    id: '5',
    name: 'Assurance Habitation',
    content: 'Protégez votre foyer avec notre assurance habitation complète. Obtenez un devis gratuit en ligne !',
    category: 'Assurance',
    lastModified: '2023-12-10'
  }
];

const initialSubAccounts: SubAccount[] = [
  {
    id: '1',
    name: 'Équipe Marketing',
    email: 'marketing@example.com',
    status: 'Actif',
    lastConnection: '2023-11-01'
  },
  {
    id: '2',
    name: 'Département des Ventes',
    email: 'sales@example.com',
    status: 'Actif',
    lastConnection: '2023-10-29'
  },
  {
    id: '3',
    name: 'Équipe de Support',
    email: 'support@example.com',
    status: 'Inactif',
    lastConnection: '2023-09-15'
  }
];

const initialApiKeys: ApiKey[] = [
  {
    id: '1',
    name: 'Clé API Principale',
    key: '******************abcde',
    createdAt: '2023-01-15',
    lastUsed: '2024-07-20'
  },
  {
    id: '2',
    name: 'Accès aux Rapports',
    key: '******************fghij',
    createdAt: '2023-03-01',
    lastUsed: '2024-07-18'
  },
  {
    id: '3',
    name: 'Intégration Webhook',
    key: '******************klmno',
    createdAt: '2024-05-10',
    lastUsed: 'Jamais'
  }
];

export const useAppStore = create<AppState>()(
  persist(
    (set, get) => ({
      // Auth
      user: null,
      isAuthenticated: false,
      login: async (email: string, password: string) => {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        if (email === 'admin@jobs-sms.com' && password === 'password123') {
          const user = {
            id: '1',
            name: 'Hervé Ndjibi',
            email: 'herve.ndjibi@jobs-sms.com',
            role: 'Admin',
            avatar: '/lovable-uploads/b0eed011-e1a3-4c14-b352-4d36872d2778.png'
          };
          set({ user, isAuthenticated: true });
          return true;
        }
        return false;
      },
      logout: () => set({ user: null, isAuthenticated: false }),

      // Contacts
      contacts: initialContacts,
      addContact: (contact) => {
        const newContact = { ...contact, id: generateId() };
        set(state => ({ contacts: [...state.contacts, newContact] }));
      },
      updateContact: (id, updatedContact) => {
        set(state => ({
          contacts: state.contacts.map(contact => 
            contact.id === id ? { ...contact, ...updatedContact } : contact
          )
        }));
      },
      deleteContact: (id) => {
        set(state => ({
          contacts: state.contacts.filter(contact => contact.id !== id)
        }));
      },

      // Campaigns
      campaigns: initialCampaigns,
      addCampaign: (campaign) => {
        const newCampaign = { ...campaign, id: generateId() };
        set(state => ({ campaigns: [...state.campaigns, newCampaign] }));
      },
      updateCampaign: (id, updatedCampaign) => {
        set(state => ({
          campaigns: state.campaigns.map(campaign => 
            campaign.id === id ? { ...campaign, ...updatedCampaign } : campaign
          )
        }));
      },
      deleteCampaign: (id) => {
        set(state => ({
          campaigns: state.campaigns.filter(campaign => campaign.id !== id)
        }));
      },

      // Templates
      templates: initialTemplates,
      addTemplate: (template) => {
        const newTemplate = { ...template, id: generateId() };
        set(state => ({ templates: [...state.templates, newTemplate] }));
      },
      updateTemplate: (id, updatedTemplate) => {
        set(state => ({
          templates: state.templates.map(template => 
            template.id === id ? { ...template, ...updatedTemplate } : template
          )
        }));
      },
      deleteTemplate: (id) => {
        set(state => ({
          templates: state.templates.filter(template => template.id !== id)
        }));
      },

      // Sub Accounts
      subAccounts: initialSubAccounts,
      addSubAccount: (subAccount) => {
        const newSubAccount = { ...subAccount, id: generateId() };
        set(state => ({ subAccounts: [...state.subAccounts, newSubAccount] }));
      },
      updateSubAccount: (id, updatedSubAccount) => {
        set(state => ({
          subAccounts: state.subAccounts.map(subAccount => 
            subAccount.id === id ? { ...subAccount, ...updatedSubAccount } : subAccount
          )
        }));
      },
      deleteSubAccount: (id) => {
        set(state => ({
          subAccounts: state.subAccounts.filter(subAccount => subAccount.id !== id)
        }));
      },

      // API Keys
      apiKeys: initialApiKeys,
      addApiKey: (apiKey) => {
        const newApiKey = { ...apiKey, id: generateId() };
        set(state => ({ apiKeys: [...state.apiKeys, newApiKey] }));
      },
      revokeApiKey: (id) => {
        set(state => ({
          apiKeys: state.apiKeys.filter(apiKey => apiKey.id !== id)
        }));
      },
    }),
    {
      name: 'sms-app-storage',
      partialize: (state) => ({
        user: state.user,
        isAuthenticated: state.isAuthenticated,
        contacts: state.contacts,
        campaigns: state.campaigns,
        templates: state.templates,
        subAccounts: state.subAccounts,
        apiKeys: state.apiKeys,
      }),
    }
  )
);