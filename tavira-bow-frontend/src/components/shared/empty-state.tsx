'use client'

import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  FileQuestion,
  Search,
  Plus,
  AlertCircle,
  FolderOpen,
  Users,
  FileText,
  Shield,
  Building2,
  LucideIcon,
} from 'lucide-react'

interface EmptyStateProps {
  icon?: LucideIcon
  title: string
  description?: string
  actionLabel?: string
  onAction?: () => void
  className?: string
}

export function EmptyState({
  icon: Icon = FileQuestion,
  title,
  description,
  actionLabel,
  onAction,
  className,
}: EmptyStateProps) {
  return (
    <div
      className={cn(
        'flex flex-col items-center justify-center py-12 text-center',
        className
      )}
    >
      <div className="flex h-16 w-16 items-center justify-center rounded-full bg-muted">
        <Icon className="h-8 w-8 text-muted-foreground" />
      </div>
      <h3 className="mt-4 text-lg font-semibold">{title}</h3>
      {description && (
        <p className="mt-2 max-w-sm text-sm text-muted-foreground">
          {description}
        </p>
      )}
      {actionLabel && onAction && (
        <Button onClick={onAction} className="mt-6">
          <Plus className="mr-2 h-4 w-4" />
          {actionLabel}
        </Button>
      )}
    </div>
  )
}

// Preset empty states for common use cases
export function EmptySearchResults() {
  return (
    <EmptyState
      icon={Search}
      title="Aucun resultat"
      description="Aucun element ne correspond a votre recherche. Essayez avec d'autres termes."
    />
  )
}

export function EmptyWorkItems({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={FileText}
      title="Aucune tache"
      description="Commencez par creer votre premiere tache pour suivre vos projets."
      actionLabel="Nouvelle tache"
      onAction={onAdd}
    />
  )
}

export function EmptyGovernance({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={FolderOpen}
      title="Aucun element de gouvernance"
      description="Ajoutez des elements de gouvernance pour suivre vos processus."
      actionLabel="Nouvel element"
      onAction={onAdd}
    />
  )
}

export function EmptySuppliers({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={Building2}
      title="Aucun fournisseur"
      description="Ajoutez vos fournisseurs pour gerer vos contrats et factures."
      actionLabel="Nouveau fournisseur"
      onAction={onAdd}
    />
  )
}

export function EmptyRisks({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={Shield}
      title="Aucun risque"
      description="Identifiez et enregistrez les risques pour mieux les gerer."
      actionLabel="Nouveau risque"
      onAction={onAdd}
    />
  )
}

export function EmptyTeams({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={Users}
      title="Aucune equipe"
      description="Creez des equipes pour organiser vos collaborateurs."
      actionLabel="Nouvelle equipe"
      onAction={onAdd}
    />
  )
}

export function ErrorState({
  title = 'Une erreur est survenue',
  description,
  onRetry,
}: {
  title?: string
  description?: string
  onRetry?: () => void
}) {
  return (
    <div className="flex flex-col items-center justify-center py-12 text-center">
      <div className="flex h-16 w-16 items-center justify-center rounded-full bg-destructive/10">
        <AlertCircle className="h-8 w-8 text-destructive" />
      </div>
      <h3 className="mt-4 text-lg font-semibold">{title}</h3>
      {description && (
        <p className="mt-2 max-w-sm text-sm text-muted-foreground">
          {description}
        </p>
      )}
      {onRetry && (
        <Button variant="outline" onClick={onRetry} className="mt-6">
          Reessayer
        </Button>
      )}
    </div>
  )
}
