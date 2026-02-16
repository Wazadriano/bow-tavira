import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Header } from '@/components/layout/header'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { get } from '@/lib/api'
import { formatDistanceToNow } from 'date-fns'

interface AuditEntry {
  id: number
  log_name: string
  description: string
  subject_type: string | null
  subject_id: number | null
  event: string | null
  causer: { id: number; full_name: string } | null
  properties: {
    old?: Record<string, unknown>
    attributes?: Record<string, unknown>
  }
  created_at: string
}

interface AuditResponse {
  data: AuditEntry[]
  meta: {
    current_page: number
    last_page: number
    total: number
    per_page: number
  }
}

interface AuditStats {
  total_events: number
  last_30_days: number
  by_log: Record<string, number>
  by_event: Record<string, number>
}

function getEventColor(event: string | null): string {
  switch (event) {
    case 'created': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'updated': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    case 'deleted': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
  }
}

function getSubjectLabel(type: string | null): string {
  if (!type) return 'System'
  const parts = type.split('\\')
  return parts[parts.length - 1] || type
}

export default function AuditPage() {
  const [page, setPage] = useState(1)
  const [logFilter, setLogFilter] = useState('all')
  const [eventFilter, setEventFilter] = useState('all')

  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', '25')
  if (logFilter !== 'all') params.set('log_name', logFilter)
  if (eventFilter !== 'all') params.set('event', eventFilter)

  const { data: auditData, isLoading } = useQuery({
    queryKey: ['audit', page, logFilter, eventFilter],
    queryFn: () => get<AuditResponse>(`/audit?${params}`),
  })

  const { data: stats } = useQuery({
    queryKey: ['audit-stats'],
    queryFn: () => get<AuditStats>('/audit/stats'),
  })

  return (
    <div className="flex flex-col">
      <Header title="Audit Trail" description="Activity log and change history" />

      <div className="p-6 space-y-6">
        {/* Stats */}
        <div className="grid grid-cols-4 gap-4">
          <Card>
            <CardContent className="p-4">
              <p className="text-sm text-muted-foreground">Total Events</p>
              <p className="text-2xl font-bold">{stats?.total_events ?? 0}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <p className="text-sm text-muted-foreground">Last 30 Days</p>
              <p className="text-2xl font-bold">{stats?.last_30_days ?? 0}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <p className="text-sm text-muted-foreground">Log Types</p>
              <p className="text-2xl font-bold">{Object.keys(stats?.by_log ?? {}).length}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <p className="text-sm text-muted-foreground">Event Types</p>
              <p className="text-2xl font-bold">{Object.keys(stats?.by_event ?? {}).length}</p>
            </CardContent>
          </Card>
        </div>

        {/* Filters */}
        <div className="flex items-center gap-3">
          <Select value={logFilter} onValueChange={(v) => { setLogFilter(v); setPage(1) }}>
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="All Logs" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Logs</SelectItem>
              <SelectItem value="work_items">Work Items</SelectItem>
              <SelectItem value="risks">Risks</SelectItem>
              <SelectItem value="suppliers">Suppliers</SelectItem>
              <SelectItem value="governance">Governance</SelectItem>
            </SelectContent>
          </Select>

          <Select value={eventFilter} onValueChange={(v) => { setEventFilter(v); setPage(1) }}>
            <SelectTrigger className="w-[160px]">
              <SelectValue placeholder="All Events" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Events</SelectItem>
              <SelectItem value="created">Created</SelectItem>
              <SelectItem value="updated">Updated</SelectItem>
              <SelectItem value="deleted">Deleted</SelectItem>
            </SelectContent>
          </Select>

          <Badge variant="secondary">
            {auditData?.meta.total ?? 0} entries
          </Badge>
        </div>

        {/* Timeline */}
        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
          </div>
        ) : (
          <div className="space-y-2">
            {(auditData?.data ?? []).map((entry) => (
              <Card key={entry.id} className="hover:bg-muted/30 transition-colors">
                <CardContent className="p-4">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <Badge className={getEventColor(entry.event)}>
                          {entry.event || 'action'}
                        </Badge>
                        <Badge variant="outline">{entry.log_name}</Badge>
                        <span className="text-sm font-medium">
                          {getSubjectLabel(entry.subject_type)}
                          {entry.subject_id && ` #${entry.subject_id}`}
                        </span>
                      </div>
                      <p className="text-sm text-muted-foreground">{entry.description}</p>
                      {entry.properties?.attributes && (
                        <div className="mt-2 text-xs font-mono bg-muted p-2 rounded max-h-20 overflow-y-auto">
                          {Object.entries(entry.properties.attributes).map(([key, val]) => (
                            <div key={key}>
                              <span className="text-muted-foreground">{key}:</span>{' '}
                              <span>{String(val)}</span>
                              {entry.properties.old?.[key] !== undefined && (
                                <span className="text-muted-foreground">
                                  {' '}(was: {String(entry.properties.old[key])})
                                </span>
                              )}
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                    <div className="text-right shrink-0 ml-4">
                      <p className="text-sm font-medium">
                        {entry.causer?.full_name || 'System'}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        {formatDistanceToNow(new Date(entry.created_at), { addSuffix: true })}
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}

            {(auditData?.meta.last_page ?? 1) > 1 && (
              <div className="flex items-center justify-center gap-2 pt-4">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page <= 1}
                  onClick={() => setPage(page - 1)}
                >
                  Previous
                </Button>
                <span className="text-sm text-muted-foreground">
                  Page {page} of {auditData?.meta.last_page ?? 1}
                </span>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page >= (auditData?.meta.last_page ?? 1)}
                  onClick={() => setPage(page + 1)}
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
