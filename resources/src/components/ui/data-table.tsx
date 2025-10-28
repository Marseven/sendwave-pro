import { useState } from "react";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { 
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { MoreHorizontal, Search } from "lucide-react";

export interface Column<T> {
  key: keyof T;
  header: string;
  render?: (value: any, row: T) => React.ReactNode;
  sortable?: boolean;
}

export interface Action<T> {
  label: string;
  onClick: (row: T) => void;
  variant?: "default" | "destructive";
}

interface DataTableProps<T> {
  data: T[];
  columns: Column<T>[];
  actions?: Action<T>[];
  searchable?: boolean;
  searchPlaceholder?: string;
  onSearch?: (query: string) => void;
}

export function DataTable<T extends { id: string }>({
  data,
  columns,
  actions,
  searchable = true,
  searchPlaceholder = "Rechercher...",
  onSearch,
}: DataTableProps<T>) {
  const [searchQuery, setSearchQuery] = useState("");

  const handleSearch = (query: string) => {
    setSearchQuery(query);
    onSearch?.(query);
  };

  const getStatusBadge = (status: string) => {
    const statusColors: Record<string, string> = {
      'Actif': 'bg-success text-success-foreground',
      'Active': 'bg-success text-success-foreground',
      'Terminé': 'bg-muted text-muted-foreground',
      'Completed': 'bg-muted text-muted-foreground',
      'Planifié': 'bg-warning text-warning-foreground',
      'Planned': 'bg-warning text-warning-foreground',
      'En Attente': 'bg-warning text-warning-foreground',
      'Pending': 'bg-warning text-warning-foreground',
      'Inactif': 'bg-muted text-muted-foreground',
      'Inactive': 'bg-muted text-muted-foreground',
    };

    return (
      <Badge className={statusColors[status] || 'bg-muted text-muted-foreground'}>
        {status}
      </Badge>
    );
  };

  return (
    <div className="space-y-4">
      {searchable && (
        <div className="flex items-center gap-2">
          <div className="relative flex-1 max-w-sm">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <Input
              placeholder={searchPlaceholder}
              value={searchQuery}
              onChange={(e) => handleSearch(e.target.value)}
              className="pl-10"
            />
          </div>
        </div>
      )}

      <div className="rounded-lg border border-border bg-card">
        <Table>
          <TableHeader>
            <TableRow className="bg-muted/50">
              {columns.map((column) => (
                <TableHead key={column.key as string} className="font-medium">
                  {column.header}
                </TableHead>
              ))}
              {actions && actions.length > 0 && (
                <TableHead className="font-medium text-right">Actions</TableHead>
              )}
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.map((row) => (
              <TableRow key={row.id} className="hover:bg-muted/50">
                {columns.map((column) => (
                  <TableCell key={column.key as string}>
                    {column.render ? (
                      column.render(row[column.key], row)
                    ) : column.key === 'status' || column.key === 'statut' ? (
                      getStatusBadge(String(row[column.key]))
                    ) : (
                      String(row[column.key])
                    )}
                  </TableCell>
                ))}
                {actions && actions.length > 0 && (
                  <TableCell className="text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm">
                          <MoreHorizontal className="w-4 h-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        {actions.map((action, index) => (
                          <DropdownMenuItem
                            key={index}
                            onClick={() => action.onClick(row)}
                            className={action.variant === 'destructive' ? 'text-destructive' : ''}
                          >
                            {action.label}
                          </DropdownMenuItem>
                        ))}
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                )}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}