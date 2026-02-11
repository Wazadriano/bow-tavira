'use client'

import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useRouter } from 'next/navigation'
import { Header } from '@/components/layout/header'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { get } from '@/lib/api'
import { cn } from '@/lib/utils'
import type { WorkItem, PaginatedResponse } from '@/types'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'

function getStatusColor(status: string | null): string {
  switch (status) {
    case 'completed': return 'bg-green-500'
    case 'in_progress': return 'bg-blue-500'
    case 'on_hold': return 'bg-amber-500'
    default: return 'bg-gray-400'
  }
}

function getRagColor(rag: string | null): string {
  switch (rag) {
    case 'green': return 'bg-green-500'
    case 'amber': return 'bg-amber-500'
    case 'red': return 'bg-red-500'
    case 'blue': return 'bg-blue-500'
    default: return 'bg-gray-400'
  }
}

export default function GanttPage() {
  const router = useRouter()

  const { data, isLoading } = useQuery({
    queryKey: ['workitems-gantt'],
    queryFn: () => get<PaginatedResponse<WorkItem>>('/workitems?per_page=100'),
  })

  const items = data?.data || []

  const { timeline, months, startDate } = useMemo(() => {
    if (items.length === 0) {
      return { timeline: [], months: [], startDate: new Date() }
    }

    const now = new Date()
    const dates = items
      .filter((item) => item.deadline)
      .map((item) => new Date(item.deadline!))

    if (dates.length === 0) {
      return { timeline: items, months: [], startDate: now }
    }

    const minDate = new Date(Math.min(now.getTime(), ...dates.map((d) => d.getTime())))
    const maxDate = new Date(Math.max(...dates.map((d) => d.getTime())))

    // Add 1 month padding on each side
    const start = new Date(minDate.getFullYear(), minDate.getMonth() - 1, 1)
    const end = new Date(maxDate.getFullYear(), maxDate.getMonth() + 2, 0)

    const monthList: { label: string; start: Date; end: Date }[] = []
    const cursor = new Date(start)
    while (cursor <= end) {
      const monthStart = new Date(cursor)
      const monthEnd = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0)
      monthList.push({
        label: cursor.toLocaleDateString('en-US', { month: 'short', year: '2-digit' }),
        start: monthStart,
        end: monthEnd,
      })
      cursor.setMonth(cursor.getMonth() + 1)
    }

    return { timeline: items, months: monthList, startDate: start }
  }, [items])

  const totalDays = useMemo(() => {
    if (months.length === 0) return 1
    const lastMonth = months[months.length - 1]
    return Math.max(1, Math.ceil((lastMonth.end.getTime() - startDate.getTime()) / 86400000))
  }, [months, startDate])

  const getBarPosition = (item: WorkItem) => {
    const created = new Date(item.created_at)
    const deadline = item.deadline ? new Date(item.deadline) : new Date()

    const startOffset = Math.max(0, (created.getTime() - startDate.getTime()) / 86400000)
    const duration = Math.max(7, (deadline.getTime() - created.getTime()) / 86400000)

    const left = (startOffset / totalDays) * 100
    const width = Math.min((duration / totalDays) * 100, 100 - left)

    return { left: `${left}%`, width: `${Math.max(width, 1)}%` }
  }

  return (
    <div className="flex flex-col h-full">
      <Header title="Gantt Chart" description="Timeline view of work items" />

      <div className="flex-1 overflow-auto p-6">
        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
          </div>
        ) : items.length === 0 ? (
          <div className="flex h-64 items-center justify-center text-muted-foreground">
            No work items to display
          </div>
        ) : (
          <Card>
            <CardContent className="p-0">
              {/* Month headers */}
              <div className="flex border-b sticky top-0 bg-card z-10">
                <div className="w-64 shrink-0 border-r p-3 font-semibold text-sm">
                  Task
                </div>
                <div className="flex-1 flex">
                  {months.map((month, i) => (
                    <div
                      key={i}
                      className="flex-1 border-r p-2 text-center text-xs font-medium text-muted-foreground"
                      style={{ minWidth: '80px' }}
                    >
                      {month.label}
                    </div>
                  ))}
                </div>
              </div>

              {/* Today marker */}
              <div className="relative">
                {/* Rows */}
                <TooltipProvider>
                  {timeline.map((item) => {
                    const position = item.deadline ? getBarPosition(item) : null
                    return (
                      <div
                        key={item.id}
                        className="flex border-b hover:bg-muted/30 transition-colors cursor-pointer"
                        onClick={() => router.push(`/tasks/${item.id}`)}
                      >
                        <div className="w-64 shrink-0 border-r p-2 flex items-center gap-2">
                          <span className="text-xs font-mono text-muted-foreground">
                            {item.ref_no}
                          </span>
                          <span className="text-sm truncate flex-1">
                            {item.description?.slice(0, 30)}
                          </span>
                        </div>
                        <div className="flex-1 relative" style={{ minHeight: '36px' }}>
                          {position && (
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <div
                                  className={cn(
                                    'absolute top-1/2 -translate-y-1/2 h-6 rounded-md',
                                    item.rag_status ? getRagColor(item.rag_status) : getStatusColor(item.current_status),
                                    'opacity-80 hover:opacity-100 transition-opacity'
                                  )}
                                  style={{
                                    left: position.left,
                                    width: position.width,
                                    minWidth: '4px',
                                  }}
                                />
                              </TooltipTrigger>
                              <TooltipContent>
                                <div className="text-xs space-y-1">
                                  <p className="font-medium">{item.ref_no}</p>
                                  <p>{item.description?.slice(0, 60)}</p>
                                  <p>Status: {item.current_status || 'N/A'}</p>
                                  <p>Deadline: {item.deadline || 'N/A'}</p>
                                  <p>Owner: {item.responsible_party?.full_name || 'N/A'}</p>
                                </div>
                              </TooltipContent>
                            </Tooltip>
                          )}
                        </div>
                      </div>
                    )
                  })}
                </TooltipProvider>
              </div>

              {/* Legend */}
              <div className="flex items-center gap-4 p-3 border-t text-xs text-muted-foreground">
                <div className="flex items-center gap-1">
                  <div className="h-3 w-3 rounded bg-green-500" /> Green
                </div>
                <div className="flex items-center gap-1">
                  <div className="h-3 w-3 rounded bg-amber-500" /> Amber
                </div>
                <div className="flex items-center gap-1">
                  <div className="h-3 w-3 rounded bg-red-500" /> Red
                </div>
                <div className="flex items-center gap-1">
                  <div className="h-3 w-3 rounded bg-blue-500" /> Blue
                </div>
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  )
}
