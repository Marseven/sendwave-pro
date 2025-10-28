import { Layout } from "@/components/layout/Layout";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Calendar as CalendarIcon, Clock, Plus } from "lucide-react";

const upcomingCampaigns = [
  {
    id: '1',
    name: 'Promotion Black Friday',
    date: '2024-11-29',
    time: '09:00',
    status: 'Planifié',
    recipients: 15000
  },
  {
    id: '2',
    name: 'Newsletter Mensuelle',
    date: '2024-12-01',
    time: '14:00',
    status: 'Planifié',
    recipients: 8500
  },
  {
    id: '3',
    name: 'Rappel Fin d\'Année',
    date: '2024-12-15',
    time: '16:30',
    status: 'Planifié',
    recipients: 12000
  }
];

export default function Calendar() {
  return (
    <Layout title="Calendrier">
      <div className="space-y-6">
        {/* Quick Actions */}
        <div className="flex gap-4">
          <Button>
            <Plus className="w-4 h-4 mr-2" />
            Planifier une Campagne
          </Button>
          <Button variant="outline">
            <CalendarIcon className="w-4 h-4 mr-2" />
            Vue Calendrier
          </Button>
        </div>

        {/* Upcoming Campaigns */}
        <Card>
          <CardHeader>
            <CardTitle>Campagnes à Venir</CardTitle>
            <CardDescription>
              Vos prochaines campagnes programmées
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {upcomingCampaigns.map((campaign) => (
                <div key={campaign.id} className="flex items-center justify-between p-4 border rounded-lg">
                  <div className="flex items-center gap-4">
                    <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                      <Clock className="w-6 h-6 text-primary" />
                    </div>
                    <div>
                      <h3 className="font-medium">{campaign.name}</h3>
                      <p className="text-sm text-muted-foreground">
                        {campaign.date} à {campaign.time} • {campaign.recipients.toLocaleString()} destinataires
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant="outline">{campaign.status}</Badge>
                    <Button variant="ghost" size="sm">
                      Modifier
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Calendar View Placeholder */}
        <Card>
          <CardHeader>
            <CardTitle>Vue Calendrier</CardTitle>
            <CardDescription>
              Visualisez vos campagnes sur un calendrier mensuel
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="h-96 bg-muted rounded-lg flex items-center justify-center">
              <div className="text-center">
                <CalendarIcon className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                <p className="text-muted-foreground">Vue calendrier en développement</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </Layout>
  );
}