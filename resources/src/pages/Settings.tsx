import { useState } from "react";
import { Layout } from "@/components/layout/Layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { useToast } from "@/hooks/use-toast";

export default function Settings() {
  const { toast } = useToast();
  const [generalSettings, setGeneralSettings] = useState({
    companyName: 'Gestionnaire SMS Inc.',
    timezone: 'GMT+1',
    language: 'Français'
  });

  const [notifications, setNotifications] = useState({
    emailUpdates: true,
    smsAlerts: false
  });

  const [security, setSecuritySettings] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
    twoFactorAuth: true
  });

  const handleGeneralSave = () => {
    toast({
      title: "Paramètres sauvegardés",
      description: "Les paramètres généraux ont été mis à jour avec succès.",
    });
  };

  const handleNotificationSave = () => {
    toast({
      title: "Préférences sauvegardées",
      description: "Vos préférences de notification ont été mises à jour.",
    });
  };

  const handleSecuritySave = () => {
    if (security.newPassword !== security.confirmPassword) {
      toast({
        title: "Erreur",
        description: "Les mots de passe ne correspondent pas.",
        variant: "destructive",
      });
      return;
    }

    toast({
      title: "Sécurité mise à jour",
      description: "Vos paramètres de sécurité ont été modifiés avec succès.",
    });
    
    setSecuritySettings({
      ...security,
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    });
  };

  return (
    <Layout title="Paramètres">
      <div className="space-y-6 max-w-4xl">
        {/* General Settings */}
        <Card>
          <CardHeader>
            <CardTitle>Paramètres Généraux</CardTitle>
            <CardDescription>
              Configurez les paramètres de base de votre compte
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="companyName">Nom de l'Entreprise</Label>
                <Input
                  id="companyName"
                  value={generalSettings.companyName}
                  onChange={(e) => setGeneralSettings({
                    ...generalSettings,
                    companyName: e.target.value
                  })}
                />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="timezone">Fuseau Horaire</Label>
                <Select 
                  value={generalSettings.timezone}
                  onValueChange={(value) => setGeneralSettings({
                    ...generalSettings,
                    timezone: value
                  })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="GMT+1">GMT+1:00 Heure de l'Est (É.-U. et Canada)</SelectItem>
                    <SelectItem value="GMT+0">GMT+0:00 Temps universel coordonné</SelectItem>
                    <SelectItem value="GMT+2">GMT+2:00 Heure d'Europe centrale</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="language">Langue</Label>
                <Select 
                  value={generalSettings.language}
                  onValueChange={(value) => setGeneralSettings({
                    ...generalSettings,
                    language: value
                  })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Français">Français</SelectItem>
                    <SelectItem value="English">English</SelectItem>
                    <SelectItem value="Español">Español</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            
            <Button onClick={handleGeneralSave}>
              Enregistrer les Modifications
            </Button>
          </CardContent>
        </Card>

        {/* Notification Settings */}
        <Card>
          <CardHeader>
            <CardTitle>Paramètres de Notification</CardTitle>
            <CardDescription>
              Gérez vos préférences de notification
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center space-x-2">
              <Checkbox 
                id="emailUpdates"
                checked={notifications.emailUpdates}
                onCheckedChange={(checked) => setNotifications({
                  ...notifications,
                  emailUpdates: checked as boolean
                })}
              />
              <Label htmlFor="emailUpdates">
                Recevoir des notifications par e-mail pour les mises à jour du statut de la campagne
              </Label>
            </div>
            
            <div className="flex items-center space-x-2">
              <Checkbox 
                id="smsAlerts"
                checked={notifications.smsAlerts}
                onCheckedChange={(checked) => setNotifications({
                  ...notifications,
                  smsAlerts: checked as boolean
                })}
              />
              <Label htmlFor="smsAlerts">
                Recevoir des alertes SMS pour les problèmes critiques
              </Label>
            </div>
            
            <Button onClick={handleNotificationSave}>
              Sauvegarder les Préférences
            </Button>
          </CardContent>
        </Card>

        {/* Security Settings */}
        <Card>
          <CardHeader>
            <CardTitle>Paramètres de Sécurité</CardTitle>
            <CardDescription>
              Gérez votre mot de passe et vos options de sécurité
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="currentPassword">Mot de Passe Actuel</Label>
                <Input
                  id="currentPassword"
                  type="password"
                  value={security.currentPassword}
                  onChange={(e) => setSecuritySettings({
                    ...security,
                    currentPassword: e.target.value
                  })}
                />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="newPassword">Nouveau Mot de Passe</Label>
                <Input
                  id="newPassword"
                  type="password"
                  value={security.newPassword}
                  onChange={(e) => setSecuritySettings({
                    ...security,
                    newPassword: e.target.value
                  })}
                />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="confirmPassword">Confirmer le Nouveau Mot de Passe</Label>
                <Input
                  id="confirmPassword"
                  type="password"
                  value={security.confirmPassword}
                  onChange={(e) => setSecuritySettings({
                    ...security,
                    confirmPassword: e.target.value
                  })}
                />
              </div>
            </div>
            
            <div className="flex items-center space-x-2">
              <Checkbox 
                id="twoFactorAuth"
                checked={security.twoFactorAuth}
                onCheckedChange={(checked) => setSecuritySettings({
                  ...security,
                  twoFactorAuth: checked as boolean
                })}
              />
              <Label htmlFor="twoFactorAuth">
                Activer l'Authentification à Deux Facteurs
              </Label>
            </div>
            
            <Button onClick={handleSecuritySave}>
              Mettre à jour la Sécurité
            </Button>
          </CardContent>
        </Card>
      </div>
    </Layout>
  );
}