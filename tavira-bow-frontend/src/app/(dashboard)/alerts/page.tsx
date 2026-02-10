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
  Filter,
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { api } from '@/lib/api'
import { safeParseDate } from '@/lib/utils'
import { formatDistanceToNow } from 'date-fns'
import { fr } from 'date-fns/locale'

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

const alertTypeLabels: Record<Alert['type'], string> = {
  overdue_task: 'Overdue Work Item',
  expiring_contract: 'Expiring Contract',
  high_risk: 'High Risk',
  pending_review: 'Pending Review',
}

const severityColors: Record<Alert['severity'], string> = {
  high: 'bg-red-500',
  medium: 'bg-amber-500',
  low: 'bg-blue-500',
}

const severityLabels: Record<Alert['severity'], string> = {
  high: 'Haute',
  medium: 'Moyenne',
  low: 'Basse',
}

export default function AlertsPage() {
  const [alerts, setAlerts] = useState<Alert[]>([])
  const [total, setTotal] = useState(0)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [severityFilter, setSeverityFilter] = useState<string>('all')
  const [typeFilter, setTypeFilter] = useState<string>('all')

  useEffect(() => {
    const fetchAlerts = async () => {
      try {
        setIsLoading(true)
        const response = await api.get<AlertsResponse>('/dashboard/alerts')
        setAlerts(response.data.data)
        setTotal(response.data.total)
        setError(null)
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Error during loading')
        setAlerts([])
        setTotal(0)
      } finally {
        setIsLoading(false)
      }
    }
    fetchAlerts()
  }, [])

  const filteredAlerts = alerts.filter((alert) => {
    if (severityFilter !== 'all' && alert.severity !== severityFilter) return false
    if (typeFilter !== 'all' && alert.type !== typeFilter) return false
    return true
  })

  if (isLoading) {
    return (
      <div className="container mx-auto py-6 space-y-6">
        <h1 className="text-3xl font-bold">Alerts</h1>
        <Card>
          <CardContent className="p-6 space-y-4">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="flex items-start gap-3">
                <Skeleton className="h-10 w-10 rounded-full" />
                <div className="flex-1 space-y-2">
                  <Skeleton className="h-4 w-3/4" />
                  <Skeleton className="h-3 w-full" />
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <div className="container mx-auto py-6 space-y-6">
      <div className="flex items-center gap-3">
        <AlertCircle className="h-8 w-8 text-primary" />
        <div>
          <h1 className="text-3xl font-bold">Alerts</h1>
          <p className="text-muted-foreground">
            {total} active alert{total !== 1 ? 's' : ''}
          </p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-lg">
            <Filter className="h-4 w-4" />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-4">
            <div className="w-48">
              <label className="text-sm font-medium mb-1 block">Severity</label>
              <Select value={severityFilter} onValueChange={setSeverityFilter}>
                <SelectTrigger>
                  <SelectValue placeholder="All" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="low">Low</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="w-48">
              <label className="text-sm font-medium mb-1 block">Type</label>
              <Select value={typeFilter} onValueChange={setTypeFilter}>
                <SelectTrigger>
                  <SelectValue placeholder="All" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All</SelectItem>
                  <SelectItem value="overdue_task">Overdue Work Items</SelectItem>
                  <SelectItem value="expiring_contract">Expiring Contracts</SelectItem>
                  <SelectItem value="high_risk">High Risks</SelectItem>
                  <SelectItem value="pending_review">Pending</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="p-6">
          {error && (
            <div className="bg-destructive/10 text-destructive p-4 rounded-lg mb-4">
              {error}
            </div>
          )}

          {filteredAlerts.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <Bell className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p className="text-lg">No alerts</p>
              <p className="text-sm">
                {alerts.length > 0
                  ? 'No alerts match the filters'
                  : 'Everything is in order!'}
              </p>
            </div>
          ) : (
            <div className="space-y-3">
              {filteredAlerts.map((alert) => {
                const Icon = alertIcons[alert.type]
                const content = (
                  <>
                    <div className={'p-3 rounded-full ' + severityColors[alert.severity] + ' bg-opacity-10 shrink-0'}>
                      <Icon className={'h-5 w-5 ' + (alert.severity === 'high' ? 'text-red-500' : alert.severity === 'medium' ? 'text-amber-500' : 'text-blue-500')} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-1 flex-wrap">
                        <p className="font-medium">{alert.title}</p>
                        <Badge variant={alert.severity === 'high' ? 'destructive' : 'secondary'} className="text-xs">
                          {severityLabels[alert.severity]}
                        </Badge>
                        <Badge variant="outline" className="text-xs">
                          {alertTypeLabels[alert.type]}
                        </Badge>
                      </div>
                      <p className="text-sm text-muted-foreground">{alert.description}</p>
                      <p className="text-xs text-muted-foreground mt-2">
                        {(() => {
                          const date = safeParseDate(alert.created_at)
                          return date ? formatDistanceToNow(date, { addSuffix: true, locale: fr }) : '-'
                        })()}
                      </p>
                    </div>
                    {alert.link && <ChevronRight className="h-5 w-5 text-muted-foreground shrink-0" />}
                  </>
                )

                if (alert.link) {
                  return (
                    <Link key={alert.id} href={alert.link} className="flex items-center gap-4 p-4 rounded-lg border hover:bg-muted/50 transition-colors group">
                      {content}
                    </Link>
                  )
                }
                return (
                  <div key={alert.id} className="flex items-center gap-4 p-4 rounded-lg border">
                    {content}
                  </div>
                )
              })}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
