import { useState, useEffect } from "react";
import { Layout } from "@/components/layout/Layout";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { Plus, Webhook, Activity, AlertCircle, Shield, RefreshCw, Clock } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import webhookService, { Webhook as WebhookType } from "@/services/webhookService";

export default function Webhooks() {
  const { toast } = useToast();
  const [webhooks, setWebhooks] = useState<WebhookType[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadWebhooks();
  }, []);

  const loadWebhooks = async () => {
    try {
      setLoading(true);
      const data = await webhookService.getAll();
      setWebhooks(data);
    } catch (error) {
      console.error('Failed to load webhooks:', error);
      toast({
        title: "Erreur",
        description: "Impossible de charger les webhooks",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const columns: Column<WebhookType>[] = [
    {
      key: 'name',
      header: 'Nom',
    },
    {
      key: 'url',
      header: 'URL',
      render: (value) => (
        <span className="text-sm font-mono text-muted-foreground truncate block max-w-xs">
          {value}
        </span>
      )
    },
    {
      key: 'events',
      header: 'Événements',
      render: (value: string[]) => (
        <span className="text-sm">{value.length} événement(s)</span>
      )
    },
    {
      key: 'is_active',
      header: 'Statut',
      render: (value) => (
        <span className={`px-2 py-1 rounded-full text-xs font-medium ${
          value ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
        }`}>
          {value ? 'Actif' : 'Inactif'}
        </span>
      )
    },
    {
      key: 'success_count',
      header: 'Succès',
      render: (value) => (
        <span className="text-green-600 font-medium">{value}</span>
      )
    },
    {
      key: 'failure_count',
      header: 'Échecs',
      render: (value) => (
        <span className="text-red-600 font-medium">{value}</span>
      )
    }
  ];

  const actions: Action<WebhookType>[] = [
    {
      label: 'Voir les logs',
      onClick: async (webhook) => {
        toast({
          title: "Logs du webhook",
          description: `Affichage des logs pour "${webhook.name}"`,
        });
      }
    },
    {
      label: 'Tester',
      onClick: async (webhook) => {
        try {
          const result = await webhookService.test(webhook.id);
          toast({
            title: result.success ? "Test réussi" : "Test échoué",
            description: `Code: ${result.response_code}`,
            variant: result.success ? "default" : "destructive"
          });
        } catch (error) {
          toast({
            title: "Erreur",
            description: "Impossible de tester le webhook",
            variant: "destructive"
          });
        }
      }
    },
    {
      label: 'Toggle',
      onClick: async (webhook) => {
        try {
          await webhookService.toggle(webhook.id);
          toast({
            title: "Statut modifié",
            description: `Le webhook "${webhook.name}" a été ${webhook.is_active ? 'désactivé' : 'activé'}`,
          });
          await loadWebhooks();
        } catch (error) {
          toast({
            title: "Erreur",
            description: "Impossible de modifier le statut",
            variant: "destructive"
          });
        }
      }
    }
  ];

  return (
    <Layout title="Webhooks">
      <div className="space-y-6">
        {/* Header */}
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Webhooks</h1>
            <p className="text-muted-foreground mt-1">
              Intégrez SendWave Pro avec vos applications tierces
            </p>
          </div>
          <Button>
            <Plus className="w-4 h-4 mr-2" />
            Nouveau Webhook
          </Button>
        </div>

        {/* Info Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Webhooks Actifs</CardTitle>
              <Webhook className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {webhooks.filter(w => w.is_active).length}
              </div>
              <p className="text-xs text-muted-foreground">
                sur {webhooks.length} total
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Livraisons Réussies</CardTitle>
              <Activity className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-600">
                {webhooks.reduce((sum, w) => sum + w.success_count, 0)}
              </div>
              <p className="text-xs text-muted-foreground">
                au total
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Échecs</CardTitle>
              <AlertCircle className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-red-600">
                {webhooks.reduce((sum, w) => sum + w.failure_count, 0)}
              </div>
              <p className="text-xs text-muted-foreground">
                au total
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Events Info */}
        <Card>
          <CardHeader>
            <CardTitle>Événements Disponibles</CardTitle>
            <CardDescription>
              12 types d'événements pour déclencher vos webhooks
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
              {[
                'message.sent',
                'message.delivered',
                'message.failed',
                'campaign.started',
                'campaign.completed',
                'campaign.failed',
                'contact.created',
                'contact.updated',
                'contact.deleted',
                'sub_account.created',
                'sub_account.suspended',
                'blacklist.added'
              ].map((event) => (
                <div
                  key={event}
                  className="px-3 py-2 bg-secondary rounded-lg text-sm font-medium text-center"
                >
                  {event}
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Webhooks Table */}
        <Card>
          <CardHeader>
            <CardTitle>Vos Webhooks</CardTitle>
            <CardDescription>
              Gérez vos endpoints de webhooks et surveillez leur statut
            </CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex items-center justify-center h-32">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
              </div>
            ) : webhooks.length > 0 ? (
              <DataTable
                data={webhooks}
                columns={columns}
                actions={actions}
                searchPlaceholder="Rechercher un webhook..."
              />
            ) : (
              <div className="text-center py-12">
                <Webhook className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                <h3 className="text-lg font-medium mb-2">Aucun webhook configuré</h3>
                <p className="text-muted-foreground mb-4">
                  Commencez par créer votre premier webhook pour recevoir des événements
                </p>
                <Button>
                  <Plus className="w-4 h-4 mr-2" />
                  Créer un Webhook
                </Button>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Documentation */}
        <Card>
          <CardHeader>
            <CardTitle>Documentation</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3 text-sm">
              <div>
                <h4 className="font-semibold mb-1 flex items-center">
                  <Shield className="h-4 w-4 mr-2" />
                  Sécurité
                </h4>
                <p className="text-muted-foreground">
                  Tous les webhooks incluent une signature HMAC-SHA256 dans le header <code className="px-1 py-0.5 bg-secondary rounded">X-Webhook-Signature</code>
                </p>
              </div>
              <div>
                <h4 className="font-semibold mb-1 flex items-center">
                  <RefreshCw className="h-4 w-4 mr-2" />
                  Retry Logic
                </h4>
                <p className="text-muted-foreground">
                  Les webhooks échoués sont automatiquement retry jusqu'à 3 fois avec un délai exponentiel (1s, 2s, 4s)
                </p>
              </div>
              <div>
                <h4 className="font-semibold mb-1 flex items-center">
                  <Clock className="h-4 w-4 mr-2" />
                  Timeout
                </h4>
                <p className="text-muted-foreground">
                  Le timeout par défaut est de 30 secondes, configurable entre 5 et 120 secondes
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </Layout>
  );
}
