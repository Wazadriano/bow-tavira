'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { Header } from '@/components/layout/header'
import { CalendarView, type CalendarEvent } from '@/components/calendar'
import { useWorkItemsStore } from '@/stores/workitems'
import { safeParseDate } from '@/lib/utils'

export default function TasksCalendarPage() {
  const router = useRouter()
  const { items, fetchItems, isLoading } = useWorkItemsStore()
  const [events, setEvents] = useState<CalendarEvent[]>([])

  useEffect(() => {
    fetchItems(1)
  }, [fetchItems])

  useEffect(() => {
    const calendarEvents: CalendarEvent[] = items
      .filter((item) => item.deadline && safeParseDate(item.deadline))
      .map((item) => ({
        id: item.id,
        title: item.description || `Task #${item.id}`,
        date: item.deadline!,
        status: item.rag_status?.toLowerCase() as 'blue' | 'green' | 'amber' | 'red',
        type: item.department,
        href: `/tasks/${item.id}`,
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
        description="Calendar view of due dates"
      />

      <div className="p-6">
        {isLoading ? (
          <div className="animate-pulse">
            <div className="h-[600px] bg-muted rounded-lg" />
          </div>
        ) : (
          <CalendarView
            title="Work Item Due Dates"
            events={events}
            onEventClick={handleEventClick}
          />
        )}
      </div>
    </>
  )
}
