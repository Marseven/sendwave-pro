import { useState, useEffect } from "react";
import { Layout } from "@/components/layout/Layout";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { Button } from "@/components/ui/button";
import { useAppStore, Contact } from "@/lib/store";
import { Plus, Upload } from "lucide-react";
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export default function Contacts() {
  const { contacts, loadContacts, addContact, updateContact, deleteContact } = useAppStore();
  const { toast } = useToast();
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  useEffect(() => {
    loadContacts();
  }, [loadContacts]);
  const [editingContact, setEditingContact] = useState<Contact | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    group: '',
    status: 'Actif' as 'Actif' | 'En Attente' | 'Inactif'
  });

  const resetForm = () => {
    setFormData({ name: '', phone: '', group: '', status: 'Actif' });
    setEditingContact(null);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (editingContact) {
      updateContact(editingContact.id, {
        ...formData,
        last_connection: new Date().toISOString().split('T')[0]
      });
      toast({
        title: "Contact modifié",
        description: `${formData.name} a été mis à jour avec succès.`,
      });
    } else {
      addContact({
        ...formData,
        last_connection: new Date().toISOString().split('T')[0]
      });
      toast({
        title: "Contact ajouté",
        description: `${formData.name} a été ajouté avec succès.`,
      });
    }
    
    resetForm();
    setIsDialogOpen(false);
  };

  const handleEdit = (contact: Contact) => {
    setEditingContact(contact);
    setFormData({
      name: contact.name,
      phone: contact.phone,
      group: contact.group,
      status: contact.status
    });
    setIsDialogOpen(true);
  };

  const handleDelete = (contact: Contact) => {
    deleteContact(contact.id);
    toast({
      title: "Contact supprimé",
      description: `${contact.name} a été supprimé avec succès.`,
      variant: "destructive",
    });
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
      key: 'group',
      header: 'Groupe',
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
                  <Label htmlFor="group">Groupe</Label>
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
                    onValueChange={(value: 'Actif' | 'En Attente' | 'Inactif') => 
                      setFormData({ ...formData, status: value })
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Actif">Actif</SelectItem>
                      <SelectItem value="En Attente">En Attente</SelectItem>
                      <SelectItem value="Inactif">Inactif</SelectItem>
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
          <DataTable
            data={contacts}
            columns={columns}
            actions={actions}
            searchPlaceholder="Rechercher des contacts..."
          />
        </div>
      </div>
    </Layout>
  );
}