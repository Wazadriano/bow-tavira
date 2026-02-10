'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import {
  AlertCircle,
  Clock,
  FileWarning,
  AlertTriangle,
  ChevronRight,
  Bell,
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { api } from '@/lib/api'
import { safeParseDate } from '@/lib/utils'
import { formatDistanceToNow } from 'date-fns'
import { enUS } from 'date-fns/locale'

interface Alert {
  id: number
  type: 'overdue_task' | 'expiring_contract' | 'high_risk' | 'pending_review'
  title: string
  description: string
  severity: 'high' | 'medium' | 'low'
  link: string | null
  created_at: string
}

interface AlertsResponse {
  data: Alert[]
  total: number
}

const alertIcons: Record<Alert['type'], React.ElementType> = {
  overdue_task: Clock,
  expiring_contract: FileWarning,
  high_risk: AlertTriangle,
  pending_review: Bell,
}

const severityColors: Record<Alert['severity'], string> = {
  high: 'bg-red-500',
  medium: 'bg-amber-500',
  low: 'bg-blue-500',
}

export function AlertsPanel() {
  const [alerts, setAlerts] = useState<Alert[]>([])
  const [total, setTotal] = useState(0)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const fetchAlerts = async () => {
      try {
        setIsLoading(true)
        const response = await api.get<AlertsResponse>('/dashboard/alerts')
        setAlerts(response.data.data)
        setTotal(response.data.total)
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Error loading alerts')
        // Fallback to mock data for demo
        setAlerts([
          {
            id: 1,
            type: 'overdue_task',
            title: 'Overdue Work Item',
            description: 'Database migration - 3 days overdue',
            severity: 'high',
            link: '/tasks/1',
            created_at: new Date().toISOString(),
          },
          {
            id: 2,
            type: 'expiring_contract',
            title: 'Expiring Contract',
            description: 'AWS contract expires in 15 days',
            severity: 'medium',
            link: '/suppliers/2',
            created_at: new Date().toISOString(),
          },
          {
            id: 3,
            type: 'high_risk',
            title: 'High Risk',
            description: 'RSK-001: Unaddressed cybersecurity risk',
            severity: 'high',
            link: '/risks/3',
            created_at: new Date().toISOString(),
          },
        ])
        setTotal(3)
      } finally {
        setIsLoading(false)
      }
    }

    fetchAlerts()
  }, [])

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <AlertCircle className="h-5 w-5" />
            Alerts
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="flex items-start gap-3">
              <Skeleton className="h-8 w-8 rounded-full" />
              <div className="flex-1 space-y-2">
                <Skeleton className="h-4 w-3/4" />
                <Skeleton className="h-3 w-full" />
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="flex items-center gap-2">
          <AlertCircle className="h-5 w-5" />
          Alertes
          {total > 0 && (
            <Badge variant="destructive" className="ml-2">
              {total}
            </Badge>
          )}
        </CardTitle>
      </CardHeader>
      <CardContent>
        {alerts.length === 0 ? (
          <div className="text-center py-8 text-muted-foreground">
            <Bell className="h-8 w-8 mx-auto mb-2 opacity-50" />
            <p>No active alerts</p>
          </div>
        ) : (
          <div className="space-y-4">
            {alerts.slice(0, 5).map((alert) => {
              const Icon = alertIcons[alert.type]
              const content = (
                <>
                  <div
                    className={`p-2 rounded-full ${severityColors[alert.severity]} bg-opacity-10`}
                  >
                    <Icon
                      className={`h-4 w-4 ${
                        alert.severity === 'high'
                          ? 'text-red-500'
                          : alert.severity === 'medium'
                          ? 'text-amber-500'
                          : 'text-blue-500'
                      }`}
                    />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-sm">{alert.title}</p>
                    <p className="text-sm text-muted-foreground truncate">
                      {alert.description}
                    </p>
                    <p className="text-xs text-muted-foreground mt-1">
                      {(() => {
                        const date = safeParseDate(alert.created_at)
                        return date
                          ? formatDistanceToNow(date, { addSuffix: true, locale: enUS })
                          : '-'
                      })()}
                    </p>
                  </div>
                  <ChevronRight className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
                </>
              )

              // Only render Link if alert.link is valid
              if (alert.link) {
                return (
                  <Link
                    key={alert.id}
                    href={alert.link}
                    className="flex items-start gap-3 p-3 rounded-lg border hover:bg-muted/50 transition-colors group"
                  >
                    {content}
                  </Link>
                )
              }

              // Render as div if no link
              return (
                <div
                  key={alert.id}
                  className="flex items-start gap-3 p-3 rounded-lg border"
                >
                  {content}
                </div>
              )
            })}

            {total > 5 && (
              <Button variant="ghost" className="w-full" asChild>
                <Link href="/alerts">
                  View all alerts ({total})
                  <ChevronRight className="h-4 w-4 ml-1" />
                </Link>
              </Button>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
