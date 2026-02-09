'use client'

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
  { label: string; className: string }
> = {
  completed: {
    label: 'Completed',
    className: 'bg-green-100 text-green-800 border-green-200',
  },
  in_progress: {
    label: 'In Progress',
    className: 'bg-blue-100 text-blue-800 border-blue-200',
  },
  not_started: {
    label: 'Not Started',
    className: 'bg-gray-100 text-gray-800 border-gray-200',
  },
  on_hold: {
    label: 'On Hold',
    className: 'bg-amber-100 text-amber-800 border-amber-200',
  },
  overdue: {
    label: 'Overdue',
    className: 'bg-red-100 text-red-800 border-red-200',
  },
  active: {
    label: 'Active',
    className: 'bg-green-100 text-green-800 border-green-200',
  },
  inactive: {
    label: 'Inactive',
    className: 'bg-gray-100 text-gray-800 border-gray-200',
  },
  pending: {
    label: 'Pending',
    className: 'bg-amber-100 text-amber-800 border-amber-200',
  },
  exited: {
    label: 'Exited',
    className: 'bg-red-100 text-red-800 border-red-200',
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
  }

  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border',
        config.className,
        className
      )}
    >
      {config.label}
    </span>
  )
}
