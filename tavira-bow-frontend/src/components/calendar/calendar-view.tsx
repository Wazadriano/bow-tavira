'use client'

import { useState, useMemo } from 'react'
import {
  format,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  isSameMonth,
  isSameDay,
  addMonths,
  subMonths,
  startOfWeek,
  endOfWeek,
  isToday,
  isValid,
} from 'date-fns'
import { enUS } from 'date-fns/locale'
import {
  ChevronLeft,
  ChevronRight,
  Circle,
  Clock,
  PauseCircle,
  CheckCircle2,
  AlertTriangle,
  PlayCircle,
  CircleOff,
  LogOut,
  type LucideIcon,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { cn } from '@/lib/utils'

export interface CalendarEvent {
  id: number
  title: string
  date: string
  status?: 'blue' | 'green' | 'amber' | 'red'
  workStatus?: 'not_started' | 'in_progress' | 'on_hold' | 'completed' | 'overdue' | string
  type?: string
  href?: string
  /** POC parity: 'milestone' shows in pink, 'task' uses RAG/department styling */
  eventKind?: 'task' | 'milestone'
}

/** POC icons preserved + additional statuses added */
const workStatusIcons: Record<string, { icon: LucideIcon; className: string }> = {
  not_started: { icon: Circle, className: 'h-3 w-3 text-muted-foreground' },
  in_progress: { icon: Clock, className: 'h-3 w-3 text-blue-500' },
  on_hold: { icon: PauseCircle, className: 'h-3 w-3 text-amber-500' },
  completed: { icon: CheckCircle2, className: 'h-3 w-3 text-green-500' },
  overdue: { icon: AlertTriangle, className: 'h-3 w-3 text-red-500' },
  active: { icon: PlayCircle, className: 'h-3 w-3 text-green-500' },
  inactive: { icon: CircleOff, className: 'h-3 w-3 text-muted-foreground' },
  pending: { icon: Clock, className: 'h-3 w-3 text-amber-500' },
  exited: { icon: LogOut, className: 'h-3 w-3 text-red-500' },
}

interface CalendarViewProps {
  title: string
  events: CalendarEvent[]
  onEventClick?: (event: CalendarEvent) => void
}

const statusColors: Record<string, string> = {
  blue: 'bg-sky-500',
  green: 'bg-green-500',
  amber: 'bg-amber-500',
  red: 'bg-red-500',
}

/** POC: milestones use pink to distinguish from tasks */
const MILESTONE_DOT = 'bg-pink-500'

const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

export function CalendarView({ title, events, onEventClick }: CalendarViewProps) {
  const [currentDate, setCurrentDate] = useState(new Date())

  const days = useMemo(() => {
    const start = startOfWeek(startOfMonth(currentDate), { weekStartsOn: 1 })
    const end = endOfWeek(endOfMonth(currentDate), { weekStartsOn: 1 })
    return eachDayOfInterval({ start, end })
  }, [currentDate])

  const eventsByDate = useMemo(() => {
    const map = new Map<string, CalendarEvent[]>()
    events.forEach((event) => {
      if (!event.date) return // Skip events without date
      try {
        const parsedDate = new Date(event.date)
        // Double check: isNaN and isValid from date-fns
        if (isNaN(parsedDate.getTime()) || !isValid(parsedDate)) return
        const dateKey = format(parsedDate, 'yyyy-MM-dd')
        if (!map.has(dateKey)) {
          map.set(dateKey, [])
        }
        map.get(dateKey)!.push(event)
      } catch {
        // Skip events with unparseable dates
      }
    })
    return map
  }, [events])

  const goToPrevMonth = () => setCurrentDate(subMonths(currentDate, 1))
  const goToNextMonth = () => setCurrentDate(addMonths(currentDate, 1))
  const goToToday = () => setCurrentDate(new Date())

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <CardTitle>{title}</CardTitle>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={goToToday}>
            Today
          </Button>
          <Button variant="ghost" size="icon" onClick={goToPrevMonth}>
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <span className="min-w-[150px] text-center font-medium">
            {format(currentDate, 'MMMM yyyy', { locale: enUS })}
          </span>
          <Button variant="ghost" size="icon" onClick={goToNextMonth}>
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        {/* Week days header */}
        <div className="grid grid-cols-7 gap-1 mb-2">
          {weekDays.map((day) => (
            <div
              key={day}
              className="text-center text-sm font-medium text-muted-foreground py-2"
            >
              {day}
            </div>
          ))}
        </div>

        {/* Calendar grid */}
        <div className="grid grid-cols-7 gap-1">
          {days.map((day) => {
            const dateKey = format(day, 'yyyy-MM-dd')
            const dayEvents = eventsByDate.get(dateKey) || []
            const isCurrentMonth = isSameMonth(day, currentDate)
            const isCurrentDay = isToday(day)

            return (
              <div
                key={dateKey}
                className={cn(
                  'min-h-[100px] p-1 border rounded-lg transition-colors',
                  isCurrentMonth ? 'bg-card' : 'bg-muted/30',
                  isCurrentDay && 'ring-2 ring-primary'
                )}
              >
                <div
                  className={cn(
                    'text-sm font-medium mb-1',
                    !isCurrentMonth && 'text-muted-foreground',
                    isCurrentDay && 'text-primary'
                  )}
                >
                  {format(day, 'd')}
                </div>

                <div className="space-y-0.5">
                  {dayEvents.slice(0, 3).map((event) => (
                    <TooltipProvider key={event.id}>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <button
                            onClick={() => onEventClick?.(event)}
                            className="flex items-center gap-1 w-full text-left text-xs px-1 py-0.5 rounded hover:bg-muted/50 transition-colors truncate"
                          >
                            {event.workStatus && workStatusIcons[event.workStatus] && (() => {
                              const StatusIcon = workStatusIcons[event.workStatus!].icon
                              return <StatusIcon className={cn('shrink-0', workStatusIcons[event.workStatus!].className)} />
                            })()}
                            <span
                              className={cn(
                                'h-2 w-2 shrink-0 rounded-full',
                                event.eventKind === 'milestone'
                                  ? MILESTONE_DOT
                                  : event.status
                                    ? statusColors[event.status]
                                    : 'bg-primary',
                                event.eventKind !== 'milestone' &&
                                  (event.status === 'red' || event.status === 'amber') &&
                                  'animate-pulse'
                              )}
                            />
                            <span className="truncate">{event.title}</span>
                          </button>
                        </TooltipTrigger>
                        <TooltipContent>
                          <p className="font-medium">{event.title}</p>
                          {event.workStatus && (
                            <p className="text-xs text-muted-foreground capitalize">
                              {event.workStatus.replace(/_/g, ' ')}
                            </p>
                          )}
                          {event.type && (
                            <p className="text-xs text-muted-foreground">{event.type}</p>
                          )}
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  ))}

                  {dayEvents.length > 3 && (
                    <div className="text-xs text-muted-foreground px-1">
                      +{dayEvents.length - 3} more
                    </div>
                  )}
                </div>
              </div>
            )
          })}
        </div>

        {/* Legend (POC parity: Task RAG + Milestone in pink + status icons) */}
        <div className="mt-4 space-y-2 text-sm">
          <div className="flex flex-wrap items-center gap-4">
            <span className="text-muted-foreground">Legend:</span>
            <div className="flex items-center gap-1">
              <div className="w-3 h-3 rounded-full bg-pink-500" />
              <span>Milestone</span>
            </div>
            <div className="flex items-center gap-1">
              <div className="w-3 h-3 rounded-full bg-sky-500" />
              <span>Blue</span>
            </div>
            <div className="flex items-center gap-1">
              <div className="w-3 h-3 rounded-full bg-green-500" />
              <span>Green</span>
            </div>
            <div className="flex items-center gap-1">
              <div className="w-3 h-3 rounded-full bg-amber-500" />
              <span>Amber</span>
            </div>
            <div className="flex items-center gap-1">
              <div className="w-3 h-3 rounded-full bg-red-500" />
              <span>Red</span>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <span className="text-muted-foreground">Status:</span>
            <div className="flex items-center gap-1">
              <Circle className="h-3 w-3 text-muted-foreground" />
              <span>Not Started</span>
            </div>
            <div className="flex items-center gap-1">
              <Clock className="h-3 w-3 text-blue-500" />
              <span>In Progress</span>
            </div>
            <div className="flex items-center gap-1">
              <PauseCircle className="h-3 w-3 text-amber-500" />
              <span>On Hold</span>
            </div>
            <div className="flex items-center gap-1">
              <CheckCircle2 className="h-3 w-3 text-green-500" />
              <span>Completed</span>
            </div>
            <div className="flex items-center gap-1">
              <AlertTriangle className="h-3 w-3 text-red-500" />
              <span>Overdue</span>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}
