import { useEffect } from 'react'
import { Bell, Check, CheckCheck, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Header } from '@/components/layout/header'
import { useNotificationsStore } from '@/stores/notifications'
import { formatDistanceToNow } from 'date-fns'
import { cn } from '@/lib/utils'

function getNotificationIcon(type: string) {
  const icons: Record<string, string> = {
    task_assigned: 'Task Assigned',
    task_due_reminder: 'Task Reminder',
    contract_expiring: 'Contract Expiring',
    risk_threshold_breached: 'Risk Alert',
  }
  return icons[type] || 'Notification'
}

export default function NotificationsPage() {
  const {
    items,
    unreadCount,
    currentPage,
    lastPage,
    total,
    isLoading,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    deleteNotification,
  } = useNotificationsStore()

  useEffect(() => {
    fetchNotifications()
  }, [fetchNotifications])

  return (
    <div className="flex flex-col">
      <Header
        title="Notifications"
        description={`${total} notification(s), ${unreadCount} unread`}
        actions={
          unreadCount > 0 ? (
            <Button variant="outline" size="sm" onClick={() => markAllAsRead()}>
              <CheckCheck className="mr-2 h-4 w-4" />
              Mark all as read
            </Button>
          ) : undefined
        }
      />

      <div className="p-6">
        {isLoading ? (
          <div className="flex items-center justify-center py-12">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
          </div>
        ) : items.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
            <Bell className="mb-4 h-12 w-12" />
            <p className="text-lg font-medium">No notifications</p>
            <p className="text-sm">You are all caught up.</p>
          </div>
        ) : (
          <div className="space-y-2">
            {items.map((notification) => (
              <Card
                key={notification.id}
                className={cn(
                  'transition-colors',
                  !notification.read_at && 'border-primary/30 bg-primary/5'
                )}
              >
                <CardContent className="flex items-start justify-between p-4">
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <p className="text-sm font-medium">
                        {getNotificationIcon(notification.data.type)}
                      </p>
                      {!notification.read_at && (
                        <span className="h-2 w-2 rounded-full bg-primary" />
                      )}
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {notification.data.message}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground/60">
                      {formatDistanceToNow(new Date(notification.created_at), {
                        addSuffix: true,
                      })}
                    </p>
                  </div>
                  <div className="flex items-center gap-1">
                    {!notification.read_at && (
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8"
                        onClick={() => markAsRead(notification.id)}
                        title="Mark as read"
                      >
                        <Check className="h-4 w-4" />
                      </Button>
                    )}
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 text-destructive hover:text-destructive"
                      onClick={() => deleteNotification(notification.id)}
                      title="Delete"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}

            {lastPage > 1 && (
              <div className="flex items-center justify-center gap-2 pt-4">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={currentPage <= 1}
                  onClick={() => fetchNotifications(currentPage - 1)}
                >
                  Previous
                </Button>
                <span className="text-sm text-muted-foreground">
                  Page {currentPage} of {lastPage}
                </span>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={currentPage >= lastPage}
                  onClick={() => fetchNotifications(currentPage + 1)}
                >
                  Next
                </Button>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
