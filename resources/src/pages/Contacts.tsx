import { useState, useEffect } from "react";
import { Layout } from "@/components/layout/Layout";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { Button } from "@/components/ui/button";
import { Plus, Upload } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { contactService, Contact } from "@/services/contactService";
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

export default function Contacts() {
  const { toast } = useToast();
  const [contacts, setContacts] = useState<Contact[]>([]);
  const [loading, setLoading] = useState(true);
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  useEffect(() => {
    loadContacts();
  }, []);

  const loadContacts = async () => {
    try {
      setLoading(true);
      const data = await contactService.getAll();
      setContacts(data);
    } catch (error) {
      console.error('Failed to load contacts:', error);
      toast({
        title: "Erreur",
        description: "Impossible de charger les contacts",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };
  const [editingContact, setEditingContact] = useState<Contact | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    email: '',
    group: '',
    status: 'active' as 'active' | 'inactive'
  });

  const resetForm = () => {
    setFormData({ name: '', phone: '', email: '', group: '', status: 'active' });
    setEditingContact(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      if (editingContact) {
        await contactService.update(editingContact.id, formData);
        toast({
          title: "Contact modifié",
          description: `${formData.name} a été mis à jour avec succès.`,
        });
      } else {
        await contactService.create(formData);
        toast({
          title: "Contact ajouté",
          description: `${formData.name} a été ajouté avec succès.`,
        });
      }

      await loadContacts();
      resetForm();
      setIsDialogOpen(false);
    } catch (error) {
      console.error('Failed to save contact:', error);
      toast({
        title: "Erreur",
        description: "Impossible de sauvegarder le contact",
        variant: "destructive"
      });
    }
  };

  const handleEdit = (contact: Contact) => {
    setEditingContact(contact);
    setFormData({
      name: contact.name,
      phone: contact.phone,
      email: contact.email || '',
      group: contact.custom_fields?.group || '',
      status: contact.status
    });
    setIsDialogOpen(true);
  };

  const handleDelete = async (contact: Contact) => {
    try {
      await contactService.delete(contact.id);
      toast({
        title: "Contact supprimé",
        description: `${contact.name} a été supprimé avec succès.`,
        variant: "destructive",
      });
      await loadContacts();
    } catch (error) {
      console.error('Failed to delete contact:', error);
      toast({
        title: "Erreur",
        description: "Impossible de supprimer le contact",
        variant: "destructive"
      });
    }
  };

  const columns: Column<Contact>[] = [
    {
      key: 'name',
      header: 'Nom',
    },
    {
      key: 'phone',
      header: 'Numéro de Téléphone',
    },
    {
      key: 'email',
      header: 'Email',
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
      key: 'created_at',
      header: 'Date de création',
      render: (value) => value ? new Date(value as string).toLocaleDateString('fr-FR') : '-'
    }
  ];

  const actions: Action<Contact>[] = [
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
    <Layout title="Gestion des Contacts">
      <div className="space-y-6">
        {/* Action Buttons */}
        <div className="flex gap-4">
          <Button className="bg-primary hover:bg-primary/90">
            <Upload className="w-4 h-4 mr-2" />
            Importer des Contacts
          </Button>
          
          <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
            <DialogTrigger asChild>
              <Button variant="outline" onClick={resetForm}>
                <Plus className="w-4 h-4 mr-2" />
                Ajouter un Nouveau Contact
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>
                  {editingContact ? 'Modifier le contact' : 'Ajouter un nouveau contact'}
                </DialogTitle>
                <DialogDescription>
                  {editingContact 
                    ? 'Modifiez les informations du contact ci-dessous.'
                    : 'Remplissez les informations pour ajouter un nouveau contact.'
                  }
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Nom</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="phone">Numéro de téléphone</Label>
                  <Input
                    id="phone"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    placeholder="+241 XX XX XX XX"
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="email">Email (optionnel)</Label>
                  <Input
                    id="email"
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    placeholder="email@example.com"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="group">Groupe (optionnel)</Label>
                  <Select
                    value={formData.group}
                    onValueChange={(value) => setFormData({ ...formData, group: value })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner un groupe" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Clients">Clients</SelectItem>
                      <SelectItem value="Prospects">Prospects</SelectItem>
                      <SelectItem value="Partenaires">Partenaires</SelectItem>
                    </SelectContent>
                  </Select>
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
                    {editingContact ? 'Modifier' : 'Ajouter'}
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

        {/* Contacts Table */}
        <div className="bg-card rounded-lg border border-border p-6">
          {loading ? (
            <div className="flex items-center justify-center h-64">
              <div className="text-center">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
                <p className="text-muted-foreground">Chargement des contacts...</p>
              </div>
            </div>
          ) : (
            <DataTable
              data={contacts}
              columns={columns}
              actions={actions}
              searchPlaceholder="Rechercher des contacts..."
            />
          )}
        </div>
      </div>
    </Layout>
  );
}