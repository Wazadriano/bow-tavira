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
      title="No results"
      description="No items match your search. Try different terms."
    />
  )
}

export function EmptyWorkItems({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={FileText}
      title="No work items"
      description="Start by creating your first work item to track your projects."
      actionLabel="New Work Item"
      onAction={onAdd}
    />
  )
}

export function EmptyGovernance({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={FolderOpen}
      title="No governance items"
      description="Add governance items to track your processes."
      actionLabel="New Item"
      onAction={onAdd}
    />
  )
}

export function EmptySuppliers({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={Building2}
      title="No suppliers"
      description="Add your suppliers to manage contracts and invoices."
      actionLabel="New Supplier"
      onAction={onAdd}
    />
  )
}

export function EmptyRisks({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={Shield}
      title="No risks"
      description="Identify and register risks to better manage them."
      actionLabel="New Risk"
      onAction={onAdd}
    />
  )
}

export function EmptyTeams({ onAdd }: { onAdd?: () => void }) {
  return (
    <EmptyState
      icon={Users}
      title="No teams"
      description="Create teams to organize your collaborators."
      actionLabel="New Team"
      onAction={onAdd}
    />
  )
}

export function ErrorState({
  title = 'An error occurred',
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
          Retry
        </Button>
      )}
    </div>
  )
}
