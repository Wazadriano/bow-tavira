'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
import { Header } from '@/components/layout/header'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { RAGBadge } from '@/components/shared/rag-badge'
import { UserAvatarWithName } from '@/components/ui/user-avatar'
import { get } from '@/lib/api'
import { formatDate, cn } from '@/lib/utils'
import { useWorkItemsStore } from '@/stores/workitems'
import type { WorkItem, PaginatedResponse } from '@/types'

const COLUMNS = [
  { id: 'not_started', label: 'Not Started', color: 'bg-gray-100 dark:bg-gray-800' },
  { id: 'in_progress', label: 'In Progress', color: 'bg-blue-50 dark:bg-blue-950' },
  { id: 'on_hold', label: 'On Hold', color: 'bg-amber-50 dark:bg-amber-950' },
  { id: 'completed', label: 'Completed', color: 'bg-green-50 dark:bg-green-950' },
]

export default function KanbanPage() {
  const router = useRouter()
  const { updateStatus } = useWorkItemsStore()
  const [draggedItem, setDraggedItem] = useState<number | null>(null)
  const [dragOverColumn, setDragOverColumn] = useState<string | null>(null)

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['workitems-kanban'],
    queryFn: () => get<PaginatedResponse<WorkItem>>('/workitems?per_page=100'),
  })

  const items = data?.data || []

  const getColumnItems = (status: string) =>
    items.filter((item) => {
      const itemStatus = item.current_status || 'not_started'
      return itemStatus === status
    })

  const handleDragStart = (e: React.DragEvent, itemId: number) => {
    setDraggedItem(itemId)
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', String(itemId))
  }

  const handleDragOver = (e: React.DragEvent, columnId: string) => {
    e.preventDefault()
    e.dataTransfer.dropEffect = 'move'
    setDragOverColumn(columnId)
  }

  const handleDragLeave = () => {
    setDragOverColumn(null)
  }

  const handleDrop = async (e: React.DragEvent, newStatus: string) => {
    e.preventDefault()
    setDragOverColumn(null)
    const itemId = parseInt(e.dataTransfer.getData('text/plain'), 10)
    if (!itemId) return

    const item = items.find((i) => i.id === itemId)
    if (!item || item.current_status === newStatus) return

    try {
      await updateStatus(itemId, newStatus)
      refetch()
    } catch {
      // Error handled by store
    }
    setDraggedItem(null)
  }

  return (
    <div className="flex flex-col h-full">
      <Header title="Kanban Board" description="Drag and drop tasks between columns" />

      <div className="flex-1 overflow-x-auto p-6">
        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
          </div>
        ) : (
          <div className="flex gap-4 h-full min-h-[600px]">
            {COLUMNS.map((column) => {
              const columnItems = getColumnItems(column.id)
              return (
                <div
                  key={column.id}
                  className={cn(
                    'flex-1 min-w-[280px] rounded-lg p-3 transition-colors',
                    column.color,
                    dragOverColumn === column.id && 'ring-2 ring-primary ring-offset-2'
                  )}
                  onDragOver={(e) => handleDragOver(e, column.id)}
                  onDragLeave={handleDragLeave}
                  onDrop={(e) => handleDrop(e, column.id)}
                >
                  <div className="mb-3 flex items-center justify-between">
                    <h3 className="text-sm font-semibold">{column.label}</h3>
                    <Badge variant="secondary" className="text-xs">
                      {columnItems.length}
                    </Badge>
                  </div>

                  <div className="space-y-2">
                    {columnItems.map((item) => (
                      <Card
                        key={item.id}
                        draggable
                        onDragStart={(e) => handleDragStart(e, item.id)}
                        onDragEnd={() => setDraggedItem(null)}
                        className={cn(
                          'cursor-grab active:cursor-grabbing transition-opacity',
                          draggedItem === item.id && 'opacity-50'
                        )}
                        onClick={() => router.push(`/tasks/${item.id}`)}
                      >
                        <CardContent className="p-3">
                          <div className="flex items-start justify-between gap-2">
                            <span className="text-xs font-mono text-muted-foreground">
                              {item.ref_no}
                            </span>
                            <RAGBadge status={item.rag_status} />
                          </div>
                          <p className="mt-1 text-sm font-medium line-clamp-2">
                            {item.description}
                          </p>
                          <div className="mt-2 flex items-center justify-between">
                            <UserAvatarWithName
                              name={item.responsible_party?.full_name}
                              size="sm"
                            />
                            {item.deadline && (
                              <span className="text-xs text-muted-foreground">
                                {formatDate(item.deadline)}
                              </span>
                            )}
                          </div>
                          {item.priority_item && (
                            <Badge variant="destructive" className="mt-2 text-xs">
                              Priority
                            </Badge>
                          )}
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                </div>
              )
            })}
          </div>
        )}
      </div>
    </div>
  )
}
