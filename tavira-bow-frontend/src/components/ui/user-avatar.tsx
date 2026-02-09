'use client'

import { cn } from '@/lib/utils'

interface UserAvatarProps {
  name?: string | null
  size?: 'sm' | 'md' | 'lg'
  className?: string
}

const sizeClasses = {
  sm: 'h-6 w-6 text-xs',
  md: 'h-8 w-8 text-sm',
  lg: 'h-10 w-10 text-base',
}

export function UserAvatar({ name, size = 'sm', className }: UserAvatarProps) {
  const initials = name
    ? name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
    : '?'

  return (
    <div
      className={cn(
        'rounded-full bg-primary/10 flex items-center justify-center font-medium text-primary',
        sizeClasses[size],
        className
      )}
    >
      {initials}
    </div>
  )
}

interface UserAvatarWithNameProps extends UserAvatarProps {
  showName?: boolean
}

export function UserAvatarWithName({
  name,
  size = 'sm',
  showName = true,
  className,
}: UserAvatarWithNameProps) {
  return (
    <div className={cn('flex items-center gap-2', className)}>
      <UserAvatar name={name} size={size} />
      {showName && (
        <span className="text-sm text-muted-foreground">
          {name || 'Unassigned'}
        </span>
      )}
    </div>
  )
}
