import { useState, useEffect } from "react";
import { Layout } from "@/components/layout/Layout";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { templateService, Template } from "@/services/templateService";
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
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export default function Templates() {
  const { toast } = useToast();
  const [templates, setTemplates] = useState<Template[]>([]);
  const [loading, setLoading] = useState(true);
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  useEffect(() => {
    loadTemplates();
  }, []);

  const loadTemplates = async () => {
    try {
      setLoading(true);
      const data = await templateService.getAll();
      setTemplates(data);
    } catch (error) {
      console.error('Failed to load templates:', error);
      toast({
        title: "Erreur",
        description: "Impossible de charger les modèles",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const [editingTemplate, setEditingTemplate] = useState<Template | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    message: '',
    category: ''
  });

  const resetForm = () => {
    setFormData({ name: '', message: '', category: '' });
    setEditingTemplate(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      if (editingTemplate) {
        await templateService.update(editingTemplate.id, formData);
        toast({
          title: "Modèle modifié",
          description: `${formData.name} a été mis à jour avec succès.`,
        });
      } else {
        await templateService.create(formData);
        toast({
          title: "Modèle créé",
          description: `${formData.name} a été créé avec succès.`,
        });
      }

      await loadTemplates();
      resetForm();
      setIsDialogOpen(false);
    } catch (error) {
      console.error('Failed to save template:', error);
      toast({
        title: "Erreur",
        description: "Impossible de sauvegarder le modèle",
        variant: "destructive"
      });
    }
  };

  const handleEdit = (template: Template) => {
    setEditingTemplate(template);
    setFormData({
      name: template.name,
      message: template.message,
      category: template.category
    });
    setIsDialogOpen(true);
  };

  const handleDelete = async (template: Template) => {
    try {
      await templateService.delete(template.id);
      toast({
        title: "Modèle supprimé",
        description: `${template.name} a été supprimé avec succès.`,
        variant: "destructive",
      });
      await loadTemplates();
    } catch (error) {
      console.error('Failed to delete template:', error);
      toast({
        title: "Erreur",
        description: "Impossible de supprimer le modèle",
        variant: "destructive"
      });
    }
  };

  const columns: Column<Template>[] = [
    {
      key: 'name',
      header: 'Nom du Modèle',
    },
    {
      key: 'message',
      header: 'Extrait de Contenu',
      render: (value) => (
        <div className="max-w-md truncate">
          {String(value).substring(0, 80)}...
        </div>
      )
    },
    {
      key: 'category',
      header: 'Catégorie',
    },
    {
      key: 'updated_at',
      header: 'Dernière Modification',
      render: (value) => value ? new Date(value as string).toLocaleDateString('fr-FR') : '-'
    }
  ];

  const actions: Action<Template>[] = [
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
    <Layout title="Modèles de Message">
      <div className="space-y-6">
        {/* Action Button */}
        <div className="flex justify-start">
          <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
            <DialogTrigger asChild>
              <Button className="bg-primary hover:bg-primary/90" onClick={resetForm}>
                <Plus className="w-4 h-4 mr-2" />
                Créer un Nouveau Modèle
              </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
              <DialogHeader>
                <DialogTitle>
                  {editingTemplate ? 'Modifier le modèle' : 'Créer un nouveau modèle'}
                </DialogTitle>
                <DialogDescription>
                  {editingTemplate 
                    ? 'Modifiez le contenu du modèle ci-dessous.'
                    : 'Créez un nouveau modèle de message réutilisable.'
                  }
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Nom du modèle</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="category">Catégorie</Label>
                  <Select 
                    value={formData.category} 
                    onValueChange={(value) => setFormData({ ...formData, category: value })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner une catégorie" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Marketing">Marketing</SelectItem>
                      <SelectItem value="Promo">Promo</SelectItem>
                      <SelectItem value="Notification">Notification</SelectItem>
                      <SelectItem value="Information">Information</SelectItem>
                      <SelectItem value="Assurance">Assurance</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="message">Contenu du message</Label>
                  <Textarea
                    id="message"
                    value={formData.message}
                    onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                    placeholder="Entrez le contenu de votre message..."
                    rows={6}
                    required
                  />
                  <p className="text-sm text-muted-foreground">
                    Caractères: {formData.message.length}/160
                  </p>
                </div>
                
                <div className="flex gap-2">
                  <Button type="submit" className="flex-1">
                    {editingTemplate ? 'Modifier' : 'Créer'}
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

        {/* Templates Table */}
        <div className="bg-card rounded-lg border border-border p-6">
          {loading ? (
            <div className="flex items-center justify-center h-64">
              <div className="text-center">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
                <p className="text-muted-foreground">Chargement des modèles...</p>
              </div>
            </div>
          ) : (
            <DataTable
              data={templates}
              columns={columns}
              actions={actions}
              searchPlaceholder="Rechercher des modèles..."
            />
          )}
        </div>
      </div>
    </Layout>
  );
}