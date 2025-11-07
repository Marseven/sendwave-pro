import { useState, useEffect } from "react";
import { Layout } from "@/components/layout/Layout";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useAppStore } from "@/lib/store";
import { Plus, User } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { subAccountService, SubAccount } from "@/services/subAccountService";
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export default function Accounts() {
  const { user } = useAppStore();
  const { toast } = useToast();
  const [subAccounts, setSubAccounts] = useState<SubAccount[]>([]);
  const [loading, setLoading] = useState(true);
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  useEffect(() => {
    loadSubAccounts();
  }, []);

  const loadSubAccounts = async () => {
    try {
      setLoading(true);
      const data = await subAccountService.getAll();
      setSubAccounts(data);
    } catch (error) {
      console.error('Failed to load sub-accounts:', error);
      toast({
        title: "Erreur",
        description: "Impossible de charger les sous-comptes",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const [editingAccount, setEditingAccount] = useState<SubAccount | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    status: 'active' as 'active' | 'inactive'
  });

  const resetForm = () => {
    setFormData({ name: '', status: 'active' });
    setEditingAccount(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      if (editingAccount) {
        await subAccountService.update(editingAccount.id, formData);
        toast({
          title: "Sous-compte modifié",
          description: `${formData.name} a été mis à jour avec succès.`,
        });
      } else {
        await subAccountService.create(formData);
        toast({
          title: "Sous-compte créé",
          description: `${formData.name} a été créé avec succès.`,
        });
      }

      await loadSubAccounts();
      resetForm();
      setIsDialogOpen(false);
    } catch (error) {
      console.error('Failed to save sub-account:', error);
      toast({
        title: "Erreur",
        description: "Impossible de sauvegarder le sous-compte",
        variant: "destructive"
      });
    }
  };

  const handleEdit = (account: SubAccount) => {
    setEditingAccount(account);
    setFormData({
      name: account.name,
      status: account.status
    });
    setIsDialogOpen(true);
  };

  const handleDelete = async (account: SubAccount) => {
    try {
      await subAccountService.delete(account.id);
      toast({
        title: "Sous-compte supprimé",
        description: `${account.name} a été supprimé avec succès.`,
        variant: "destructive",
      });
      await loadSubAccounts();
    } catch (error) {
      console.error('Failed to delete sub-account:', error);
      toast({
        title: "Erreur",
        description: "Impossible de supprimer le sous-compte",
        variant: "destructive"
      });
    }
  };

  const columns: Column<SubAccount>[] = [
    {
      key: 'name',
      header: 'Nom du Sous-compte',
    },
    {
      key: 'status',
      header: 'Statut',
      render: (value) => (
        <span className={`px-2 py-1 rounded-full text-xs font-medium ${
          value === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
        }`}>
          {value === 'active' ? 'Actif' : 'Inactif'}
        </span>
      )
    },
    {
      key: 'credits_remaining',
      header: 'Crédits restants',
      render: (value) => (value as number).toLocaleString('fr-FR')
    },
    {
      key: 'delivery_rate',
      header: 'Taux de livraison',
      render: (value) => `${(value as number).toFixed(1)}%`
    },
    {
      key: 'created_at',
      header: 'Date de création',
      render: (value) => value ? new Date(value as string).toLocaleDateString('fr-FR') : '-'
    }
  ];

  const actions: Action<SubAccount>[] = [
    {
      label: 'Modifier',
      onClick: handleEdit
    },
    {
      label: 'Supprimer',
      onClick: handleDelete,
      variant: 'destructive'
    }
  ];

  return (
    <Layout title="Comptes & Sous-comptes">
      <div className="space-y-6">
        <Tabs defaultValue="subaccounts" className="w-full">
          <TabsList>
            <TabsTrigger value="profile">Mon Compte</TabsTrigger>
            <TabsTrigger value="subaccounts">Sous-comptes</TabsTrigger>
          </TabsList>
          
          <TabsContent value="profile" className="space-y-6">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Profile Information */}
              <Card>
                <CardHeader>
                  <CardTitle>Informations de Profil</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="flex items-center gap-4">
                    <Avatar className="w-16 h-16">
                      <AvatarImage src={user?.avatar} />
                      <AvatarFallback>
                        <User className="w-8 h-8" />
                      </AvatarFallback>
                    </Avatar>
                    <div>
                      <h3 className="text-lg font-semibold">{user?.name}</h3>
                      <p className="text-muted-foreground">{user?.email}</p>
                      <p className="text-sm text-muted-foreground">Rôle: {user?.role}</p>
                    </div>
                  </div>
                  <Button>Modifier le Profil</Button>
                </CardContent>
              </Card>

              {/* Security Settings */}
              <Card>
                <CardHeader>
                  <CardTitle>Paramètres de Sécurité</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="font-medium">Authentification à Deux Facteurs</p>
                      <p className="text-sm text-muted-foreground">Sécurisez votre compte</p>
                    </div>
                    <Button variant="outline">Activer</Button>
                  </div>
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="font-medium">Changer le Mot de Passe</p>
                      <p className="text-sm text-muted-foreground">Dernière modification il y a 30 jours</p>
                    </div>
                    <Button variant="outline">Réinitialiser</Button>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>
          
          <TabsContent value="subaccounts" className="space-y-6">
            {/* Action Button */}
            <div className="flex justify-start">
              <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogTrigger asChild>
                  <Button className="bg-primary hover:bg-primary/90" onClick={resetForm}>
                    <Plus className="w-4 h-4 mr-2" />
                    Créer un Nouveau Sous-compte
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>
                      {editingAccount ? 'Modifier le sous-compte' : 'Créer un nouveau sous-compte'}
                    </DialogTitle>
                    <DialogDescription>
                      {editingAccount 
                        ? 'Modifiez les informations du sous-compte ci-dessous.'
                        : 'Créez un nouveau sous-compte pour votre équipe.'
                      }
                    </DialogDescription>
                  </DialogHeader>
                  <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                      <Label htmlFor="name">Nom du sous-compte</Label>
                      <Input
                        id="name"
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        required
                      />
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="status">Statut</Label>
                      <Select
                        value={formData.status}
                        onValueChange={(value: 'active' | 'inactive') =>
                          setFormData({ ...formData, status: value })
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="active">Actif</SelectItem>
                          <SelectItem value="inactive">Inactif</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    
                    <div className="flex gap-2">
                      <Button type="submit" className="flex-1">
                        {editingAccount ? 'Modifier' : 'Créer'}
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

            {/* Sub Accounts Table */}
            <div className="bg-card rounded-lg border border-border p-6">
              {loading ? (
                <div className="flex items-center justify-center h-64">
                  <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
                    <p className="text-muted-foreground">Chargement des sous-comptes...</p>
                  </div>
                </div>
              ) : (
                <DataTable
                  data={subAccounts}
                  columns={columns}
                  actions={actions}
                  searchPlaceholder="Rechercher des sous-comptes..."
                />
              )}
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </Layout>
  );
}