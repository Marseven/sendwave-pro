import { Button } from "@/components/ui/button";
import { Plus, Bell } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { useAppStore } from "@/lib/store";

interface HeaderProps {
  title: string;
}

export function Header({ title }: HeaderProps) {
  const navigate = useNavigate();
  const { user } = useAppStore();

  return (
    <div className="h-16 bg-white border-b border-border px-6 flex items-center justify-between">
      <h1 className="text-2xl font-semibold text-foreground">{title}</h1>
      
      <div className="flex items-center gap-4">
        <Button 
          onClick={() => navigate('/campaign/create')}
          className="bg-primary hover:bg-primary/90 text-primary-foreground"
        >
          <Plus className="w-4 h-4 mr-2" />
          Créer une Nouvelle Campagne
        </Button>
        
        <Button variant="ghost" size="icon">
          <Bell className="w-5 h-5" />
        </Button>
        
        <Avatar className="w-8 h-8">
          <AvatarImage src={user?.avatar} />
          <AvatarFallback>
            {user?.name?.split(' ').map(n => n[0]).join('')}
          </AvatarFallback>
        </Avatar>
      </div>
    </div>
  );
}