import { Circle, CheckCircle, Clock, AlertCircle } from 'lucide-react'
import { cn } from '@/lib/utils'

type RAGStatus = 'blue' | 'green' | 'amber' | 'red'

interface RAGBadgeProps {
  status?: string | null
  variant?: 'circle' | 'pill'
  className?: string
}

const ragConfig: Record<
  RAGStatus,
  { className: string; label: string; Icon: typeof Circle }
> = {
  blue: {
    className: 'bg-sky-100 text-sky-800 border-sky-200',
    label: 'Blue',
    Icon: Circle,
  },
  green: {
    className: 'bg-green-100 text-green-800 border-green-200',
    label: 'Green',
    Icon: CheckCircle,
  },
  amber: {
    className: 'bg-amber-100 text-amber-800 border-amber-200',
    label: 'Amber',
    Icon: Clock,
  },
  red: {
    className: 'bg-red-100 text-red-800 border-red-200',
    label: 'Red',
    Icon: AlertCircle,
  },
}

export function RAGBadge({ status, variant = 'circle', className }: RAGBadgeProps) {
  if (!status) return <span className="text-muted-foreground">-</span>

  const normalizedStatus = status.toLowerCase() as RAGStatus
  const config = ragConfig[normalizedStatus]

  if (!config) return <span className="text-muted-foreground">-</span>

  const Icon = config.Icon

  if (variant === 'pill') {
    return (
      <span
        className={cn(
          'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold border',
          config.className,
          className
        )}
      >
        <Icon className="h-3.5 w-3.5 shrink-0" />
        {config.label}
      </span>
    )
  }

  // Circle variant (default) - icon + letter
  return (
    <span
      className={cn(
        'inline-flex items-center justify-center gap-1 h-6 min-w-[1.5rem] rounded-full text-xs font-bold border px-1.5',
        config.className,
        className
      )}
      title={config.label}
    >
      <Icon className="h-3.5 w-3.5 shrink-0" />
      {config.label.charAt(0)}
    </span>
  )
}
