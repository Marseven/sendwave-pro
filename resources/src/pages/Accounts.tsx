import { useState, useEffect } from "react";
import { Layout } from "@/components/layout/Layout";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useAppStore, SubAccount } from "@/lib/store";
import { Plus, User } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
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
  const { user, subAccounts, loadSubAccounts, addSubAccount, updateSubAccount, deleteSubAccount } = useAppStore();
  const { toast } = useToast();
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  useEffect(() => {
    loadSubAccounts();
  }, [loadSubAccounts]);
  const [editingAccount, setEditingAccount] = useState<SubAccount | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    status: 'Actif' as 'Actif' | 'Inactif'
  });

  const resetForm = () => {
    setFormData({ name: '', email: '', status: 'Actif' });
    setEditingAccount(null);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (editingAccount) {
      updateSubAccount(editingAccount.id, {
        ...formData,
        last_connection: new Date().toISOString().split('T')[0]
      });
      toast({
        title: "Sous-compte modifié",
        description: `${formData.name} a été mis à jour avec succès.`,
      });
    } else {
      addSubAccount({
        ...formData,
        last_connection: new Date().toISOString().split('T')[0]
      });
      toast({
        title: "Sous-compte créé",
        description: `${formData.name} a été créé avec succès.`,
      });
    }
    
    resetForm();
    setIsDialogOpen(false);
  };

  const handleEdit = (account: SubAccount) => {
    setEditingAccount(account);
    setFormData({
      name: account.name,
      email: account.email,
      status: account.status
    });
    setIsDialogOpen(true);
  };

  const handleDelete = (account: SubAccount) => {
    deleteSubAccount(account.id);
    toast({
      title: "Sous-compte supprimé",
      description: `${account.name} a été supprimé avec succès.`,
      variant: "destructive",
    });
  };

  const columns: Column<SubAccount>[] = [
    {
      key: 'name',
      header: 'Nom du Sous-compte',
    },
    {
      key: 'email',
      header: 'Email',
    },
    {
      key: 'status',
      header: 'Statut',
    },
    {
      key: 'last_connection',
      header: 'Dernière Connexion',
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
                      <Label htmlFor="email">Email</Label>
                      <Input
                        id="email"
                        type="email"
                        value={formData.email}
                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                        required
                      />
                    </div>
                    
                    <div className="space-y-2">
                      <Label htmlFor="status">Statut</Label>
                      <Select 
                        value={formData.status} 
                        onValueChange={(value: 'Actif' | 'Inactif') => 
                          setFormData({ ...formData, status: value })
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="Actif">Actif</SelectItem>
                          <SelectItem value="Inactif">Inactif</SelectItem>
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
              <DataTable
                data={subAccounts}
                columns={columns}
                actions={actions}
                searchPlaceholder="Rechercher des sous-comptes..."
              />
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </Layout>
  );
}