import { useEffect } from "react";
import { Layout } from "@/components/layout/Layout";
import { MetricCard } from "@/components/ui/metric-card";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { useAppStore, Campaign } from "@/lib/store";
import { TrendingUp, Users, MessageSquare, BarChart3 } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function Dashboard() {
  const { campaigns, loadCampaigns } = useAppStore();
  const { toast } = useToast();

  useEffect(() => {
    loadCampaigns();
  }, [loadCampaigns]);

  const metrics = [
    {
      title: "Campagnes Totales",
      value: "1,250",
      icon: BarChart3,
      trend: { value: 12, label: "depuis le mois dernier", isPositive: true }
    },
    {
      title: "Campagnes Actives",
      value: "85",
      icon: TrendingUp,
      trend: { value: 8, label: "depuis la semaine dernière", isPositive: true }
    },
    {
      title: "Messages Envoyés",
      value: "125,432",
      subtitle: "(Aujourd'hui)",
      icon: MessageSquare,
      trend: { value: 15, label: "vs hier", isPositive: true }
    },
    {
      title: "Taux de Livraison Moyen",
      value: "98.2%",
      icon: Users,
      trend: { value: 2, label: "ce mois", isPositive: true }
    }
  ];

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

  return (
    <Layout title="Tableau de Bord">
      <div className="space-y-6">
        {/* Metrics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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