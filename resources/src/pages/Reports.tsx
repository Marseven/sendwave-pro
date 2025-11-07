import { useState, useEffect } from "react";
import { Layout } from "@/components/layout/Layout";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, LineChart, Line, Legend } from 'recharts';
import { Download, TrendingUp, Users, MessageSquare, Target, FileText, Calendar } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import analyticsService, { ChartData, ComprehensiveReport } from "@/services/analyticsService";

export default function Reports() {
  const { toast } = useToast();
  const [chartData, setChartData] = useState<ChartData | null>(null);
  const [period, setPeriod] = useState<string>('last_7_days');
  const [loading, setLoading] = useState(true);
  const [startDate, setStartDate] = useState<string>(
    new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
  );
  const [endDate, setEndDate] = useState<string>(
    new Date().toISOString().split('T')[0]
  );

  useEffect(() => {
    loadChartData();
  }, [period]);

  const loadChartData = async () => {
    try {
      setLoading(true);
      const data = await analyticsService.getChartData(period);
      setChartData(data);
    } catch (error) {
      console.error('Failed to load chart data:', error);
      toast({
        title: "Erreur",
        description: "Impossible de charger les données du graphique",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const handleExportPdf = async () => {
    try {
      toast({
        title: "Export en cours",
        description: "Génération du rapport PDF...",
      });
      const blob = await analyticsService.exportPdf(startDate, endDate);
      analyticsService.downloadFile(blob, `rapport_analytics_${startDate}_${endDate}.pdf`);
      toast({
        title: "Export réussi",
        description: "Le rapport PDF a été téléchargé",
      });
    } catch (error) {
      console.error('Export PDF failed:', error);
      toast({
        title: "Erreur d'export",
        description: "Impossible de générer le rapport PDF",
        variant: "destructive"
      });
    }
  };

  const handleExportExcel = async () => {
    try {
      toast({
        title: "Export en cours",
        description: "Génération du rapport Excel...",
      });
      const blob = await analyticsService.exportExcel(startDate, endDate);
      analyticsService.downloadFile(blob, `rapport_analytics_${startDate}_${endDate}.xlsx`);
      toast({
        title: "Export réussi",
        description: "Le rapport Excel a été téléchargé",
      });
    } catch (error) {
      console.error('Export Excel failed:', error);
      toast({
        title: "Erreur d'export",
        description: "Impossible de générer le rapport Excel",
        variant: "destructive"
      });
    }
  };

  const handleExportCsv = async () => {
    try {
      toast({
        title: "Export en cours",
        description: "Génération du rapport CSV...",
      });
      const blob = await analyticsService.exportCsv(startDate, endDate);
      analyticsService.downloadFile(blob, `rapport_analytics_${startDate}_${endDate}.csv`);
      toast({
        title: "Export réussi",
        description: "Le rapport CSV a été téléchargé",
      });
    } catch (error) {
      console.error('Export CSV failed:', error);
      toast({
        title: "Erreur d'export",
        description: "Impossible de générer le rapport CSV",
        variant: "destructive"
      });
    }
  };

  const monthlyData = chartData ? chartData.labels.map((label, index) => ({
    month: label,
    sent: chartData.datasets[0].data[index],
    delivered: chartData.datasets[1].data[index],
    failed: chartData.datasets[2].data[index]
  })) : [];
  return (
    <Layout title="Analytics & Rapports">
      <div className="space-y-6">
        {/* Header with Period Selector */}
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Analytics & Rapports</h1>
            <p className="text-muted-foreground mt-1">Analyse détaillée de vos campagnes SMS</p>
          </div>
          <select
            value={period}
            onChange={(e) => setPeriod(e.target.value)}
            className="px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="week">Cette semaine</option>
            <option value="month">Ce mois</option>
            <option value="last_7_days">7 derniers jours</option>
            <option value="last_30_days">30 derniers jours</option>
            <option value="year">Cette année</option>
          </select>
        </div>

        {/* Chart Section */}
        <Card>
          <CardHeader>
            <CardTitle>Évolution des SMS</CardTitle>
            <CardDescription>
              Suivi quotidien des SMS envoyés, délivrés et échoués
            </CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex items-center justify-center h-[300px]">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
              </div>
            ) : monthlyData.length > 0 ? (
              <ResponsiveContainer width="100%" height={350}>
                <LineChart data={monthlyData}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="month" />
                  <YAxis />
                  <Tooltip />
                  <Legend />
                  <Line type="monotone" dataKey="sent" stroke="#3B82F6" name="Envoyés" strokeWidth={2} />
                  <Line type="monotone" dataKey="delivered" stroke="#10B981" name="Délivrés" strokeWidth={2} />
                  <Line type="monotone" dataKey="failed" stroke="#EF4444" name="Échoués" strokeWidth={2} />
                </LineChart>
              </ResponsiveContainer>
            ) : (
              <div className="flex items-center justify-center h-[300px] text-muted-foreground">
                Aucune donnée disponible pour cette période
              </div>
            )}
          </CardContent>
        </Card>

        {/* Export Section */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Exporter les Rapports</CardTitle>
                <CardDescription>
                  Téléchargez vos rapports d'analytics dans différents formats
                </CardDescription>
              </div>
              <FileText className="h-8 w-8 text-muted-foreground" />
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-2">Date de début</label>
                  <input
                    type="date"
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    className="w-full px-3 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-2">Date de fin</label>
                  <input
                    type="date"
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                    className="w-full px-3 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
              </div>
              <div className="flex flex-wrap gap-4 pt-4">
                <Button variant="outline" onClick={handleExportPdf}>
                  <Download className="w-4 h-4 mr-2" />
                  Exporter en PDF
                </Button>
                <Button variant="outline" onClick={handleExportExcel}>
                  <Download className="w-4 h-4 mr-2" />
                  Exporter en Excel
                </Button>
                <Button variant="outline" onClick={handleExportCsv}>
                  <Download className="w-4 h-4 mr-2" />
                  Exporter en CSV
                </Button>
              </div>
              <p className="text-sm text-muted-foreground mt-2">
                Les exports incluent: résumé général, tendances, répartition par opérateur, top campagnes, et détail quotidien.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </Layout>
  );
}