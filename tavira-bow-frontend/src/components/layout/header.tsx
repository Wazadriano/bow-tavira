'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { Bell, Search, Moon, Sun, Check } from 'lucide-react'
import { useTheme } from 'next-themes'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useNotificationsStore } from '@/stores/notifications'
import { formatDistanceToNow } from 'date-fns'

interface HeaderProps {
  title: string
  description?: string
  actions?: React.ReactNode
}

function getNotificationLabel(type: string): string {
  const labels: Record<string, string> = {
    task_assigned: 'Task Assigned',
    task_due_reminder: 'Task Reminder',
    contract_expiring: 'Contract Expiring',
    risk_threshold_breached: 'Risk Alert',
  }
  return labels[type] || 'Notification'
}

export function Header({ title, description, actions }: HeaderProps) {
  const { setTheme, theme } = useTheme()
  const router = useRouter()
  const { items, unreadCount, fetchNotifications, fetchUnreadCount, markAsRead, markAllAsRead } =
    useNotificationsStore()

  useEffect(() => {
    fetchUnreadCount()
    const interval = setInterval(fetchUnreadCount, 60000)
    return () => clearInterval(interval)
  }, [fetchUnreadCount])

  const handleBellOpen = () => {
    fetchNotifications()
  }

  return (
    <header className="flex h-16 items-center justify-between border-b bg-card px-6">
      <div>
        <h1 className="text-lg font-semibold">{title}</h1>
        {description && (
          <p className="text-sm text-muted-foreground">{description}</p>
        )}
      </div>

      <div className="flex items-center gap-4">
        {actions}

        <div className="relative hidden md:block">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input placeholder="Search..." className="w-64 pl-9" />
        </div>

        <Button
          variant="ghost"
          size="icon"
          onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
        >
          <Sun className="h-5 w-5 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
          <Moon className="absolute h-5 w-5 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
          <span className="sr-only">Toggle theme</span>
        </Button>

        <DropdownMenu onOpenChange={(open) => open && handleBellOpen()}>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" className="relative">
              <Bell className="h-5 w-5" />
              {unreadCount > 0 && (
                <span className="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-[10px] text-destructive-foreground">
                  {unreadCount > 9 ? '9+' : unreadCount}
                </span>
              )}
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-80">
            {items.length === 0 ? (
              <div className="p-4 text-center text-sm text-muted-foreground">
                No notifications
              </div>
            ) : (
              <>
                {items.slice(0, 5).map((notification) => (
                  <DropdownMenuItem
                    key={notification.id}
                    className="cursor-pointer"
                    onClick={() => {
                      if (!notification.read_at) {
                        markAsRead(notification.id)
                      }
                    }}
                  >
                    <div className="flex w-full flex-col gap-1">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-medium">
                          {getNotificationLabel(notification.data.type)}
                        </p>
                        {!notification.read_at && (
                          <span className="h-2 w-2 rounded-full bg-primary" />
                        )}
                      </div>
                      <p className="text-xs text-muted-foreground">
                        {notification.data.message}
                      </p>
                      <p className="text-xs text-muted-foreground/60">
                        {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
                      </p>
                    </div>
                  </DropdownMenuItem>
                ))}
                <DropdownMenuSeparator />
                {unreadCount > 0 && (
                  <DropdownMenuItem onClick={() => markAllAsRead()}>
                    <Check className="mr-2 h-4 w-4" />
                    Mark all as read
                  </DropdownMenuItem>
                )}
                <DropdownMenuItem onClick={() => router.push('/notifications')}>
                  View all notifications
                </DropdownMenuItem>
              </>
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </header>
  )
}
