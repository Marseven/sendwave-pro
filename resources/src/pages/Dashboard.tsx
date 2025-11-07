import { useEffect, useState } from "react";
import { Layout } from "@/components/layout/Layout";
import { MetricCard } from "@/components/ui/metric-card";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { useAppStore, Campaign } from "@/lib/store";
import { TrendingUp, Users, MessageSquare, BarChart3, DollarSign, Activity } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import analyticsService, { AnalyticsDashboard } from "@/services/analyticsService";

export default function Dashboard() {
  const { campaigns, loadCampaigns } = useAppStore();
  const { toast } = useToast();
  const [analytics, setAnalytics] = useState<AnalyticsDashboard | null>(null);
  const [loading, setLoading] = useState(true);
  const [period, setPeriod] = useState<string>('today');

  useEffect(() => {
    loadCampaigns();
    loadAnalytics();
  }, [loadCampaigns, period]);

  const loadAnalytics = async () => {
    try {
      setLoading(true);
      const { data } = await analyticsService.getDashboard(period);
      setAnalytics(data);
    } catch (error) {
      console.error('Failed to load analytics:', error);
      toast({
        title: "Erreur",
        description: "Impossible de charger les statistiques",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const metrics = analytics ? [
    {
      title: "SMS Envoyés",
      value: analytics.overview.sms_sent.toLocaleString('fr-FR'),
      subtitle: `(${period === 'today' ? "Aujourd'hui" : "Période sélectionnée"})`,
      icon: MessageSquare,
      trend: {
        value: Math.abs(analytics.trends.sms_sent_change),
        label: "vs période précédente",
        isPositive: analytics.trends.sms_sent_change >= 0
      }
    },
    {
      title: "SMS Délivrés",
      value: analytics.overview.sms_delivered.toLocaleString('fr-FR'),
      icon: Activity,
      trend: {
        value: analytics.overview.success_rate,
        label: "taux de succès",
        isPositive: true
      }
    },
    {
      title: "Taux de Succès",
      value: `${analytics.overview.success_rate.toFixed(1)}%`,
      icon: TrendingUp,
      trend: {
        value: Math.abs(analytics.trends.success_rate_change),
        label: "vs période précédente",
        isPositive: analytics.trends.success_rate_change >= 0
      }
    },
    {
      title: "Coût Total",
      value: `${analytics.overview.total_cost.toLocaleString('fr-FR')} FCFA`,
      icon: DollarSign,
      trend: {
        value: Math.abs(analytics.trends.cost_change),
        label: "vs période précédente",
        isPositive: analytics.trends.cost_change <= 0
      }
    },
    {
      title: "Campagnes Exécutées",
      value: analytics.overview.campaigns_executed.toString(),
      icon: BarChart3,
      trend: {
        value: Math.abs(analytics.trends.campaigns_change),
        label: "vs période précédente",
        isPositive: analytics.trends.campaigns_change >= 0
      }
    },
    {
      title: "Contacts Ajoutés",
      value: analytics.overview.contacts_added.toLocaleString('fr-FR'),
      icon: Users,
      trend: {
        value: 0,
        label: "pendant la période",
        isPositive: true
      }
    }
  ] : [];

  const columns: Column<Campaign>[] = [
    {
      key: 'name',
      header: 'Nom de la Campagne',
    },
    {
      key: 'status',
      header: 'Statut',
    },
    {
      key: 'messages_sent',
      header: 'Messages Envoyés',
      render: (value) => value.toLocaleString('fr-FR')
    },
    {
      key: 'delivery_rate',
      header: 'Taux de Livraison',
      render: (value) => `${value}%`
    },
    {
      key: 'ctr',
      header: 'CTR',
      render: (value) => `${value}%`
    }
  ];

  const actions: Action<Campaign>[] = [
    {
      label: 'Voir',
      onClick: (campaign) => {
        toast({
          title: "Campagne sélectionnée",
          description: `Consultation de la campagne "${campaign.name}"`,
        });
      }
    },
    {
      label: 'Modifier',
      onClick: (campaign) => {
        toast({
          title: "Modification",
          description: `Édition de la campagne "${campaign.name}"`,
        });
      }
    }
  ];

  if (loading && !analytics) {
    return (
      <Layout title="Tableau de Bord">
        <div className="flex items-center justify-center h-64">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
            <p className="text-muted-foreground">Chargement des statistiques...</p>
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout title="Tableau de Bord">
      <div className="space-y-6">
        {/* Period Selector */}
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Tableau de Bord</h1>
            <p className="text-muted-foreground mt-1">Vue d'ensemble de vos campagnes SMS</p>
          </div>
          <select
            value={period}
            onChange={(e) => setPeriod(e.target.value)}
            className="px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="today">Aujourd'hui</option>
            <option value="yesterday">Hier</option>
            <option value="week">Cette semaine</option>
            <option value="month">Ce mois</option>
            <option value="last_7_days">7 derniers jours</option>
            <option value="last_30_days">30 derniers jours</option>
            <option value="year">Cette année</option>
          </select>
        </div>

        {/* Metrics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {metrics.map((metric, index) => (
            <MetricCard
              key={index}
              title={metric.title}
              value={metric.value}
              subtitle={metric.subtitle}
              icon={metric.icon}
              trend={metric.trend}
            />
          ))}
        </div>

        {/* Provider Distribution */}
        {analytics && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="bg-card rounded-lg border border-border p-6">
              <h2 className="text-xl font-semibold mb-4">Répartition par Opérateur</h2>
              <div className="space-y-4">
                <div>
                  <div className="flex justify-between items-center mb-2">
                    <span className="font-medium">Airtel</span>
                    <span className="text-sm text-muted-foreground">
                      {analytics.providers.airtel.count.toLocaleString('fr-FR')} SMS ({analytics.providers.airtel.percentage.toFixed(1)}%)
                    </span>
                  </div>
                  <div className="w-full bg-secondary rounded-full h-3">
                    <div
                      className="bg-red-500 h-3 rounded-full transition-all"
                      style={{ width: `${analytics.providers.airtel.percentage}%` }}
                    ></div>
                  </div>
                </div>
                <div>
                  <div className="flex justify-between items-center mb-2">
                    <span className="font-medium">Moov</span>
                    <span className="text-sm text-muted-foreground">
                      {analytics.providers.moov.count.toLocaleString('fr-FR')} SMS ({analytics.providers.moov.percentage.toFixed(1)}%)
                    </span>
                  </div>
                  <div className="w-full bg-secondary rounded-full h-3">
                    <div
                      className="bg-blue-500 h-3 rounded-full transition-all"
                      style={{ width: `${analytics.providers.moov.percentage}%` }}
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            {/* Cost Analysis */}
            <div className="bg-card rounded-lg border border-border p-6">
              <h2 className="text-xl font-semibold mb-4">Analyse des Coûts</h2>
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Coût Airtel</span>
                  <span className="font-semibold">{analytics.cost_analysis.airtel_cost.toLocaleString('fr-FR')} FCFA</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Coût Moov</span>
                  <span className="font-semibold">{analytics.cost_analysis.moov_cost.toLocaleString('fr-FR')} FCFA</span>
                </div>
                <div className="border-t border-border pt-3 mt-3">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Coût Moyen/SMS</span>
                    <span className="font-semibold">{analytics.overview.average_cost_per_sms.toFixed(2)} FCFA</span>
                  </div>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Coût Quotidien Moyen</span>
                  <span className="font-semibold">{analytics.cost_analysis.average_daily_cost.toFixed(2)} FCFA</span>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Top Campaigns */}
        {analytics && analytics.campaigns.length > 0 && (
          <div className="bg-card rounded-lg border border-border p-6">
            <h2 className="text-xl font-semibold mb-4">Top 5 Campagnes</h2>
            <div className="space-y-3">
              {analytics.campaigns.map((campaign, index) => (
                <div key={campaign.id} className="flex items-center justify-between p-3 bg-secondary/50 rounded-lg">
                  <div className="flex items-center space-x-3">
                    <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-semibold">
                      {index + 1}
                    </div>
                    <div>
                      <p className="font-medium">{campaign.name}</p>
                      <p className="text-sm text-muted-foreground">
                        {new Date(campaign.created_at).toLocaleDateString('fr-FR')}
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold">{campaign.messages_sent.toLocaleString('fr-FR')}</p>
                    <p className="text-sm text-muted-foreground">messages envoyés</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Recent Campaigns Table */}
        <div className="bg-card rounded-lg border border-border p-6">
          <h2 className="text-xl font-semibold mb-4">Campagnes Récentes</h2>
          <DataTable
            data={campaigns}
            columns={columns}
            actions={actions}
            searchPlaceholder="Rechercher des campagnes..."
          />
        </div>
      </div>
    </Layout>
  );
}