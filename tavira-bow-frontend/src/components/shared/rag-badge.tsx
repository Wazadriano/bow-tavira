'use client'

import { cn } from '@/lib/utils'

type RAGStatus = 'blue' | 'green' | 'amber' | 'red'

interface RAGBadgeProps {
  status?: string | null
  variant?: 'circle' | 'pill'
  className?: string
}

const ragConfig: Record<RAGStatus, { className: string; label: string }> = {
  blue: {
    className: 'bg-sky-100 text-sky-800 border-sky-200',
    label: 'Blue',
  },
  green: {
    className: 'bg-green-100 text-green-800 border-green-200',
    label: 'Green',
  },
  amber: {
    className: 'bg-amber-100 text-amber-800 border-amber-200',
    label: 'Amber',
  },
  red: {
    className: 'bg-red-100 text-red-800 border-red-200',
    label: 'Red',
  },
}

export function RAGBadge({ status, variant = 'circle', className }: RAGBadgeProps) {
  if (!status) return <span className="text-muted-foreground">-</span>

  const normalizedStatus = status.toLowerCase() as RAGStatus
  const config = ragConfig[normalizedStatus]

  if (!config) return <span className="text-muted-foreground">-</span>

  if (variant === 'pill') {
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

  // Circle variant (default) - single letter
  return (
    <span
      className={cn(
        'inline-flex items-center justify-center h-6 w-6 rounded-full text-xs font-bold border',
        config.className,
        className
      )}
    >
      {config.label.charAt(0)}
    </span>
  )
}
