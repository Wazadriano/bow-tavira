import { useEffect, useState, useMemo } from 'react'
import { useNavigate } from 'react-router-dom'
import { Header } from '@/components/layout/header'
import { CalendarView, type CalendarEvent } from '@/components/calendar'
import { WorkItemQuickView } from '@/components/workitems/quick-view'
import { useWorkItemsStore } from '@/stores/workitems'
import { api } from '@/lib/api'
import { safeParseDate } from '@/lib/utils'
import type { TaskMilestone } from '@/types'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const STATUS_OPTIONS = [
  { value: 'all', label: 'All Status' },
  { value: 'not_started', label: 'Not Started' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'on_hold', label: 'On Hold' },
  { value: 'completed', label: 'Completed' },
]

const RAG_OPTIONS = [
  { value: 'all', label: 'All RAG' },
  { value: 'blue', label: 'Blue' },
  { value: 'green', label: 'Green' },
  { value: 'amber', label: 'Amber' },
  { value: 'red', label: 'Red' },
]

export default function TasksCalendarPage() {
  const navigate = useNavigate()
  const { items, fetchItems, isLoading } = useWorkItemsStore()
  const [milestones, setMilestones] = useState<TaskMilestone[]>([])
  const [events, setEvents] = useState<CalendarEvent[]>([])
  const [statusFilter, setStatusFilter] = useState('all')
  const [ragFilter, setRagFilter] = useState('all')
  const [departmentFilter, setDepartmentFilter] = useState('all')
  const [quickViewId, setQuickViewId] = useState<number | null>(null)
  const [quickViewOpen, setQuickViewOpen] = useState(false)

  useEffect(() => {
    fetchItems(1)
  }, [fetchItems])

  useEffect(() => {
    api
      .get<TaskMilestone[]>('/milestones')
      .then((res) => setMilestones(Array.isArray(res.data) ? res.data : []))
      .catch(() => setMilestones([]))
  }, [])

  const departments = useMemo(() => {
    const depts = new Set(items.map((i) => i.department).filter(Boolean))
    return Array.from(depts).sort()
  }, [items])

  useEffect(() => {
    const taskEvents: CalendarEvent[] = items
      .filter((item) => item.deadline && safeParseDate(item.deadline))
      .map((item) => ({
        id: item.id,
        title: item.description || `Task #${item.id}`,
        date: item.deadline!,
        status: item.rag_status?.toLowerCase() as 'blue' | 'green' | 'amber' | 'red',
        workStatus: item.current_status || undefined,
        type: item.department,
        href: `/tasks/${item.id}`,
        eventKind: 'task' as const,
      }))
    const milestoneEvents: CalendarEvent[] = milestones
      .filter((m) => m.due_date && safeParseDate(m.due_date))
      .map((m) => ({
        id: 1000000 + m.id,
        title: `🎯 ${m.title}`,
        date: m.due_date!,
        workStatus: m.status || undefined,
        type: undefined,
        href: `/tasks/${m.work_item_id}`,
        eventKind: 'milestone' as const,
      }))
    setEvents([...taskEvents, ...milestoneEvents])
  }, [items, milestones])

  const filteredEvents = useMemo(() => {
    return events.filter((event) => {
      if (statusFilter !== 'all' && event.workStatus !== statusFilter) return false
      if (ragFilter !== 'all' && event.status !== ragFilter) return false
      if (departmentFilter !== 'all' && event.type !== departmentFilter) return false
      return true
    })
  }, [events, statusFilter, ragFilter, departmentFilter])

  const handleEventClick = (event: CalendarEvent) => {
    if (event.eventKind === 'milestone' && event.href) {
      navigate(event.href)
      return
    }
    setQuickViewId(event.id)
    setQuickViewOpen(true)
  }

  return (
    <>
      <Header
        title="Calendar"
        description="Calendar view of due dates"
      />

      <div className="p-6">
        <div className="mb-4 flex flex-wrap items-center gap-3">
          <Select value={statusFilter} onValueChange={setStatusFilter}>
            <SelectTrigger className="w-[140px]">
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              {STATUS_OPTIONS.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select value={ragFilter} onValueChange={setRagFilter}>
            <SelectTrigger className="w-[120px]">
              <SelectValue placeholder="RAG" />
            </SelectTrigger>
            <SelectContent>
              {RAG_OPTIONS.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select value={departmentFilter} onValueChange={setDepartmentFilter}>
            <SelectTrigger className="w-[160px]">
              <SelectValue placeholder="Department" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Departments</SelectItem>
              {departments.map((dept) => (
                <SelectItem key={dept} value={dept}>
                  {dept}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {isLoading ? (
          <div className="animate-pulse">
            <div className="h-[600px] bg-muted rounded-lg" />
          </div>
        ) : (
          <CalendarView
            title="Work Item Due Dates"
            events={filteredEvents}
            onEventClick={handleEventClick}
          />
        )}
      </div>

      <WorkItemQuickView
        workItemId={quickViewId}
        open={quickViewOpen}
        onOpenChange={setQuickViewOpen}
      />
    </>
  )
}
