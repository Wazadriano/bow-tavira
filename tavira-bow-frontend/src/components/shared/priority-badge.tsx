'use client'

import { cn } from '@/lib/utils'

interface PriorityBadgeProps {
  priority: boolean | string | null | undefined
  className?: string
}

export function PriorityBadge({ priority, className }: PriorityBadgeProps) {
  // Handle boolean or string priority
  const isHighPriority =
    priority === true ||
    priority === 'true' ||
    priority === 'high' ||
    priority === 'High'

  if (!isHighPriority) {
    return <span className="text-muted-foreground">-</span>
  }

  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
        'bg-orange-100 text-orange-800 border border-orange-200',
        className
      )}
    >
      High
    </span>
  )
}
