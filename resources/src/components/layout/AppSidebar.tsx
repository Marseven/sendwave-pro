import { useState } from "react";
import { NavLink, useLocation } from "react-router-dom";
import {
  BarChart3,
  Rocket,
  Users,
  MessageSquare,
  BarChart,
  Calendar,
  Plug,
  Webhook,
  UserCog,
  Settings,
  ChevronDown,
  ChevronRight,
  MessageCircle
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useAppStore } from "@/lib/store";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

const menuItems = [
  { title: "Tableau de Bord", url: "/dashboard", icon: BarChart3 },
  { title: "Création de Campagne", url: "/campaign/create", icon: Rocket },
  { title: "Gestion des Contacts", url: "/contacts", icon: Users },
  { title: "Modèles de Message", url: "/templates", icon: MessageSquare },
  { title: "Rapports", url: "/reports", icon: BarChart },
  { title: "Calendrier", url: "/calendar", icon: Calendar },
  { title: "API & Interconnexions", url: "/api", icon: Plug },
  { title: "Webhooks", url: "/webhooks", icon: Webhook },
  { title: "Comptes & Sous-comptes", url: "/accounts", icon: UserCog },
  { title: "Paramètres", url: "/settings", icon: Settings },
];

export function AppSidebar() {
  const location = useLocation();
  const { user } = useAppStore();
  const [collapsed, setCollapsed] = useState(false);

  const isActive = (path: string) => location.pathname === path;

  return (
    <div className={cn(
      "h-screen bg-white border-r border-border flex flex-col transition-all duration-300",
      collapsed ? "w-16" : "w-64"
    )}>
      {/* Header */}
      <div className="p-4 border-b border-border">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
            <MessageCircle className="w-5 h-5 text-primary" />
          </div>
          {!collapsed && (
            <span className="font-semibold text-lg text-foreground">
              JOBS SMS
            </span>
          )}
        </div>
      </div>

      {/* Navigation */}
      <div className="flex-1 py-4">
        <nav className="space-y-1 px-3">
          {menuItems.map((item) => {
            const Icon = item.icon;
            const active = isActive(item.url);
            
            return (
              <NavLink
                key={item.title}
                to={item.url}
                className={cn(
                  "flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors",
                  active 
                    ? "bg-muted text-primary" 
                    : "text-muted-foreground hover:bg-muted/50 hover:text-foreground"
                )}
              >
                <Icon className="w-5 h-5 flex-shrink-0" />
                {!collapsed && <span>{item.title}</span>}
              </NavLink>
            );
          })}
        </nav>
      </div>

      {/* User Profile */}
      <div className="p-4 border-t border-border">
        <div className="flex items-center gap-3">
          <Avatar className="w-8 h-8">
            <AvatarImage src={user?.avatar} />
            <AvatarFallback>
              {user?.name?.split(' ').map(n => n[0]).join('')}
            </AvatarFallback>
          </Avatar>
          {!collapsed && (
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-foreground truncate">
                {user?.name}
              </p>
              <p className="text-xs text-muted-foreground truncate">
                {user?.role}
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}