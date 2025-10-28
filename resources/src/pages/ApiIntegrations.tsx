import { useState } from "react";
import { Layout } from "@/components/layout/Layout";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useAppStore, ApiKey } from "@/lib/store";
import { Plus, Copy, ExternalLink } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export default function ApiIntegrations() {
  const { apiKeys, addApiKey, revokeApiKey } = useAppStore();
  const { toast } = useToast();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [newKeyName, setNewKeyName] = useState('');

  const handleCreateApiKey = (e: React.FormEvent) => {
    e.preventDefault();
    
    const newKey = {
      name: newKeyName,
      key: `sk_live_${Math.random().toString(36).substring(2)}${Date.now().toString(36)}`,
      createdAt: new Date().toISOString().split('T')[0],
      lastUsed: 'Jamais'
    };
    
    addApiKey(newKey);
    toast({
      title: "Clé API générée",
      description: `${newKeyName} a été créée avec succès.`,
    });
    
    setNewKeyName('');
    setIsDialogOpen(false);
  };

  const handleRevoke = (apiKey: ApiKey) => {
    revokeApiKey(apiKey.id);
    toast({
      title: "Clé API révoquée",
      description: `${apiKey.name} a été révoquée avec succès.`,
      variant: "destructive",
    });
  };

  const handleCopyKey = (key: string) => {
    navigator.clipboard.writeText(key);
    toast({
      title: "Copié",
      description: "La clé API a été copiée dans le presse-papiers.",
    });
  };

  const columns: Column<ApiKey>[] = [
    {
      key: 'name',
      header: 'Nom de la Clé',
    },
    {
      key: 'key',
      header: 'Clé',
      render: (value) => (
        <div className="flex items-center gap-2">
          <code className="text-sm bg-muted px-2 py-1 rounded">
            {String(value)}
          </code>
          <Button 
            variant="ghost" 
            size="sm"
            onClick={() => handleCopyKey(String(value))}
          >
            <Copy className="w-4 h-4" />
          </Button>
        </div>
      )
    },
    {
      key: 'createdAt',
      header: 'Créée le',
    },
    {
      key: 'lastUsed',
      header: 'Dernière Utilisation',
    }
  ];

  const actions: Action<ApiKey>[] = [
    {
      label: 'Révoquer',
      onClick: handleRevoke,
      variant: 'destructive'
    }
  ];

  return (
    <Layout title="API & Interconnexions">
      <div className="space-y-6">
        {/* API Keys Section */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Clés API</CardTitle>
                <CardDescription>
                  Gérez vos clés API pour intégrer Gestionnaire SMS à vos applications
                </CardDescription>
              </div>
              <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogTrigger asChild>
                  <Button className="bg-primary hover:bg-primary/90">
                    <Plus className="w-4 h-4 mr-2" />
                    Générer une Nouvelle Clé
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>Générer une nouvelle clé API</DialogTitle>
                    <DialogDescription>
                      Créez une nouvelle clé API pour accéder à nos services.
                    </DialogDescription>
                  </DialogHeader>
                  <form onSubmit={handleCreateApiKey} className="space-y-4">
                    <div className="space-y-2">
                      <Label htmlFor="keyName">Nom de la clé</Label>
                      <Input
                        id="keyName"
                        value={newKeyName}
                        onChange={(e) => setNewKeyName(e.target.value)}
                        placeholder="Ex: API Production"
                        required
                      />
                    </div>
                    
                    <div className="flex gap-2">
                      <Button type="submit" className="flex-1">
                        Générer
                      </Button>
                      <Button 
                        type="button" 
                        variant="outline" 
                        onClick={() => setIsDialogOpen(false)}
                      >
                        Annuler
                      </Button>
                    </div>
                  </form>
                </DialogContent>
              </Dialog>
            </div>
          </CardHeader>
          <CardContent>
            <DataTable
              data={apiKeys}
              columns={columns}
              actions={actions}
              searchable={false}
            />
          </CardContent>
        </Card>

        {/* API Documentation Section */}
        <Card>
          <CardHeader>
            <CardTitle>Documentation API</CardTitle>
            <CardDescription>
              Explorez notre documentation API complète pour intégrer Gestionnaire SMS à vos applications. 
              Trouvez des guides détaillés, des références d'endpoints et des exemples de code.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Button variant="outline">
              <ExternalLink className="w-4 h-4 mr-2" />
              Voir la Documentation
            </Button>
          </CardContent>
        </Card>

        {/* Integration Examples */}
        <Card>
          <CardHeader>
            <CardTitle>Exemples d'intégration</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="p-4 border rounded-lg">
                <h4 className="font-medium mb-2">Envoi de SMS via cURL</h4>
                <pre className="text-xs bg-muted p-3 rounded overflow-x-auto">
{`curl -X POST https://api.gestionnaire-sms.com/send \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Content-Type: application/json" \\
  -d '{
    "to": "+24166123456",
    "message": "Votre message ici"
  }'`}
                </pre>
              </div>
              
              <div className="p-4 border rounded-lg">
                <h4 className="font-medium mb-2">JavaScript/Node.js</h4>
                <pre className="text-xs bg-muted p-3 rounded overflow-x-auto">
{`const response = await fetch('/api/send', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    to: '+24166123456',
    message: 'Votre message ici'
  })
});`}
                </pre>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </Layout>
  );
}