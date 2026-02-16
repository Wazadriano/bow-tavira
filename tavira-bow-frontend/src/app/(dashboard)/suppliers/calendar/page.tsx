import { useEffect, useState, useMemo } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Header } from '@/components/layout/header'
import { CalendarView, type CalendarEvent } from '@/components/calendar'
import { get } from '@/lib/api'
import { safeParseDate } from '@/lib/utils'
import type { Supplier, PaginatedResponse, SupplierContract } from '@/types'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const STATUS_OPTIONS = [
  { value: 'all', label: 'All Status' },
  { value: 'active', label: 'Active' },
  { value: 'expired', label: 'Expired' },
  { value: 'pending', label: 'Pending' },
]

const LOCATION_OPTIONS = [
  { value: 'all', label: 'All Locations' },
  { value: 'local', label: 'Local' },
  { value: 'overseas', label: 'Overseas' },
]

interface SupplierCalendarEvent extends CalendarEvent {
  _contractStatus?: string | null
  _location?: string | null
}

export default function SuppliersCalendarPage() {
  const navigate = useNavigate()
  const [events, setEvents] = useState<SupplierCalendarEvent[]>([])
  const [statusFilter, setStatusFilter] = useState('all')
  const [locationFilter, setLocationFilter] = useState('all')

  const { data: suppliersData, isLoading } = useQuery({
    queryKey: ['suppliers-calendar'],
    queryFn: () => get<PaginatedResponse<Supplier & { contracts?: SupplierContract[] }>>('/suppliers?per_page=100'),
  })

  useEffect(() => {
    if (!suppliersData?.data) return

    const calendarEvents: SupplierCalendarEvent[] = []

    suppliersData.data.forEach((supplier) => {
      const contracts = (supplier as Supplier & { contracts?: SupplierContract[] }).contracts || []
      contracts.forEach((contract) => {
        const endDate = safeParseDate(contract.end_date)
        if (endDate && contract.end_date) {
          const now = new Date()
          const daysUntilEnd = Math.ceil(
            (endDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
          )

          let ragStatus: 'blue' | 'green' | 'amber' | 'red' = 'green'
          if (daysUntilEnd < 0) ragStatus = 'red'
          else if (daysUntilEnd <= 30) ragStatus = 'amber'
          else if (daysUntilEnd <= 90) ragStatus = 'blue'

          calendarEvents.push({
            id: contract.id,
            title: contract.description || contract.contract_ref || `Contract #${contract.id}`,
            date: contract.end_date,
            status: ragStatus,
            type: 'Contract End',
            href: `/suppliers/${contract.supplier_id}`,
            _contractStatus: contract.status,
            _location: supplier.location,
          })
        }
      })
    })

    setEvents(calendarEvents)
  }, [suppliersData])

  const filteredEvents = useMemo(() => {
    return events.filter((event) => {
      if (statusFilter !== 'all' && event._contractStatus !== statusFilter) return false
      if (locationFilter !== 'all' && event._location !== locationFilter) return false
      return true
    })
  }, [events, statusFilter, locationFilter])

  const handleEventClick = (event: CalendarEvent) => {
    if (event.href) {
      navigate(event.href)
    }
  }

  return (
    <>
      <Header
        title="Calendar"
        description="Calendar view of contract due dates"
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

          <Select value={locationFilter} onValueChange={setLocationFilter}>
            <SelectTrigger className="w-[140px]">
              <SelectValue placeholder="Location" />
            </SelectTrigger>
            <SelectContent>
              {LOCATION_OPTIONS.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
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
            title="Contract Due Dates"
            events={filteredEvents}
            onEventClick={handleEventClick}
          />
        )}
      </div>
    </>
  )
}
