import { useState } from "react";
import { Layout } from "@/components/layout/Layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { useToast } from "@/hooks/use-toast";
import { useNavigate } from "react-router-dom";
import { useAppStore } from "@/lib/store";

type CampaignStep = 'info' | 'contacts' | 'message' | 'schedule' | 'review';

export default function CampaignCreate() {
  const { toast } = useToast();
  const navigate = useNavigate();
  const { contacts, templates, addCampaign } = useAppStore();
  const [currentStep, setCurrentStep] = useState<CampaignStep>('info');
  
  const [campaignData, setCampaignData] = useState({
    name: '',
    description: '',
    type: 'immediate',
    selectedGroups: [] as string[],
    message: '',
    scheduleDate: '',
    scheduleTime: ''
  });

  const steps = [
    { id: 'info', title: 'Informations générales', completed: false },
    { id: 'contacts', title: 'Sélection contacts', completed: false },
    { id: 'message', title: 'Composition message', completed: false },
    { id: 'schedule', title: 'Planification', completed: false },
    { id: 'review', title: 'Récapitulatif', completed: false }
  ];

  const groups = ['Clients', 'Prospects', 'Partenaires'];
  const selectedContacts = contacts.filter(contact => 
    campaignData.selectedGroups.includes(contact.group)
  );

  const handleNext = () => {
    const stepOrder: CampaignStep[] = ['info', 'contacts', 'message', 'schedule', 'review'];
    const currentIndex = stepOrder.indexOf(currentStep);
    if (currentIndex < stepOrder.length - 1) {
      setCurrentStep(stepOrder[currentIndex + 1]);
    }
  };

  const handlePrevious = () => {
    const stepOrder: CampaignStep[] = ['info', 'contacts', 'message', 'schedule', 'review'];
    const currentIndex = stepOrder.indexOf(currentStep);
    if (currentIndex > 0) {
      setCurrentStep(stepOrder[currentIndex - 1]);
    }
  };

  const handleLaunch = () => {
    addCampaign({
      name: campaignData.name,
      status: campaignData.type === 'immediate' ? 'Actif' : 'Planifié',
      messagesSent: campaignData.type === 'immediate' ? Math.floor(Math.random() * 10000) : 0,
      deliveryRate: campaignData.type === 'immediate' ? 95 + Math.random() * 5 : 0,
      ctr: campaignData.type === 'immediate' ? Math.random() * 5 : 0,
      createdAt: new Date().toISOString().split('T')[0]
    });

    toast({
      title: "Campagne créée",
      description: `La campagne "${campaignData.name}" a été ${campaignData.type === 'immediate' ? 'lancée' : 'planifiée'} avec succès.`,
    });

    navigate('/dashboard');
  };

  const renderStep = () => {
    switch (currentStep) {
      case 'info':
        return (
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Nom de la campagne</Label>
              <Input
                id="name"
                value={campaignData.name}
                onChange={(e) => setCampaignData({ ...campaignData, name: e.target.value })}
                placeholder="Ex: Promotion Été 2024"
              />
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                value={campaignData.description}
                onChange={(e) => setCampaignData({ ...campaignData, description: e.target.value })}
                placeholder="Décrivez votre campagne..."
                rows={3}
              />
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="type">Type de campagne</Label>
              <Select 
                value={campaignData.type}
                onValueChange={(value) => setCampaignData({ ...campaignData, type: value })}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="immediate">Envoi immédiat</SelectItem>
                  <SelectItem value="scheduled">Envoi planifié</SelectItem>
                  <SelectItem value="recurring">Envoi récurrent</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        );

      case 'contacts':
        return (
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium mb-3">Sélectionnez les groupes de contacts</h3>
              <div className="space-y-3">
                {groups.map((group) => {
                  const groupCount = contacts.filter(c => c.group === group).length;
                  return (
                    <div key={group} className="flex items-center space-x-2">
                      <Checkbox
                        id={group}
                        checked={campaignData.selectedGroups.includes(group)}
                        onCheckedChange={(checked) => {
                          if (checked) {
                            setCampaignData({
                              ...campaignData,
                              selectedGroups: [...campaignData.selectedGroups, group]
                            });
                          } else {
                            setCampaignData({
                              ...campaignData,
                              selectedGroups: campaignData.selectedGroups.filter(g => g !== group)
                            });
                          }
                        }}
                      />
                      <Label htmlFor={group} className="flex-1">
                        {group} ({groupCount} contacts)
                      </Label>
                    </div>
                  );
                })}
              </div>
            </div>
            
            <div className="p-4 bg-muted rounded-lg">
              <p className="text-sm">
                <strong>Destinataires sélectionnés:</strong> {selectedContacts.length} contacts
              </p>
            </div>
          </div>
        );

      case 'message':
        return (
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="message">Votre message</Label>
              <Textarea
                id="message"
                value={campaignData.message}
                onChange={(e) => setCampaignData({ ...campaignData, message: e.target.value })}
                placeholder="Composez votre message SMS..."
                rows={6}
              />
              <p className="text-sm text-muted-foreground">
                Caractères: {campaignData.message.length}/160
              </p>
            </div>
            
            <div>
              <h4 className="font-medium mb-2">Templates disponibles</h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                {templates.slice(0, 4).map((template) => (
                  <Button
                    key={template.id}
                    variant="outline"
                    size="sm"
                    onClick={() => setCampaignData({ ...campaignData, message: template.content })}
                    className="text-left h-auto p-3"
                  >
                    <div>
                      <p className="font-medium text-sm">{template.name}</p>
                      <p className="text-xs text-muted-foreground truncate">
                        {template.content.substring(0, 50)}...
                      </p>
                    </div>
                  </Button>
                ))}
              </div>
            </div>
          </div>
        );

      case 'schedule':
        return (
          <div className="space-y-4">
            {campaignData.type === 'immediate' ? (
              <div className="p-4 bg-muted rounded-lg">
                <p>Cette campagne sera envoyée immédiatement après validation.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="scheduleDate">Date d'envoi</Label>
                  <Input
                    id="scheduleDate"
                    type="date"
                    value={campaignData.scheduleDate}
                    onChange={(e) => setCampaignData({ ...campaignData, scheduleDate: e.target.value })}
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="scheduleTime">Heure d'envoi</Label>
                  <Input
                    id="scheduleTime"
                    type="time"
                    value={campaignData.scheduleTime}
                    onChange={(e) => setCampaignData({ ...campaignData, scheduleTime: e.target.value })}
                  />
                </div>
              </div>
            )}
            
            <div className="p-4 bg-muted rounded-lg">
              <p className="text-sm">
                <strong>Fuseau horaire:</strong> GMT+1 (Libreville)
              </p>
            </div>
          </div>
        );

      case 'review':
        return (
          <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="font-medium mb-2">Informations générales</h3>
                <div className="space-y-1 text-sm">
                  <p><strong>Nom:</strong> {campaignData.name}</p>
                  <p><strong>Type:</strong> {campaignData.type === 'immediate' ? 'Immédiat' : 'Planifié'}</p>
                  <p><strong>Destinataires:</strong> {selectedContacts.length} contacts</p>
                </div>
              </div>
              
              <div>
                <h3 className="font-medium mb-2">Planning</h3>
                <div className="space-y-1 text-sm">
                  {campaignData.type === 'immediate' ? (
                    <p>Envoi immédiat</p>
                  ) : (
                    <>
                      <p><strong>Date:</strong> {campaignData.scheduleDate}</p>
                      <p><strong>Heure:</strong> {campaignData.scheduleTime}</p>
                    </>
                  )}
                </div>
              </div>
            </div>
            
            <div>
              <h3 className="font-medium mb-2">Message</h3>
              <div className="p-4 bg-muted rounded-lg">
                <p className="text-sm">{campaignData.message}</p>
              </div>
            </div>
            
            <div className="p-4 bg-muted rounded-lg">
              <p className="text-sm">
                <strong>Estimation du coût:</strong> {(selectedContacts.length * 25).toLocaleString('fr-FR')} F CFA
              </p>
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <Layout title="Création de Campagne">
      <div className="max-w-4xl mx-auto space-y-6">
        {/* Progress Steps */}
        <div className="flex items-center justify-between">
          {steps.map((step, index) => (
            <div key={step.id} className="flex items-center">
              <div className={`
                w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                ${currentStep === step.id 
                  ? 'bg-primary text-primary-foreground' 
                  : 'bg-muted text-muted-foreground'
                }
              `}>
                {index + 1}
              </div>
              <span className={`
                ml-2 text-sm font-medium
                ${currentStep === step.id ? 'text-primary' : 'text-muted-foreground'}
              `}>
                {step.title}
              </span>
              {index < steps.length - 1 && (
                <div className="w-12 h-px bg-border mx-4" />
              )}
            </div>
          ))}
        </div>

        {/* Step Content */}
        <Card>
          <CardHeader>
            <CardTitle>
              {steps.find(s => s.id === currentStep)?.title}
            </CardTitle>
          </CardHeader>
          <CardContent>
            {renderStep()}
          </CardContent>
        </Card>

        {/* Navigation */}
        <div className="flex justify-between">
          <Button 
            variant="outline" 
            onClick={handlePrevious}
            disabled={currentStep === 'info'}
          >
            Précédent
          </Button>
          
          <div className="space-x-2">
            {currentStep === 'review' ? (
              <Button onClick={handleLaunch}>
                {campaignData.type === 'immediate' ? 'Lancer la campagne' : 'Planifier la campagne'}
              </Button>
            ) : (
              <Button onClick={handleNext}>
                Suivant
              </Button>
            )}
          </div>
        </div>
      </div>
    </Layout>
  );
}