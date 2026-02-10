'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { Header } from '@/components/layout/header'
import { CalendarView, type CalendarEvent } from '@/components/calendar'
import { useSuppliersStore } from '@/stores/suppliers'
import { safeParseDate } from '@/lib/utils'

export default function SuppliersCalendarPage() {
  const router = useRouter()
  const { contracts, isLoading } = useSuppliersStore()
  const [events, setEvents] = useState<CalendarEvent[]>([])

  useEffect(() => {
    // Create calendar events from contracts end dates
    const calendarEvents: CalendarEvent[] = []

    // Add contract end dates from the store's contracts array
    contracts.forEach((contract) => {
      const endDate = safeParseDate(contract.end_date)
      if (endDate && contract.end_date) {
        const now = new Date()
        const daysUntilEnd = Math.ceil(
          (endDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
        )

        let status: 'blue' | 'green' | 'amber' | 'red' = 'green'
        if (daysUntilEnd < 0) status = 'red'
        else if (daysUntilEnd <= 30) status = 'amber'
        else if (daysUntilEnd <= 90) status = 'blue'

        calendarEvents.push({
          id: contract.id,
          title: contract.description || contract.contract_ref || `Contract #${contract.id}`,
          date: contract.end_date,
          status,
          type: 'Contract End',
          href: `/suppliers/${contract.supplier_id}`,
        })
      }
    })

    setEvents(calendarEvents)
  }, [contracts])

  const handleEventClick = (event: CalendarEvent) => {
    if (event.href) {
      router.push(event.href)
    }
  }

  return (
    <>
      <Header
        title="Calendar"
        description="Calendar view of contract due dates"
      />

      <div className="p-6">
        {isLoading ? (
          <div className="animate-pulse">
            <div className="h-[600px] bg-muted rounded-lg" />
          </div>
        ) : (
          <CalendarView
            title="Contract Due Dates"
            events={events}
            onEventClick={handleEventClick}
          />
        )}
      </div>
    </>
  )
}
