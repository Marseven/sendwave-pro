export interface ApiParameter {
  name: string
  type: string
  required?: boolean
  description: string
  example?: string
}

export interface ApiEndpointData {
  method: 'GET' | 'POST' | 'PUT' | 'DELETE'
  path: string
  summary: string
  description?: string
  permissions?: string[]
  auth?: boolean
  body?: Record<string, any>
  response?: Record<string, any>
  parameters?: ApiParameter[]
  curl?: string
}

export interface ApiCategory {
  id: string
  name: string
  description: string
  icon?: string
  endpoints: ApiEndpointData[]
}

export const apiCategories: ApiCategory[] = [
  // ─── Authentication & Utilisateurs ───────────────────────────
  {
    id: 'auth',
    name: 'Authentification',
    description: 'Inscription, connexion, gestion de profil et permissions',
    endpoints: [
      {
        method: 'POST',
        path: '/api/auth/register',
        summary: "Inscription d'un nouvel utilisateur",
        auth: false,
        body: {
          name: 'John Doe',
          email: 'john@example.com',
          password: 'password123',
          password_confirmation: 'password123',
          company: 'Ma Société'
        },
        response: {
          access_token: '1|abc...',
          token_type: 'Bearer',
          user: { id: 1, name: 'John Doe', email: 'john@example.com' }
        }
      },
      {
        method: 'POST',
        path: '/api/auth/login',
        summary: 'Connexion utilisateur',
        auth: false,
        body: {
          email: 'admin@sendwave.com',
          password: 'password123'
        },
        response: {
          access_token: '1|abcdefghijklmnopqrstuvwxyz',
          token_type: 'Bearer',
          user: { id: 1, name: 'Admin', email: 'admin@sendwave.com', role: 'admin' }
        }
      },
      {
        method: 'POST',
        path: '/api/auth/logout',
        summary: 'Déconnexion (révocation du token)',
        auth: true,
        response: { message: 'Déconnexion réussie' }
      },
      {
        method: 'GET',
        path: '/api/auth/me',
        summary: "Informations de l'utilisateur connecté",
        auth: true,
        response: {
          id: 1,
          name: 'Admin',
          email: 'admin@sendwave.com',
          role: 'admin',
          permissions: ['send_sms', 'view_history', 'manage_contacts']
        }
      },
      {
        method: 'GET',
        path: '/api/auth/permissions',
        summary: "Permissions de l'utilisateur courant",
        auth: true,
        response: {
          permissions: ['send_sms', 'view_history', 'manage_contacts', 'manage_groups']
        }
      },
      {
        method: 'GET',
        path: '/api/auth/available-permissions',
        summary: 'Liste de toutes les permissions disponibles',
        auth: true,
        response: {
          permissions: [
            { key: 'send_sms', label: 'Envoyer des SMS' },
            { key: 'view_history', label: "Voir l'historique" }
          ]
        }
      },
      {
        method: 'GET',
        path: '/api/user/profile',
        summary: 'Profil utilisateur détaillé',
        auth: true,
        response: {
          id: 1,
          name: 'Admin',
          email: 'admin@sendwave.com',
          company: 'SendWave',
          phone: '+24177000000'
        }
      },
      {
        method: 'PUT',
        path: '/api/user/profile',
        summary: 'Mettre à jour le profil',
        auth: true,
        body: {
          name: 'Nouveau Nom',
          company: 'Nouvelle Société',
          phone: '+24177000001'
        },
        response: {
          message: 'Profil mis à jour',
          user: { id: 1, name: 'Nouveau Nom' }
        }
      }
    ]
  },

  // ─── Messages ────────────────────────────────────────────────
  {
    id: 'messages',
    name: 'Messages',
    description: "Envoi de SMS, analyse de numéros et historique des messages",
    endpoints: [
      {
        method: 'POST',
        path: '/api/messages/send',
        summary: 'Envoyer un SMS',
        description: "Envoie un ou plusieurs SMS aux destinataires spécifiés. Les numéros sont normalisés automatiquement et les numéros en liste noire sont filtrés.",
        auth: true,
        permissions: ['send_sms'],
        body: {
          recipients: ['+24177123456', '+24162987654'],
          contact_ids: [1, 2, 3],
          group_ids: [1],
          message: 'Votre message ici',
          _comment: 'Au moins un des champs recipients, contact_ids ou group_ids est requis'
        },
        response: {
          message: 'Messages envoyés avec succès',
          data: {
            total_sent: 2,
            total_cost: 40,
            results: [
              { phone: '24177123456', status: 'sent', provider: 'airtel', cost: 20 },
              { phone: '24162987654', status: 'sent', provider: 'moov', cost: 20 }
            ]
          }
        },
        curl: "curl -X POST {baseUrl}/api/messages/send \\\n  -H 'Authorization: Bearer YOUR_TOKEN' \\\n  -H 'Content-Type: application/json' \\\n  -d '{\"recipients\":[\"+24177123456\"],\"message\":\"Hello!\"}'"
      },
      {
        method: 'POST',
        path: '/api/messages/analyze',
        summary: 'Analyser un message SMS',
        description: "Analyse le contenu d'un message : nombre de segments, encodage, caractères spéciaux.",
        auth: true,
        permissions: ['send_sms'],
        body: {
          message: 'Votre message à analyser'
        },
        response: {
          segments: 1,
          encoding: 'GSM-7',
          characters: 25,
          max_characters: 160,
          has_unicode: false
        }
      },
      {
        method: 'POST',
        path: '/api/messages/number-info',
        summary: "Informations sur un numéro de téléphone",
        description: "Retourne l'opérateur détecté, le pays et le statut de normalisation.",
        auth: true,
        permissions: ['send_sms'],
        body: {
          phone: '77123456'
        },
        response: {
          original: '77123456',
          normalized: '+24177123456',
          country: 'GA',
          operator: 'airtel',
          valid: true
        }
      },
      {
        method: 'GET',
        path: '/api/messages/history',
        summary: "Historique des messages envoyés",
        description: "Liste paginée des messages avec filtres par date, statut, opérateur.",
        auth: true,
        permissions: ['view_history'],
        parameters: [
          { name: 'page', type: 'integer', description: 'Numéro de page', example: '1' },
          { name: 'per_page', type: 'integer', description: 'Résultats par page (max 100)', example: '15' },
          { name: 'status', type: 'string', description: 'Filtrer par statut: sent, delivered, failed', example: 'sent' },
          { name: 'date_from', type: 'string', description: 'Date de début (Y-m-d)', example: '2026-01-01' },
          { name: 'date_to', type: 'string', description: 'Date de fin (Y-m-d)', example: '2026-01-31' }
        ],
        response: {
          data: [
            { id: 1, phone: '24177123456', message: 'Hello', status: 'delivered', provider: 'airtel', cost: 20, created_at: '2026-01-15T10:30:00Z' }
          ],
          meta: { current_page: 1, last_page: 5, total: 73 }
        }
      },
      {
        method: 'GET',
        path: '/api/messages/stats',
        summary: 'Statistiques des messages',
        auth: true,
        permissions: ['view_history'],
        response: {
          total_sent: 1500,
          total_delivered: 1420,
          total_failed: 80,
          delivery_rate: 94.67,
          total_cost: 30000
        }
      },
      {
        method: 'GET',
        path: '/api/messages/export',
        summary: "Exporter l'historique des messages",
        description: "Exporte les messages au format CSV ou Excel.",
        auth: true,
        permissions: ['export_data'],
        parameters: [
          { name: 'format', type: 'string', description: 'Format: csv ou xlsx', example: 'csv' },
          { name: 'date_from', type: 'string', description: 'Date de début', example: '2026-01-01' },
          { name: 'date_to', type: 'string', description: 'Date de fin', example: '2026-01-31' }
        ],
        response: { _comment: 'Téléchargement du fichier' }
      }
    ]
  },

  // ─── Contacts ────────────────────────────────────────────────
  {
    id: 'contacts',
    name: 'Contacts',
    description: 'Gestion des contacts : création, import, export, champs personnalisés',
    endpoints: [
      {
        method: 'GET',
        path: '/api/contacts',
        summary: 'Liste des contacts',
        auth: true,
        permissions: ['manage_contacts'],
        parameters: [
          { name: 'page', type: 'integer', description: 'Numéro de page', example: '1' },
          { name: 'per_page', type: 'integer', description: 'Résultats par page', example: '15' },
          { name: 'search', type: 'string', description: 'Recherche par nom, email ou téléphone', example: 'John' },
          { name: 'group_id', type: 'integer', description: 'Filtrer par groupe', example: '3' }
        ],
        response: {
          data: [
            { id: 1, first_name: 'John', last_name: 'Doe', phone: '+24177123456', email: 'john@example.com', custom_fields: {} }
          ],
          meta: { current_page: 1, last_page: 3, total: 42 }
        }
      },
      {
        method: 'POST',
        path: '/api/contacts',
        summary: 'Créer un contact',
        auth: true,
        permissions: ['manage_contacts'],
        body: {
          first_name: 'John',
          last_name: 'Doe',
          phone: '+24177123456',
          email: 'john@example.com',
          custom_fields: { ville: 'Libreville' }
        },
        response: {
          message: 'Contact créé',
          data: { id: 1, first_name: 'John', last_name: 'Doe', phone: '+24177123456' }
        }
      },
      {
        method: 'GET',
        path: '/api/contacts/{id}',
        summary: "Détails d'un contact",
        auth: true,
        permissions: ['manage_contacts'],
        response: {
          id: 1,
          first_name: 'John',
          last_name: 'Doe',
          phone: '+24177123456',
          email: 'john@example.com',
          custom_fields: { ville: 'Libreville' },
          groups: [{ id: 1, name: 'Clients VIP' }]
        }
      },
      {
        method: 'PUT',
        path: '/api/contacts/{id}',
        summary: 'Modifier un contact',
        auth: true,
        permissions: ['manage_contacts'],
        body: {
          first_name: 'Jane',
          last_name: 'Doe',
          phone: '+24177123456',
          email: 'jane@example.com'
        },
        response: {
          message: 'Contact mis à jour',
          data: { id: 1, first_name: 'Jane', last_name: 'Doe' }
        }
      },
      {
        method: 'DELETE',
        path: '/api/contacts/{id}',
        summary: 'Supprimer un contact',
        auth: true,
        permissions: ['manage_contacts'],
        response: { message: 'Contact supprimé' }
      },
      {
        method: 'POST',
        path: '/api/contacts/import',
        summary: 'Importer des contacts (CSV/Excel)',
        description: "Upload d'un fichier CSV ou Excel pour importer des contacts en masse.",
        auth: true,
        permissions: ['manage_contacts'],
        body: {
          _comment: 'multipart/form-data',
          file: '(fichier CSV ou XLSX)',
          group_id: 1,
          mapping: { 0: 'first_name', 1: 'last_name', 2: 'phone' }
        },
        response: {
          message: 'Import terminé',
          imported: 150,
          duplicates: 5,
          errors: 2
        }
      },
      {
        method: 'POST',
        path: '/api/contacts/preview-import',
        summary: "Prévisualiser un import de contacts",
        auth: true,
        permissions: ['manage_contacts'],
        body: {
          _comment: 'multipart/form-data',
          file: '(fichier CSV ou XLSX)'
        },
        response: {
          headers: ['Nom', 'Prénom', 'Téléphone'],
          preview: [['Doe', 'John', '77123456']],
          total_rows: 150
        }
      },
      {
        method: 'GET',
        path: '/api/contacts/export',
        summary: 'Exporter les contacts',
        auth: true,
        permissions: ['export_data'],
        parameters: [
          { name: 'format', type: 'string', description: 'Format: csv ou xlsx', example: 'csv' },
          { name: 'group_id', type: 'integer', description: 'Exporter un groupe spécifique' }
        ],
        response: { _comment: 'Téléchargement du fichier' }
      }
    ]
  },

  // ─── Groupes de Contacts ─────────────────────────────────────
  {
    id: 'contact-groups',
    name: 'Groupes de Contacts',
    description: 'Création et gestion de groupes, ajout/retrait de membres',
    endpoints: [
      {
        method: 'GET',
        path: '/api/contact-groups',
        summary: 'Liste des groupes',
        auth: true,
        permissions: ['manage_groups'],
        response: {
          data: [
            { id: 1, name: 'Clients VIP', contacts_count: 42, created_at: '2026-01-01T00:00:00Z' }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/contact-groups',
        summary: 'Créer un groupe',
        auth: true,
        permissions: ['manage_groups'],
        body: { name: 'Nouveau Groupe', description: 'Description du groupe' },
        response: { message: 'Groupe créé', data: { id: 2, name: 'Nouveau Groupe' } }
      },
      {
        method: 'GET',
        path: '/api/contact-groups/{id}',
        summary: "Détails d'un groupe",
        auth: true,
        permissions: ['manage_groups'],
        response: { id: 1, name: 'Clients VIP', description: 'Nos meilleurs clients', contacts_count: 42 }
      },
      {
        method: 'PUT',
        path: '/api/contact-groups/{id}',
        summary: 'Modifier un groupe',
        auth: true,
        permissions: ['manage_groups'],
        body: { name: 'Nom modifié', description: 'Nouvelle description' },
        response: { message: 'Groupe mis à jour', data: { id: 1, name: 'Nom modifié' } }
      },
      {
        method: 'DELETE',
        path: '/api/contact-groups/{id}',
        summary: 'Supprimer un groupe',
        auth: true,
        permissions: ['manage_groups'],
        response: { message: 'Groupe supprimé' }
      },
      {
        method: 'GET',
        path: '/api/contact-groups/{id}/contacts',
        summary: "Contacts d'un groupe",
        auth: true,
        permissions: ['manage_groups'],
        response: {
          data: [
            { id: 1, first_name: 'John', last_name: 'Doe', phone: '+24177123456' }
          ],
          meta: { total: 42 }
        }
      },
      {
        method: 'POST',
        path: '/api/contact-groups/{id}/contacts/add',
        summary: 'Ajouter des contacts au groupe',
        auth: true,
        permissions: ['manage_groups'],
        body: { contact_ids: [1, 2, 3] },
        response: { message: '3 contacts ajoutés au groupe', added: 3 }
      },
      {
        method: 'POST',
        path: '/api/contact-groups/{id}/contacts/remove',
        summary: 'Retirer des contacts du groupe',
        auth: true,
        permissions: ['manage_groups'],
        body: { contact_ids: [1, 2] },
        response: { message: '2 contacts retirés du groupe', removed: 2 }
      }
    ]
  },

  // ─── Campagnes ───────────────────────────────────────────────
  {
    id: 'campaigns',
    name: 'Campagnes',
    description: 'Création, envoi, planification et A/B testing de campagnes SMS',
    endpoints: [
      {
        method: 'GET',
        path: '/api/campaigns',
        summary: 'Liste des campagnes',
        auth: true,
        permissions: ['create_campaigns'],
        parameters: [
          { name: 'status', type: 'string', description: 'Filtrer par statut: draft, scheduled, sending, completed, failed', example: 'draft' }
        ],
        response: {
          data: [
            { id: 1, name: 'Promo Janvier', status: 'completed', recipients_count: 500, sent_count: 495, created_at: '2026-01-10T08:00:00Z' }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/campaigns',
        summary: 'Créer une campagne',
        auth: true,
        permissions: ['create_campaigns'],
        body: {
          name: 'Promo Février',
          message: 'Profitez de nos offres ! {prenom}',
          group_ids: [1, 2],
          sender_id: 'SENDWAVE'
        },
        response: { message: 'Campagne créée', data: { id: 2, name: 'Promo Février', status: 'draft' } }
      },
      {
        method: 'GET',
        path: '/api/campaigns/{id}',
        summary: "Détails d'une campagne",
        auth: true,
        permissions: ['create_campaigns'],
        response: {
          id: 1,
          name: 'Promo Janvier',
          message: 'Profitez de nos offres !',
          status: 'completed',
          recipients_count: 500,
          sent_count: 495,
          failed_count: 5,
          cost: 9900,
          groups: [{ id: 1, name: 'Clients VIP' }]
        }
      },
      {
        method: 'PUT',
        path: '/api/campaigns/{id}',
        summary: 'Modifier une campagne (brouillon uniquement)',
        auth: true,
        permissions: ['create_campaigns'],
        body: { name: 'Promo Modifiée', message: 'Nouveau message' },
        response: { message: 'Campagne mise à jour', data: { id: 1, name: 'Promo Modifiée' } }
      },
      {
        method: 'DELETE',
        path: '/api/campaigns/{id}',
        summary: 'Supprimer une campagne',
        auth: true,
        permissions: ['create_campaigns'],
        response: { message: 'Campagne supprimée' }
      },
      {
        method: 'POST',
        path: '/api/campaigns/{id}/send',
        summary: 'Lancer une campagne',
        description: "Démarre l'envoi immédiat d'une campagne en brouillon.",
        auth: true,
        permissions: ['create_campaigns'],
        response: { message: 'Campagne lancée', status: 'sending' }
      },
      {
        method: 'POST',
        path: '/api/campaigns/{id}/clone',
        summary: 'Cloner une campagne',
        auth: true,
        permissions: ['create_campaigns'],
        response: { message: 'Campagne clonée', data: { id: 3, name: 'Promo Janvier (copie)', status: 'draft' } }
      },
      {
        method: 'POST',
        path: '/api/campaigns/{id}/schedule',
        summary: 'Planifier une campagne',
        auth: true,
        permissions: ['create_campaigns'],
        body: {
          scheduled_at: '2026-02-14T08:00:00',
          timezone: 'Africa/Libreville'
        },
        response: { message: 'Campagne planifiée', scheduled_at: '2026-02-14T08:00:00' }
      },
      {
        method: 'GET',
        path: '/api/campaigns/{id}/schedule',
        summary: "Voir la planification d'une campagne",
        auth: true,
        permissions: ['create_campaigns'],
        response: { scheduled_at: '2026-02-14T08:00:00', timezone: 'Africa/Libreville', status: 'pending' }
      },
      {
        method: 'DELETE',
        path: '/api/campaigns/{id}/schedule',
        summary: 'Annuler la planification',
        auth: true,
        permissions: ['create_campaigns'],
        response: { message: 'Planification annulée' }
      },
      {
        method: 'POST',
        path: '/api/campaigns/{id}/variants',
        summary: 'Créer une variante A/B',
        auth: true,
        permissions: ['create_campaigns'],
        body: {
          name: 'Variante B',
          message: 'Message alternatif',
          percentage: 50
        },
        response: { message: 'Variante créée', data: { id: 1, name: 'Variante B', percentage: 50 } }
      },
      {
        method: 'GET',
        path: '/api/campaigns/{id}/variants',
        summary: "Lister les variantes d'une campagne",
        auth: true,
        permissions: ['create_campaigns'],
        response: {
          data: [
            { id: 1, name: 'Variante A', message: 'Message A', percentage: 50 },
            { id: 2, name: 'Variante B', message: 'Message B', percentage: 50 }
          ]
        }
      },
      {
        method: 'DELETE',
        path: '/api/campaigns/{id}/variants',
        summary: 'Supprimer toutes les variantes',
        auth: true,
        permissions: ['create_campaigns'],
        response: { message: 'Variantes supprimées' }
      },
      {
        method: 'GET',
        path: '/api/campaigns/history',
        summary: 'Historique des campagnes',
        auth: true,
        permissions: ['view_history'],
        parameters: [
          { name: 'status', type: 'string', description: 'Filtrer par statut', example: 'completed' },
          { name: 'date_from', type: 'string', description: 'Date de début', example: '2026-01-01' },
          { name: 'date_to', type: 'string', description: 'Date de fin', example: '2026-01-31' }
        ],
        response: {
          data: [
            { id: 1, name: 'Promo', status: 'completed', sent_count: 500, created_at: '2026-01-10T08:00:00Z' }
          ],
          meta: { total: 15 }
        }
      },
      {
        method: 'GET',
        path: '/api/campaigns/stats',
        summary: 'Statistiques globales des campagnes',
        auth: true,
        permissions: ['view_history'],
        response: {
          total_campaigns: 25,
          total_sent: 12500,
          total_delivered: 11800,
          average_delivery_rate: 94.4,
          total_cost: 250000
        }
      }
    ]
  },

  // ─── Templates ───────────────────────────────────────────────
  {
    id: 'templates',
    name: 'Templates',
    description: 'Modèles de messages réutilisables avec variables',
    endpoints: [
      {
        method: 'GET',
        path: '/api/templates',
        summary: 'Liste des templates',
        auth: true,
        permissions: ['manage_templates'],
        response: {
          data: [
            { id: 1, name: 'Bienvenue', content: 'Bienvenue {prenom} !', category: 'notification', is_public: false }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/templates',
        summary: 'Créer un template',
        auth: true,
        permissions: ['manage_templates'],
        body: {
          name: 'Confirmation',
          content: 'Bonjour {prenom}, votre commande #{custom.order_id} est confirmée.',
          category: 'transactional'
        },
        response: { message: 'Template créé', data: { id: 2, name: 'Confirmation' } }
      },
      {
        method: 'GET',
        path: '/api/templates/{id}',
        summary: "Détails d'un template",
        auth: true,
        permissions: ['manage_templates'],
        response: { id: 1, name: 'Bienvenue', content: 'Bienvenue {prenom} !', category: 'notification' }
      },
      {
        method: 'PUT',
        path: '/api/templates/{id}',
        summary: 'Modifier un template',
        auth: true,
        permissions: ['manage_templates'],
        body: { name: 'Bienvenue V2', content: 'Salut {prenom} ! Bienvenue chez SendWave.' },
        response: { message: 'Template mis à jour' }
      },
      {
        method: 'DELETE',
        path: '/api/templates/{id}',
        summary: 'Supprimer un template',
        auth: true,
        permissions: ['manage_templates'],
        response: { message: 'Template supprimé' }
      },
      {
        method: 'GET',
        path: '/api/templates/categories',
        summary: 'Liste des catégories de templates',
        auth: true,
        permissions: ['manage_templates'],
        response: { categories: ['notification', 'transactional', 'marketing', 'reminder'] }
      },
      {
        method: 'POST',
        path: '/api/templates/{id}/use',
        summary: 'Utiliser un template (incrémenter le compteur)',
        auth: true,
        permissions: ['manage_templates'],
        response: { message: 'Template utilisé', usage_count: 15 }
      },
      {
        method: 'POST',
        path: '/api/templates/{id}/preview',
        summary: "Prévisualiser un template avec des données",
        auth: true,
        permissions: ['manage_templates'],
        body: {
          variables: { prenom: 'Jean', nom: 'Dupont' }
        },
        response: { preview: 'Bonjour Jean Dupont, bienvenue !' }
      },
      {
        method: 'POST',
        path: '/api/templates/{id}/toggle-public',
        summary: 'Basculer la visibilité publique',
        auth: true,
        permissions: ['manage_templates'],
        response: { message: 'Visibilité modifiée', is_public: true }
      }
    ]
  },

  // ─── Analytics ───────────────────────────────────────────────
  {
    id: 'analytics',
    name: 'Analytics',
    description: 'Tableau de bord, graphiques, rapports et exports analytiques',
    endpoints: [
      {
        method: 'GET',
        path: '/api/analytics/dashboard',
        summary: 'Données du tableau de bord',
        auth: true,
        permissions: ['view_analytics'],
        response: {
          total_messages: 15000,
          total_delivered: 14200,
          total_failed: 800,
          delivery_rate: 94.67,
          total_cost: 300000,
          active_campaigns: 3,
          total_contacts: 2500
        }
      },
      {
        method: 'GET',
        path: '/api/analytics/chart',
        summary: 'Données pour graphiques',
        auth: true,
        permissions: ['view_analytics'],
        parameters: [
          { name: 'period', type: 'string', description: 'Période: 7d, 30d, 90d, 12m', example: '30d' },
          { name: 'type', type: 'string', description: 'Type: messages, cost, delivery_rate', example: 'messages' }
        ],
        response: {
          labels: ['01/01', '02/01', '03/01'],
          datasets: [{ label: 'Messages envoyés', data: [150, 200, 180] }]
        }
      },
      {
        method: 'GET',
        path: '/api/analytics/report',
        summary: 'Rapport analytique détaillé',
        auth: true,
        permissions: ['view_analytics'],
        parameters: [
          { name: 'date_from', type: 'string', description: 'Date de début', example: '2026-01-01' },
          { name: 'date_to', type: 'string', description: 'Date de fin', example: '2026-01-31' }
        ],
        response: {
          period: { from: '2026-01-01', to: '2026-01-31' },
          summary: { total_sent: 5000, total_cost: 100000 },
          by_operator: { airtel: { sent: 3000, cost: 60000 }, moov: { sent: 2000, cost: 40000 } }
        }
      },
      {
        method: 'GET',
        path: '/api/analytics/providers',
        summary: 'Statistiques par opérateur',
        auth: true,
        permissions: ['view_analytics'],
        response: {
          providers: [
            { name: 'airtel', sent: 8000, delivered: 7600, failed: 400, cost: 160000 },
            { name: 'moov', sent: 7000, delivered: 6600, failed: 400, cost: 140000 }
          ]
        }
      },
      {
        method: 'GET',
        path: '/api/analytics/top-campaigns',
        summary: 'Top campagnes par performance',
        auth: true,
        permissions: ['view_analytics'],
        response: {
          data: [
            { id: 1, name: 'Promo Noel', sent: 2000, delivery_rate: 96.5, cost: 40000 }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/analytics/update',
        summary: 'Recalculer les analytics',
        auth: true,
        permissions: ['view_analytics'],
        response: { message: 'Analytics mises à jour' }
      },
      {
        method: 'GET',
        path: '/api/analytics/export/pdf',
        summary: 'Exporter le rapport en PDF',
        auth: true,
        permissions: ['export_data'],
        parameters: [
          { name: 'date_from', type: 'string', description: 'Date de début', example: '2026-01-01' },
          { name: 'date_to', type: 'string', description: 'Date de fin', example: '2026-01-31' }
        ],
        response: { _comment: 'Téléchargement PDF' }
      },
      {
        method: 'GET',
        path: '/api/analytics/export/excel',
        summary: 'Exporter le rapport en Excel',
        auth: true,
        permissions: ['export_data'],
        response: { _comment: 'Téléchargement XLSX' }
      },
      {
        method: 'GET',
        path: '/api/analytics/export/csv',
        summary: 'Exporter le rapport en CSV',
        auth: true,
        permissions: ['export_data'],
        response: { _comment: 'Téléchargement CSV' }
      }
    ]
  },

  // ─── SMS Analytics ───────────────────────────────────────────
  {
    id: 'sms-analytics',
    name: 'SMS Analytics',
    description: 'Comptabilité analytique SMS, périodes et clôtures mensuelles',
    endpoints: [
      {
        method: 'GET',
        path: '/api/sms-analytics',
        summary: 'Liste des analytics SMS',
        auth: true,
        permissions: ['view_analytics'],
        parameters: [
          { name: 'date_from', type: 'string', description: 'Date de début', example: '2026-01-01' },
          { name: 'date_to', type: 'string', description: 'Date de fin', example: '2026-01-31' }
        ],
        response: {
          data: [
            { id: 1, phone: '24177123456', provider: 'airtel', cost: 20, status: 'delivered', created_at: '2026-01-15' }
          ],
          meta: { total: 500 }
        }
      },
      {
        method: 'GET',
        path: '/api/sms-analytics/overview',
        summary: 'Vue d\'ensemble analytique',
        auth: true,
        permissions: ['view_analytics'],
        response: {
          current_month: { sent: 1200, cost: 24000, delivery_rate: 95.2 },
          previous_month: { sent: 1000, cost: 20000, delivery_rate: 93.8 },
          growth: { sent: 20, cost: 20 }
        }
      },
      {
        method: 'GET',
        path: '/api/sms-analytics/periods',
        summary: 'Liste des périodes disponibles',
        auth: true,
        permissions: ['view_analytics'],
        response: {
          periods: [
            { key: '2026-01', label: 'Janvier 2026', closed: true },
            { key: '2026-02', label: 'Février 2026', closed: false }
          ]
        }
      },
      {
        method: 'GET',
        path: '/api/sms-analytics/closures',
        summary: 'Liste des clôtures mensuelles',
        auth: true,
        permissions: ['view_analytics'],
        response: {
          data: [
            { period_key: '2026-01', total_sent: 5000, total_cost: 100000, closed_at: '2026-02-01T00:30:00Z' }
          ]
        }
      },
      {
        method: 'GET',
        path: '/api/sms-analytics/closures/{periodKey}',
        summary: "Détails d'une clôture",
        auth: true,
        permissions: ['view_analytics'],
        response: {
          period_key: '2026-01',
          total_sent: 5000,
          total_delivered: 4750,
          total_failed: 250,
          total_cost: 100000,
          by_operator: { airtel: 3000, moov: 2000 },
          closed_at: '2026-02-01T00:30:00Z'
        }
      },
      {
        method: 'POST',
        path: '/api/sms-analytics/report',
        summary: 'Générer un rapport analytique',
        auth: true,
        permissions: ['view_analytics'],
        body: {
          period: '2026-01',
          format: 'detailed'
        },
        response: {
          report: {
            period: '2026-01',
            summary: { total_sent: 5000, total_cost: 100000 }
          }
        }
      },
      {
        method: 'GET',
        path: '/api/sms-analytics/export',
        summary: 'Exporter les analytics SMS',
        auth: true,
        permissions: ['export_data'],
        parameters: [
          { name: 'period', type: 'string', description: 'Période (YYYY-MM)', example: '2026-01' },
          { name: 'format', type: 'string', description: 'Format: csv, xlsx, pdf', example: 'csv' }
        ],
        response: { _comment: 'Téléchargement du fichier' }
      }
    ]
  },

  // ─── Budget ──────────────────────────────────────────────────
  {
    id: 'budget',
    name: 'Budget',
    description: 'Gestion des budgets et plafonds mensuels par sous-compte',
    endpoints: [
      {
        method: 'GET',
        path: '/api/budgets/status/{subAccountId?}',
        summary: 'Statut du budget',
        description: "Retourne le budget restant, utilisé et le plafond. Sans paramètre, retourne le budget du compte principal.",
        auth: true,
        response: {
          sub_account_id: 1,
          monthly_limit: 500000,
          used: 125000,
          remaining: 375000,
          percentage_used: 25.0,
          alert_threshold: 80
        }
      },
      {
        method: 'GET',
        path: '/api/budgets/all',
        summary: 'Budgets de tous les sous-comptes',
        auth: true,
        response: {
          data: [
            { sub_account_id: 1, name: 'Équipe Marketing', monthly_limit: 500000, used: 125000 }
          ]
        }
      },
      {
        method: 'PUT',
        path: '/api/budgets/{subAccountId}',
        summary: "Modifier le budget d'un sous-compte",
        auth: true,
        body: {
          monthly_limit: 750000,
          alert_threshold: 85
        },
        response: { message: 'Budget mis à jour', monthly_limit: 750000 }
      },
      {
        method: 'POST',
        path: '/api/budgets/check-send',
        summary: "Vérifier si l'envoi est possible (budget)",
        auth: true,
        body: {
          recipients_count: 100,
          cost_per_sms: 20
        },
        response: {
          can_send: true,
          estimated_cost: 2000,
          remaining_budget: 373000
        }
      },
      {
        method: 'GET',
        path: '/api/budgets/history/{subAccountId?}',
        summary: 'Historique des consommations budget',
        auth: true,
        parameters: [
          { name: 'period', type: 'string', description: 'Période (YYYY-MM)', example: '2026-01' }
        ],
        response: {
          data: [
            { date: '2026-01-15', amount: 5000, type: 'sms_send', campaign: 'Promo' }
          ]
        }
      }
    ]
  },

  // ─── Liste Noire ─────────────────────────────────────────────
  {
    id: 'blacklist',
    name: 'Liste Noire',
    description: 'Gestion des numéros bloqués, mots-clés STOP',
    endpoints: [
      {
        method: 'GET',
        path: '/api/blacklist',
        summary: 'Liste des numéros bloqués',
        auth: true,
        parameters: [
          { name: 'source', type: 'string', description: 'Filtrer par source: manual, auto_stop, import', example: 'auto_stop' }
        ],
        response: {
          data: [
            { id: 1, phone: '+24177000000', source: 'auto_stop', reason: 'STOP reçu', created_at: '2026-01-15' }
          ],
          meta: { total: 25 }
        }
      },
      {
        method: 'POST',
        path: '/api/blacklist',
        summary: 'Ajouter un numéro à la liste noire',
        auth: true,
        body: {
          phone: '+24177000000',
          reason: 'Demande du client'
        },
        response: { message: 'Numéro ajouté à la liste noire' }
      },
      {
        method: 'DELETE',
        path: '/api/blacklist/{id}',
        summary: 'Retirer un numéro de la liste noire',
        auth: true,
        response: { message: 'Numéro retiré de la liste noire' }
      },
      {
        method: 'POST',
        path: '/api/blacklist/check',
        summary: 'Vérifier si un numéro est bloqué',
        auth: true,
        body: { phone: '+24177000000' },
        response: { phone: '+24177000000', is_blacklisted: true, source: 'auto_stop' }
      },
      {
        method: 'GET',
        path: '/api/blacklist/stats',
        summary: 'Statistiques de la liste noire',
        auth: true,
        response: {
          total: 25,
          by_source: { manual: 10, auto_stop: 12, import: 3 }
        }
      },
      {
        method: 'GET',
        path: '/api/blacklist/stop-keywords',
        summary: 'Liste des mots-clés STOP détectés',
        auth: true,
        response: {
          keywords: ['STOP', 'ARRET', 'DESABONNER', 'UNSUBSCRIBE', 'CANCEL', 'QUIT', 'END', 'OPTOUT']
        }
      }
    ]
  },

  // ─── Webhooks ────────────────────────────────────────────────
  {
    id: 'webhooks',
    name: 'Webhooks',
    description: 'Configuration des webhooks, événements, logs et tests',
    endpoints: [
      {
        method: 'GET',
        path: '/api/webhooks',
        summary: 'Liste des webhooks configurés',
        auth: true,
        response: {
          data: [
            { id: 1, url: 'https://example.com/webhook', events: ['message.sent', 'message.delivered'], active: true }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/webhooks',
        summary: 'Créer un webhook',
        auth: true,
        body: {
          url: 'https://example.com/webhook',
          events: ['message.sent', 'message.delivered', 'message.failed'],
          secret: 'my-webhook-secret'
        },
        response: { message: 'Webhook créé', data: { id: 2, url: 'https://example.com/webhook' } }
      },
      {
        method: 'GET',
        path: '/api/webhooks/{id}',
        summary: "Détails d'un webhook",
        auth: true,
        response: {
          id: 1,
          url: 'https://example.com/webhook',
          events: ['message.sent', 'message.delivered'],
          active: true,
          secret: '********',
          created_at: '2026-01-01T00:00:00Z'
        }
      },
      {
        method: 'PUT',
        path: '/api/webhooks/{id}',
        summary: 'Modifier un webhook',
        auth: true,
        body: { url: 'https://example.com/new-webhook', events: ['message.sent'] },
        response: { message: 'Webhook mis à jour' }
      },
      {
        method: 'DELETE',
        path: '/api/webhooks/{id}',
        summary: 'Supprimer un webhook',
        auth: true,
        response: { message: 'Webhook supprimé' }
      },
      {
        method: 'GET',
        path: '/api/webhooks/events',
        summary: 'Liste des événements disponibles',
        auth: true,
        response: {
          events: [
            { key: 'message.sent', description: 'Message envoyé' },
            { key: 'message.delivered', description: 'Message délivré' },
            { key: 'message.failed', description: "Échec d'envoi" },
            { key: 'message.received', description: 'Réponse SMS reçue' },
            { key: 'campaign.started', description: 'Campagne démarrée' },
            { key: 'campaign.completed', description: 'Campagne terminée' },
            { key: 'contact.unsubscribed', description: 'Contact désabonné (STOP)' }
          ]
        }
      },
      {
        method: 'GET',
        path: '/api/webhooks/{id}/logs',
        summary: "Logs de livraison d'un webhook",
        auth: true,
        response: {
          data: [
            { id: 1, event: 'message.sent', status_code: 200, response_time: 150, created_at: '2026-01-15T10:30:00Z' }
          ]
        }
      },
      {
        method: 'GET',
        path: '/api/webhooks/{id}/stats',
        summary: "Statistiques d'un webhook",
        auth: true,
        response: {
          total_calls: 500,
          successful: 490,
          failed: 10,
          average_response_time: 120
        }
      },
      {
        method: 'POST',
        path: '/api/webhooks/{id}/test',
        summary: 'Tester un webhook',
        auth: true,
        response: {
          success: true,
          status_code: 200,
          response_time: 95
        }
      },
      {
        method: 'POST',
        path: '/api/webhooks/{id}/toggle',
        summary: 'Activer/désactiver un webhook',
        auth: true,
        response: { message: 'Webhook activé', active: true }
      }
    ]
  },

  // ─── Clés API ────────────────────────────────────────────────
  {
    id: 'api-keys',
    name: 'Clés API',
    description: 'Gestion des clés API pour les intégrations externes',
    endpoints: [
      {
        method: 'GET',
        path: '/api/api-keys',
        summary: 'Liste des clés API',
        auth: true,
        response: {
          data: [
            { id: 1, name: 'App Mobile', key: 'sk_live_****...', type: 'production', status: 'active', rate_limit: 100, permissions: ['send_sms', 'view_history'] }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/api-keys',
        summary: 'Créer une clé API',
        auth: true,
        body: {
          name: 'Nouvelle App',
          type: 'production',
          permissions: ['send_sms', 'view_history'],
          rate_limit: 100
        },
        response: {
          message: 'Clé API créée',
          data: { id: 2, name: 'Nouvelle App', key: 'swp_live_xxxxxxxxxxxxx' },
          full_key: 'swp_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
        }
      },
      {
        method: 'GET',
        path: '/api/api-keys/{id}',
        summary: "Détails d'une clé API",
        auth: true,
        response: { id: 1, name: 'App Mobile', type: 'production', status: 'active', permissions: ['send_sms'], rate_limit: 100, last_used_at: '2026-01-15' }
      },
      {
        method: 'PUT',
        path: '/api/api-keys/{id}',
        summary: 'Modifier une clé API',
        auth: true,
        body: { name: 'App Mobile V2', permissions: ['send_sms', 'view_history', 'manage_contacts'], rate_limit: 200 },
        response: { message: 'Clé API mise à jour' }
      },
      {
        method: 'DELETE',
        path: '/api/api-keys/{id}',
        summary: 'Supprimer une clé API',
        auth: true,
        response: { message: 'Clé API supprimée' }
      },
      {
        method: 'POST',
        path: '/api/api-keys/{id}/revoke',
        summary: 'Révoquer une clé API',
        description: "Désactive définitivement la clé. Les applications l'utilisant perdent l'accès.",
        auth: true,
        response: { message: 'Clé API révoquée' }
      },
      {
        method: 'POST',
        path: '/api/api-keys/{id}/regenerate',
        summary: 'Régénérer une clé API',
        description: "Génère une nouvelle clé et invalide l'ancienne.",
        auth: true,
        response: {
          message: 'Clé régénérée',
          full_key: 'sk_live_new_xxxxxxxxxxxxxxxxxxxxx'
        }
      }
    ]
  },

  // ─── Sous-comptes ────────────────────────────────────────────
  {
    id: 'sub-accounts',
    name: 'Sous-comptes',
    description: 'Gestion des sous-comptes avec rôles, budgets et permissions',
    endpoints: [
      {
        method: 'GET',
        path: '/api/sub-accounts',
        summary: 'Liste des sous-comptes',
        auth: true,
        response: {
          data: [
            { id: 1, name: 'Équipe Marketing', email: 'marketing@company.com', role: 'manager', status: 'active', credits: 50000 }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/sub-accounts',
        summary: 'Créer un sous-compte',
        auth: true,
        body: {
          name: 'Équipe Ventes',
          email: 'ventes@company.com',
          password: 'password123',
          role: 'sender',
          credits: 100000
        },
        response: { message: 'Sous-compte créé', data: { id: 2, name: 'Équipe Ventes' } }
      },
      {
        method: 'GET',
        path: '/api/sub-accounts/{id}',
        summary: "Détails d'un sous-compte",
        auth: true,
        response: { id: 1, name: 'Équipe Marketing', email: 'marketing@company.com', role: 'manager', status: 'active', credits: 50000, permissions: ['send_sms', 'view_history'] }
      },
      {
        method: 'PUT',
        path: '/api/sub-accounts/{id}',
        summary: 'Modifier un sous-compte',
        auth: true,
        body: { name: 'Marketing Pro', role: 'admin' },
        response: { message: 'Sous-compte mis à jour' }
      },
      {
        method: 'DELETE',
        path: '/api/sub-accounts/{id}',
        summary: 'Supprimer un sous-compte',
        auth: true,
        response: { message: 'Sous-compte supprimé' }
      },
      {
        method: 'POST',
        path: '/api/sub-accounts/login',
        summary: 'Connexion sous-compte',
        auth: false,
        body: { email: 'marketing@company.com', password: 'password123' },
        response: { access_token: '2|xyz...', token_type: 'Bearer', sub_account: { id: 1, name: 'Équipe Marketing' } }
      },
      {
        method: 'POST',
        path: '/api/sub-accounts/{id}/credits',
        summary: 'Ajouter/retirer des crédits',
        auth: true,
        body: { amount: 50000, type: 'add', reason: 'Recharge mensuelle' },
        response: { message: 'Crédits mis à jour', new_balance: 100000 }
      },
      {
        method: 'POST',
        path: '/api/sub-accounts/{id}/permissions',
        summary: 'Modifier les permissions',
        auth: true,
        body: { permissions: ['send_sms', 'view_history', 'manage_contacts', 'view_analytics'] },
        response: { message: 'Permissions mises à jour' }
      },
      {
        method: 'POST',
        path: '/api/sub-accounts/{id}/suspend',
        summary: 'Suspendre un sous-compte',
        auth: true,
        response: { message: 'Sous-compte suspendu' }
      },
      {
        method: 'POST',
        path: '/api/sub-accounts/{id}/activate',
        summary: 'Réactiver un sous-compte',
        auth: true,
        response: { message: 'Sous-compte réactivé' }
      }
    ]
  },

  // ─── Config SMS ──────────────────────────────────────────────
  {
    id: 'sms-config',
    name: 'Configuration SMS',
    description: "Configuration des opérateurs SMS (Airtel, Moov) et providers",
    endpoints: [
      {
        method: 'GET',
        path: '/api/sms-configs',
        summary: 'Liste des configurations SMS',
        auth: true,
        response: {
          data: [
            { provider: 'airtel', enabled: true, cost_per_sms: 20, last_tested: '2026-01-15' },
            { provider: 'moov', enabled: false, cost_per_sms: 20, last_tested: null }
          ]
        }
      },
      {
        method: 'GET',
        path: '/api/sms-configs/{provider}',
        summary: "Configuration d'un opérateur",
        auth: true,
        response: {
          provider: 'airtel',
          enabled: true,
          api_url: 'https://messaging.airtel.ga:9002/...',
          cost_per_sms: 20,
          sender_id: 'SENDWAVE'
        }
      },
      {
        method: 'PUT',
        path: '/api/sms-configs/{provider}',
        summary: "Modifier la configuration d'un opérateur",
        auth: true,
        body: {
          api_url: 'https://new-url.com/api',
          username: 'user',
          password: 'pass',
          cost_per_sms: 25
        },
        response: { message: 'Configuration mise à jour' }
      },
      {
        method: 'POST',
        path: '/api/sms-configs/{provider}/test',
        summary: 'Tester la connexion opérateur',
        auth: true,
        response: { success: true, message: 'Connexion réussie', response_time: 250 }
      },
      {
        method: 'POST',
        path: '/api/sms-configs/{provider}/toggle',
        summary: 'Activer/désactiver un opérateur',
        auth: true,
        response: { message: 'Opérateur activé', enabled: true }
      },
      {
        method: 'POST',
        path: '/api/sms-configs/{provider}/reset',
        summary: 'Réinitialiser la configuration',
        auth: true,
        response: { message: 'Configuration réinitialisée aux valeurs par défaut' }
      },
      {
        method: 'GET',
        path: '/api/sms-providers',
        summary: 'Liste des providers SMS disponibles',
        auth: true,
        response: {
          data: [
            { code: 'airtel', name: 'Airtel Gabon', type: 'http', prefixes: ['74', '76', '77'] },
            { code: 'moov', name: 'Moov Gabon', type: 'smpp', prefixes: ['60', '62', '65', '66'] }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/sms-providers',
        summary: 'Ajouter un provider SMS',
        auth: true,
        body: { code: 'orange', name: 'Orange', type: 'http', prefixes: ['07'] },
        response: { message: 'Provider créé' }
      },
      {
        method: 'GET',
        path: '/api/sms-providers/{code}',
        summary: "Détails d'un provider",
        auth: true,
        response: { code: 'airtel', name: 'Airtel Gabon', type: 'http', prefixes: ['74', '76', '77'], enabled: true }
      },
      {
        method: 'POST',
        path: '/api/sms-providers/{code}/test',
        summary: 'Tester un provider',
        auth: true,
        response: { success: true, message: 'Test réussi' }
      }
    ]
  },

  // ─── Normalisation Téléphone ─────────────────────────────────
  {
    id: 'phone',
    name: 'Normalisation Téléphone',
    description: 'Normalisation E.164, détection pays et opérateur',
    endpoints: [
      {
        method: 'GET',
        path: '/api/phone/countries',
        summary: 'Liste des pays supportés',
        auth: false,
        response: {
          countries: [
            { code: 'GA', name: 'Gabon', prefix: '241', local_length: 8 },
            { code: 'CM', name: 'Cameroun', prefix: '237', local_length: 9 },
            { code: 'CG', name: 'Congo', prefix: '242', local_length: 9 },
            { code: 'CI', name: "Côte d'Ivoire", prefix: '225', local_length: 10 },
            { code: 'SN', name: 'Sénégal', prefix: '221', local_length: 9 }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/phone/normalize',
        summary: 'Normaliser un numéro de téléphone',
        auth: true,
        body: {
          phone: '77123456',
          country: 'GA'
        },
        response: {
          original: '77123456',
          normalized: '+24177123456',
          country: 'GA',
          operator: 'airtel',
          valid: true
        }
      },
      {
        method: 'POST',
        path: '/api/phone/normalize-many',
        summary: 'Normaliser plusieurs numéros',
        auth: true,
        body: {
          phones: ['77123456', '62987654', '+237650123456']
        },
        response: {
          results: [
            { original: '77123456', normalized: '+24177123456', country: 'GA', operator: 'airtel', valid: true },
            { original: '62987654', normalized: '+24162987654', country: 'GA', operator: 'moov', valid: true },
            { original: '+237650123456', normalized: '+237650123456', country: 'CM', operator: 'unknown', valid: true }
          ],
          summary: { total: 3, valid: 3, invalid: 0 }
        }
      }
    ]
  },

  // ─── Webhooks Incoming ───────────────────────────────────────
  {
    id: 'incoming-sms',
    name: 'SMS Entrants',
    description: "Réception de SMS entrants et webhooks opérateurs",
    endpoints: [
      {
        method: 'POST',
        path: '/api/webhooks/incoming/sms',
        summary: 'Webhook SMS entrant (générique)',
        auth: false,
        description: "Endpoint public pour recevoir les SMS entrants. Détecte automatiquement les mots-clés STOP.",
        body: {
          from: '+24177123456',
          to: 'SENDWAVE',
          message: 'STOP',
          timestamp: '2026-01-15T10:30:00Z'
        },
        response: { status: 'received', action: 'blacklisted' }
      },
      {
        method: 'POST',
        path: '/api/webhooks/incoming/airtel',
        summary: 'Webhook SMS entrant Airtel',
        auth: false,
        description: "Endpoint spécifique pour le format de notification Airtel.",
        body: {
          sender: '24177123456',
          receiver: 'SENDWAVE',
          text: 'Hello',
          messageId: 'AIR123456'
        },
        response: { status: 'received' }
      },
      {
        method: 'POST',
        path: '/api/webhooks/incoming/moov',
        summary: 'Webhook SMS entrant Moov',
        auth: false,
        description: "Endpoint spécifique pour le format de notification Moov (SMPP DLR).",
        body: {
          source_addr: '24162987654',
          dest_addr: 'SENDWAVE',
          short_message: 'Hello',
          message_id: 'MOOV789'
        },
        response: { status: 'received' }
      }
    ]
  },

  // ─── Journal d'Audit ─────────────────────────────────────────
  {
    id: 'audit-logs',
    name: "Journal d'Audit",
    description: "Traçabilité des actions utilisateurs et système",
    endpoints: [
      {
        method: 'GET',
        path: '/api/audit-logs',
        summary: "Liste des logs d'audit",
        auth: true,
        parameters: [
          { name: 'action', type: 'string', description: "Filtrer par action", example: 'sms.sent' },
          { name: 'user_id', type: 'integer', description: "Filtrer par utilisateur" },
          { name: 'date_from', type: 'string', description: 'Date de début', example: '2026-01-01' },
          { name: 'date_to', type: 'string', description: 'Date de fin', example: '2026-01-31' }
        ],
        response: {
          data: [
            { id: 1, action: 'sms.sent', user: 'Admin', details: '500 SMS envoyés', ip: '192.168.1.1', created_at: '2026-01-15T10:30:00Z' }
          ],
          meta: { total: 200 }
        }
      },
      {
        method: 'GET',
        path: '/api/audit-logs/actions',
        summary: "Liste des types d'actions",
        auth: true,
        response: {
          actions: ['sms.sent', 'campaign.created', 'contact.imported', 'api_key.created', 'user.login']
        }
      },
      {
        method: 'GET',
        path: '/api/audit-logs/{id}',
        summary: "Détail d'un log d'audit",
        auth: true,
        response: {
          id: 1,
          action: 'sms.sent',
          user: { id: 1, name: 'Admin' },
          details: { recipients: 500, cost: 10000, campaign_id: 5 },
          ip: '192.168.1.1',
          user_agent: 'Mozilla/5.0...',
          created_at: '2026-01-15T10:30:00Z'
        }
      }
    ]
  },

  // ─── Comptes (SuperAdmin) ────────────────────────────────────
  {
    id: 'accounts',
    name: 'Comptes',
    description: 'Gestion des comptes clients (SuperAdmin uniquement)',
    endpoints: [
      {
        method: 'GET',
        path: '/api/accounts',
        summary: 'Liste des comptes',
        auth: true,
        permissions: ['superadmin'],
        response: {
          data: [
            { id: 1, name: 'Entreprise A', email: 'admin@entreprise-a.com', status: 'active', credits: 500000 }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/accounts',
        summary: 'Créer un compte',
        auth: true,
        permissions: ['superadmin'],
        body: {
          name: 'Nouvelle Entreprise',
          email: 'admin@nouvelle.com',
          password: 'password123',
          credits: 100000
        },
        response: { message: 'Compte créé', data: { id: 2, name: 'Nouvelle Entreprise' } }
      },
      {
        method: 'GET',
        path: '/api/accounts/{id}',
        summary: "Détails d'un compte",
        auth: true,
        permissions: ['superadmin'],
        response: { id: 1, name: 'Entreprise A', email: 'admin@entreprise-a.com', status: 'active', credits: 500000 }
      },
      {
        method: 'PUT',
        path: '/api/accounts/{id}',
        summary: 'Modifier un compte',
        auth: true,
        permissions: ['superadmin'],
        body: { name: 'Entreprise A Pro' },
        response: { message: 'Compte mis à jour' }
      },
      {
        method: 'DELETE',
        path: '/api/accounts/{id}',
        summary: 'Supprimer un compte',
        auth: true,
        permissions: ['superadmin'],
        response: { message: 'Compte supprimé' }
      },
      {
        method: 'POST',
        path: '/api/accounts/{id}/credits',
        summary: 'Ajouter des crédits',
        auth: true,
        permissions: ['superadmin'],
        body: { amount: 100000, reason: 'Achat crédits' },
        response: { message: 'Crédits ajoutés', new_balance: 600000 }
      },
      {
        method: 'POST',
        path: '/api/accounts/{id}/suspend',
        summary: 'Suspendre un compte',
        auth: true,
        permissions: ['superadmin'],
        response: { message: 'Compte suspendu' }
      },
      {
        method: 'POST',
        path: '/api/accounts/{id}/activate',
        summary: 'Réactiver un compte',
        auth: true,
        permissions: ['superadmin'],
        response: { message: 'Compte réactivé' }
      },
      {
        method: 'GET',
        path: '/api/accounts/{id}/stats',
        summary: "Statistiques d'un compte",
        auth: true,
        permissions: ['superadmin'],
        response: { total_sent: 15000, total_cost: 300000, users_count: 5, active_campaigns: 2 }
      },
      {
        method: 'GET',
        path: '/api/accounts/{id}/users',
        summary: "Utilisateurs d'un compte",
        auth: true,
        permissions: ['superadmin'],
        response: { data: [{ id: 1, name: 'Admin', email: 'admin@company.com', role: 'admin' }] }
      }
    ]
  },

  // ─── Utilisateurs ────────────────────────────────────────────
  {
    id: 'users',
    name: 'Utilisateurs',
    description: "Gestion des utilisateurs d'un compte, rôles et permissions",
    endpoints: [
      {
        method: 'GET',
        path: '/api/users',
        summary: 'Liste des utilisateurs',
        auth: true,
        permissions: ['manage_sub_accounts'],
        response: {
          data: [
            { id: 1, name: 'John Doe', email: 'john@company.com', role: 'manager', status: 'active' }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/users',
        summary: 'Créer un utilisateur',
        auth: true,
        permissions: ['manage_sub_accounts'],
        body: { name: 'Jane Doe', email: 'jane@company.com', password: 'password123', role: 'sender' },
        response: { message: 'Utilisateur créé', data: { id: 2, name: 'Jane Doe' } }
      },
      {
        method: 'GET',
        path: '/api/users/{id}',
        summary: "Détails d'un utilisateur",
        auth: true,
        permissions: ['manage_sub_accounts'],
        response: { id: 1, name: 'John Doe', email: 'john@company.com', role: 'manager', permissions: ['send_sms', 'view_history'] }
      },
      {
        method: 'PUT',
        path: '/api/users/{id}',
        summary: 'Modifier un utilisateur',
        auth: true,
        permissions: ['manage_sub_accounts'],
        body: { name: 'John Updated', role: 'admin' },
        response: { message: 'Utilisateur mis à jour' }
      },
      {
        method: 'DELETE',
        path: '/api/users/{id}',
        summary: 'Supprimer un utilisateur',
        auth: true,
        permissions: ['manage_sub_accounts'],
        response: { message: 'Utilisateur supprimé' }
      },
      {
        method: 'POST',
        path: '/api/users/{id}/suspend',
        summary: 'Suspendre un utilisateur',
        auth: true,
        permissions: ['manage_sub_accounts'],
        response: { message: 'Utilisateur suspendu' }
      },
      {
        method: 'POST',
        path: '/api/users/{id}/activate',
        summary: 'Réactiver un utilisateur',
        auth: true,
        permissions: ['manage_sub_accounts'],
        response: { message: 'Utilisateur réactivé' }
      },
      {
        method: 'PUT',
        path: '/api/users/{id}/permissions',
        summary: "Modifier les permissions d'un utilisateur",
        auth: true,
        permissions: ['manage_sub_accounts'],
        body: { permissions: ['send_sms', 'view_history', 'manage_contacts'] },
        response: { message: 'Permissions mises à jour' }
      },
      {
        method: 'GET',
        path: '/api/users/available-roles',
        summary: 'Liste des rôles disponibles',
        auth: true,
        permissions: ['manage_sub_accounts'],
        response: { roles: ['admin', 'manager', 'sender', 'viewer'] }
      },
      {
        method: 'GET',
        path: '/api/users/available-permissions',
        summary: 'Permissions attribuables',
        auth: true,
        permissions: ['manage_sub_accounts'],
        response: {
          permissions: [
            { key: 'send_sms', label: 'Envoyer des SMS' },
            { key: 'view_history', label: "Voir l'historique" },
            { key: 'manage_contacts', label: 'Gérer les contacts' }
          ]
        }
      }
    ]
  },

  // ─── Rôles Personnalisés ─────────────────────────────────────
  {
    id: 'custom-roles',
    name: 'Rôles Personnalisés',
    description: 'Création et gestion de rôles personnalisés (SuperAdmin)',
    endpoints: [
      {
        method: 'GET',
        path: '/api/custom-roles',
        summary: 'Liste des rôles personnalisés',
        auth: true,
        permissions: ['superadmin'],
        response: {
          data: [
            { id: 1, name: 'Superviseur', permissions: ['send_sms', 'view_history', 'view_analytics'], users_count: 3 }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/custom-roles',
        summary: 'Créer un rôle personnalisé',
        auth: true,
        permissions: ['superadmin'],
        body: {
          name: 'Opérateur',
          description: 'Peut envoyer des SMS et voir les stats',
          permissions: ['send_sms', 'view_history', 'view_analytics']
        },
        response: { message: 'Rôle créé', data: { id: 2, name: 'Opérateur' } }
      },
      {
        method: 'GET',
        path: '/api/custom-roles/{id}',
        summary: "Détails d'un rôle",
        auth: true,
        permissions: ['superadmin'],
        response: { id: 1, name: 'Superviseur', description: 'Supervision des envois', permissions: ['send_sms', 'view_history'] }
      },
      {
        method: 'PUT',
        path: '/api/custom-roles/{id}',
        summary: 'Modifier un rôle',
        auth: true,
        permissions: ['superadmin'],
        body: { name: 'Superviseur Pro', permissions: ['send_sms', 'view_history', 'view_analytics', 'manage_contacts'] },
        response: { message: 'Rôle mis à jour' }
      },
      {
        method: 'DELETE',
        path: '/api/custom-roles/{id}',
        summary: 'Supprimer un rôle',
        auth: true,
        permissions: ['superadmin'],
        response: { message: 'Rôle supprimé' }
      },
      {
        method: 'POST',
        path: '/api/custom-roles/{id}/duplicate',
        summary: 'Dupliquer un rôle',
        auth: true,
        permissions: ['superadmin'],
        response: { message: 'Rôle dupliqué', data: { id: 3, name: 'Superviseur (copie)' } }
      },
      {
        method: 'GET',
        path: '/api/custom-roles/permissions',
        summary: 'Permissions attribuables aux rôles',
        auth: true,
        permissions: ['superadmin'],
        response: {
          permissions: [
            { key: 'send_sms', label: 'Envoyer des SMS', group: 'Messages' },
            { key: 'view_history', label: "Voir l'historique", group: 'Messages' },
            { key: 'manage_contacts', label: 'Gérer les contacts', group: 'Contacts' }
          ]
        }
      }
    ]
  },

  // ─── Système ─────────────────────────────────────────────────
  {
    id: 'system',
    name: 'Système',
    description: 'Endpoints systèmes en lecture seule',
    endpoints: [
      {
        method: 'GET',
        path: '/api/system-roles',
        summary: 'Rôles système prédéfinis',
        auth: true,
        response: {
          roles: [
            { key: 'admin', label: 'Administrateur', description: 'Accès complet' },
            { key: 'manager', label: 'Manager', description: 'Gestion des envois et contacts' },
            { key: 'sender', label: 'Envoyeur', description: 'Envoi de SMS uniquement' },
            { key: 'viewer', label: 'Lecteur', description: 'Lecture seule' }
          ]
        }
      },
      {
        method: 'GET',
        path: '/api/system-permissions',
        summary: 'Permissions système',
        auth: true,
        response: {
          permissions: ['send_sms', 'view_history', 'manage_contacts', 'manage_groups', 'create_campaigns', 'view_analytics', 'manage_templates', 'export_data']
        }
      }
    ]
  }
]

// Helper function to count total endpoints
export function getTotalEndpoints(): number {
  return apiCategories.reduce((sum, cat) => sum + cat.endpoints.length, 0)
}

// Helper to get method badge color
export function getMethodColor(method: string): { bg: string; text: string } {
  const colors: Record<string, { bg: string; text: string }> = {
    GET: { bg: 'bg-blue-100 dark:bg-blue-900/50', text: 'text-blue-700 dark:text-blue-300' },
    POST: { bg: 'bg-green-100 dark:bg-green-900/50', text: 'text-green-700 dark:text-green-300' },
    PUT: { bg: 'bg-orange-100 dark:bg-orange-900/50', text: 'text-orange-700 dark:text-orange-300' },
    DELETE: { bg: 'bg-red-100 dark:bg-red-900/50', text: 'text-red-700 dark:text-red-300' }
  }
  return colors[method] || { bg: 'bg-gray-100', text: 'text-gray-700' }
}

// Helper to get method badge solid color (for compact views)
export function getMethodBadgeClass(method: string): string {
  const classes: Record<string, string> = {
    GET: 'bg-blue-600 text-white',
    POST: 'bg-green-600 text-white',
    PUT: 'bg-orange-500 text-white',
    DELETE: 'bg-red-600 text-white'
  }
  return classes[method] || 'bg-gray-600 text-white'
}
