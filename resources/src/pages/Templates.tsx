import { useState, useEffect } from "react";
import { Layout } from "@/components/layout/Layout";
import { DataTable, Column, Action } from "@/components/ui/data-table";
import { Button } from "@/components/ui/button";
import { useAppStore, MessageTemplate } from "@/lib/store";
import { Plus } from "lucide-react";
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
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export default function Templates() {
  const { templates, loadTemplates, addTemplate, updateTemplate, deleteTemplate } = useAppStore();
  const { toast } = useToast();
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  useEffect(() => {
    loadTemplates();
  }, [loadTemplates]);
  const [editingTemplate, setEditingTemplate] = useState<MessageTemplate | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    content: '',
    category: ''
  });

  const resetForm = () => {
    setFormData({ name: '', content: '', category: '' });
    setEditingTemplate(null);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (editingTemplate) {
      updateTemplate(editingTemplate.id, formData);
      toast({
        title: "Modèle modifié",
        description: `${formData.name} a été mis à jour avec succès.`,
      });
    } else {
      addTemplate(formData);
      toast({
        title: "Modèle créé",
        description: `${formData.name} a été créé avec succès.`,
      });
    }
    
    resetForm();
    setIsDialogOpen(false);
  };

  const handleEdit = (template: MessageTemplate) => {
    setEditingTemplate(template);
    setFormData({
      name: template.name,
      content: template.content,
      category: template.category
    });
    setIsDialogOpen(true);
  };

  const handleDelete = (template: MessageTemplate) => {
    deleteTemplate(template.id);
    toast({
      title: "Modèle supprimé",
      description: `${template.name} a été supprimé avec succès.`,
      variant: "destructive",
    });
  };

  const columns: Column<MessageTemplate>[] = [
    {
      key: 'name',
      header: 'Nom du Modèle',
    },
    {
      key: 'content',
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
    }
  ];

  const actions: Action<MessageTemplate>[] = [
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
                  <Label htmlFor="content">Contenu du message</Label>
                  <Textarea
                    id="content"
                    value={formData.content}
                    onChange={(e) => setFormData({ ...formData, content: e.target.value })}
                    placeholder="Entrez le contenu de votre message..."
                    rows={6}
                    required
                  />
                  <p className="text-sm text-muted-foreground">
                    Caractères: {formData.content.length}/160
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
          <DataTable
            data={templates}
            columns={columns}
            actions={actions}
            searchPlaceholder="Rechercher des modèles..."
          />
        </div>
      </div>
    </Layout>
  );
}