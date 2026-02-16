import {
  CheckCircle2,
  Loader2,
  CircleDashed,
  PauseCircle,
  AlertCircle,
  PlayCircle,
  CircleOff,
  Clock,
  LogOut,
  type LucideIcon,
} from 'lucide-react'
import { cn } from '@/lib/utils'

type Status =
  | 'completed'
  | 'in_progress'
  | 'not_started'
  | 'on_hold'
  | 'overdue'
  | 'active'
  | 'inactive'
  | 'pending'
  | 'exited'

interface StatusBadgeProps {
  status: string
  className?: string
}

const statusConfig: Record<
  Status,
  { label: string; className: string; Icon: LucideIcon }
> = {
  completed: {
    label: 'Completed',
    className: 'bg-green-100 text-green-800 border-green-200',
    Icon: CheckCircle2,
  },
  in_progress: {
    label: 'In Progress',
    className: 'bg-blue-100 text-blue-800 border-blue-200',
    Icon: Loader2,
  },
  not_started: {
    label: 'Not Started',
    className: 'bg-gray-100 text-gray-800 border-gray-200',
    Icon: CircleDashed,
  },
  on_hold: {
    label: 'On Hold',
    className: 'bg-amber-100 text-amber-800 border-amber-200',
    Icon: PauseCircle,
  },
  overdue: {
    label: 'Overdue',
    className: 'bg-red-100 text-red-800 border-red-200',
    Icon: AlertCircle,
  },
  active: {
    label: 'Active',
    className: 'bg-green-100 text-green-800 border-green-200',
    Icon: PlayCircle,
  },
  inactive: {
    label: 'Inactive',
    className: 'bg-gray-100 text-gray-800 border-gray-200',
    Icon: CircleOff,
  },
  pending: {
    label: 'Pending',
    className: 'bg-amber-100 text-amber-800 border-amber-200',
    Icon: Clock,
  },
  exited: {
    label: 'Exited',
    className: 'bg-red-100 text-red-800 border-red-200',
    Icon: LogOut,
  },
}

export function StatusBadge({ status, className }: StatusBadgeProps) {
  // Normalize status string
  const normalizedStatus = status
    .toLowerCase()
    .replace(/\s+/g, '_') as Status

  const config = statusConfig[normalizedStatus] || {
    label: status,
    className: 'bg-gray-100 text-gray-800 border-gray-200',
    Icon: CircleDashed as LucideIcon,
  }

  const Icon = config.Icon

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold border',
        config.className,
        className
      )}
    >
      {normalizedStatus === 'in_progress' ? (
        <Icon className="h-3.5 w-3.5 shrink-0 animate-spin" />
      ) : (
        <Icon className="h-3.5 w-3.5 shrink-0" />
      )}
      {config.label}
    </span>
  )
}
