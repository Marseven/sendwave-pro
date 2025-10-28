import { Layout } from "@/components/layout/Layout";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
import { Download, TrendingUp, Users, MessageSquare, Target } from "lucide-react";

const monthlyData = [
  { month: 'Jan', sent: 45000, delivered: 43500 },
  { month: 'Fév', sent: 52000, delivered: 50200 },
  { month: 'Mar', sent: 48000, delivered: 46800 },
  { month: 'Avr', sent: 61000, delivered: 59800 },
  { month: 'Mai', sent: 55000, delivered: 53900 },
  { month: 'Jun', sent: 67000, delivered: 65500 }
];

const statusData = [
  { name: 'Livré', value: 92, color: '#10B981' },
  { name: 'En cours', value: 5, color: '#F59E0B' },
  { name: 'Échec', value: 3, color: '#EF4444' }
];

export default function Reports() {
  return (
    <Layout title="Rapports">
      <div className="space-y-6">
        {/* Key Metrics */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Messages Envoyés</CardTitle>
              <MessageSquare className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">328,000</div>
              <p className="text-xs text-muted-foreground">
                <span className="text-success">+12%</span> ce mois
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Taux de Livraison</CardTitle>
              <Target className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">97.8%</div>
              <p className="text-xs text-muted-foreground">
                <span className="text-success">+0.3%</span> ce mois
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Taux de Clic</CardTitle>
              <TrendingUp className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">4.2%</div>
              <p className="text-xs text-muted-foreground">
                <span className="text-success">+0.8%</span> ce mois
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Contacts Actifs</CardTitle>
              <Users className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">2,847</div>
              <p className="text-xs text-muted-foreground">
                <span className="text-success">+127</span> ce mois
              </p>
            </CardContent>
          </Card>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Monthly Evolution Chart */}
          <Card>
            <CardHeader>
              <CardTitle>Évolution Mensuelle</CardTitle>
              <CardDescription>
                Messages envoyés et livrés par mois
              </CardDescription>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={300}>
                <BarChart data={monthlyData}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="month" />
                  <YAxis />
                  <Tooltip />
                  <Bar dataKey="sent" fill="#3B82F6" name="Envoyés" />
                  <Bar dataKey="delivered" fill="#10B981" name="Livrés" />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>

          {/* Status Distribution */}
          <Card>
            <CardHeader>
              <CardTitle>Répartition par Statut</CardTitle>
              <CardDescription>
                Distribution des statuts de livraison
              </CardDescription>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={300}>
                <PieChart>
                  <Pie
                    data={statusData}
                    cx="50%"
                    cy="50%"
                    outerRadius={80}
                    dataKey="value"
                    label={({ name, value }) => `${name}: ${value}%`}
                  >
                    {statusData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </div>

        {/* Export Section */}
        <Card>
          <CardHeader>
            <CardTitle>Exporter les Rapports</CardTitle>
            <CardDescription>
              Téléchargez vos rapports dans différents formats
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex gap-4">
              <Button variant="outline">
                <Download className="w-4 h-4 mr-2" />
                Exporter en PDF
              </Button>
              <Button variant="outline">
                <Download className="w-4 h-4 mr-2" />
                Exporter en Excel
              </Button>
              <Button variant="outline">
                <Download className="w-4 h-4 mr-2" />
                Exporter en CSV
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </Layout>
  );
}