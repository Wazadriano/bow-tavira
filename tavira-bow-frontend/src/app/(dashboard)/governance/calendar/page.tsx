'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { Header } from '@/components/layout/header'
import { CalendarView, type CalendarEvent } from '@/components/calendar'
import { useGovernanceStore } from '@/stores/governance'
import { safeParseDate } from '@/lib/utils'

export default function GovernanceCalendarPage() {
  const router = useRouter()
  const { items, fetchItems, isLoading } = useGovernanceStore()
  const [events, setEvents] = useState<CalendarEvent[]>([])

  useEffect(() => {
    fetchItems(1)
  }, [fetchItems])

  useEffect(() => {
    const calendarEvents: CalendarEvent[] = items
      .filter((item) => item.deadline && safeParseDate(item.deadline))
      .map((item) => ({
        id: item.id,
        title: item.activity || `Governance #${item.id}`,
        date: item.deadline!,
        status: item.rag_status?.toLowerCase() as 'blue' | 'green' | 'amber' | 'red',
        type: item.frequency || undefined,
        href: `/governance/${item.id}`,
      }))
    setEvents(calendarEvents)
  }, [items])

  const handleEventClick = (event: CalendarEvent) => {
    if (event.href) {
      router.push(event.href)
    }
  }

  return (
    <>
      <Header
        title="Calendar"
        description="Calendar view of governance due dates"
      />

      <div className="p-6">
        {isLoading ? (
          <div className="animate-pulse">
            <div className="h-[600px] bg-muted rounded-lg" />
          </div>
        ) : (
          <CalendarView
            title="Governance Due Dates"
            events={events}
            onEventClick={handleEventClick}
          />
        )}
      </div>
    </>
  )
}
