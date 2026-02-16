import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Header } from '@/components/layout/header'
import { CalendarView, type CalendarEvent } from '@/components/calendar'
import { useGovernanceStore } from '@/stores/governance'
import { safeParseDate } from '@/lib/utils'
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
  { value: 'completed', label: 'Completed' },
]

const RAG_OPTIONS = [
  { value: 'all', label: 'All RAG' },
  { value: 'blue', label: 'Blue' },
  { value: 'green', label: 'Green' },
  { value: 'amber', label: 'Amber' },
  { value: 'red', label: 'Red' },
]

const FREQUENCY_OPTIONS = [
  { value: 'all', label: 'All Frequency' },
  { value: 'daily', label: 'Daily' },
  { value: 'weekly', label: 'Weekly' },
  { value: 'monthly', label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
  { value: 'annually', label: 'Annually' },
]

interface GovernanceCalendarEvent extends CalendarEvent {
  _department?: string
  _frequency?: string | null
}

export default function GovernanceCalendarPage() {
  const navigate = useNavigate()
  const { items, fetchItems, isLoading } = useGovernanceStore()
  const [statusFilter, setStatusFilter] = useState('all')
  const [ragFilter, setRagFilter] = useState('all')
  const [frequencyFilter, setFrequencyFilter] = useState('all')
  const [departmentFilter, setDepartmentFilter] = useState('all')

  useEffect(() => {
    fetchItems(1)
  }, [fetchItems])

  const departments = useMemo(() => {
    const depts = new Set(items.map((i) => i.department).filter(Boolean))
    return Array.from(depts).sort()
  }, [items])

  const events: GovernanceCalendarEvent[] = useMemo(() => {
    return items
      .filter((item) => item.deadline && safeParseDate(item.deadline))
      .map((item) => ({
        id: item.id,
        title: item.activity || `Governance #${item.id}`,
        date: item.deadline!,
        status: item.rag_status?.toLowerCase() as 'blue' | 'green' | 'amber' | 'red',
        workStatus: item.current_status || undefined,
        type: item.frequency || undefined,
        href: `/governance/${item.id}`,
        _department: item.department,
        _frequency: item.frequency,
      }))
  }, [items])

  const filteredEvents = useMemo(() => {
    return events.filter((event) => {
      if (statusFilter !== 'all' && event.workStatus !== statusFilter) return false
      if (ragFilter !== 'all' && event.status !== ragFilter) return false
      if (departmentFilter !== 'all' && event._department !== departmentFilter) return false
      if (frequencyFilter !== 'all' && event._frequency !== frequencyFilter) return false
      return true
    })
  }, [events, statusFilter, ragFilter, departmentFilter, frequencyFilter])

  const handleEventClick = (event: CalendarEvent) => {
    if (event.href) {
      navigate(event.href)
    }
  }

  return (
    <>
      <Header
        title="Calendar"
        description="Calendar view of governance due dates"
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

          <Select value={frequencyFilter} onValueChange={setFrequencyFilter}>
            <SelectTrigger className="w-[150px]">
              <SelectValue placeholder="Frequency" />
            </SelectTrigger>
            <SelectContent>
              {FREQUENCY_OPTIONS.map((option) => (
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
            title="Governance Due Dates"
            events={filteredEvents}
            onEventClick={handleEventClick}
          />
        )}
      </div>
    </>
  )
}
