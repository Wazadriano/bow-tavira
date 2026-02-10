'use client'

import { useState, useEffect } from 'react'
import { Plus, X, ArrowRight, Link2, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { RAGBadge } from '@/components/shared/rag-badge'
import { useWorkItemsStore } from '@/stores/workitems'
import { toast } from 'sonner'
import Link from 'next/link'
import type { WorkItem } from '@/types'

interface DependenciesPanelProps {
  workItemId: number
  currentDependencies: WorkItem[]
}

export function DependenciesPanel({
  workItemId,
  currentDependencies,
}: DependenciesPanelProps) {
  const [isOpen, setIsOpen] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [availableItems, setAvailableItems] = useState<WorkItem[]>([])
  const [selectedId, setSelectedId] = useState<string>('')

  const { items, fetchItems, addDependency, removeDependency } = useWorkItemsStore()

  useEffect(() => {
    // Filter out current item and already added dependencies
    const dependencyIds = currentDependencies.map((d) => d.id)
    const available = items.filter(
      (item) => item.id !== workItemId && !dependencyIds.includes(item.id)
    )
    setAvailableItems(available)
  }, [items, workItemId, currentDependencies])

  const handleOpenChange = async (open: boolean) => {
    if (open && items.length === 0) {
      await fetchItems(1)
    }
    setIsOpen(open)
  }

  const handleAddDependency = async () => {
    if (!selectedId) return

    setIsLoading(true)
    try {
      await addDependency(workItemId, Number(selectedId))
      toast.success('Dependency added')
      setSelectedId('')
      setIsOpen(false)
    } catch {
      toast.error('Error adding dependency')
    } finally {
      setIsLoading(false)
    }
  }

  const handleRemoveDependency = async (dependencyId: number) => {
    try {
      await removeDependency(workItemId, dependencyId)
      toast.success('Dependency removed')
    } catch {
      toast.error('Error removing dependency')
    }
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="flex items-center gap-2">
          <Link2 className="h-5 w-5" />
          Dependencies
          {currentDependencies.length > 0 && (
            <Badge variant="secondary" className="ml-2">
              {currentDependencies.length}
            </Badge>
          )}
        </CardTitle>
        <Dialog open={isOpen} onOpenChange={handleOpenChange}>
          <DialogTrigger asChild>
            <Button variant="outline" size="sm">
              <Plus className="h-4 w-4 mr-1" />
              Add
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Add a dependency</DialogTitle>
              <DialogDescription>
                This work item will depend on the selected item (must be completed first)
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <Select value={selectedId} onValueChange={setSelectedId}>
                <SelectTrigger>
                  <SelectValue placeholder="Select a work item..." />
                </SelectTrigger>
                <SelectContent>
                  {availableItems.map((item) => (
                    <SelectItem key={item.id} value={String(item.id)}>
                      <div className="flex items-center gap-2">
                        <RAGBadge status={item.rag_status} />
                        <span className="font-mono text-xs">{item.ref_no}</span>
                        <span className="truncate max-w-[200px]">{item.description}</span>
                      </div>
                    </SelectItem>
                  ))}
                  {availableItems.length === 0 && (
                    <div className="p-2 text-sm text-muted-foreground text-center">
                      No work items available
                    </div>
                  )}
                </SelectContent>
              </Select>
              <div className="flex justify-end gap-2">
                <Button variant="outline" onClick={() => setIsOpen(false)}>
                  Cancel
                </Button>
                <Button onClick={handleAddDependency} disabled={!selectedId || isLoading}>
                  {isLoading && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                  Add
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </CardHeader>
      <CardContent>
        {currentDependencies.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-4">
            No dependencies configured
          </p>
        ) : (
          <div className="space-y-2">
            {currentDependencies.map((dep) => (
              <div
                key={dep.id}
                className="flex items-center justify-between p-3 rounded-lg border bg-muted/30 group"
              >
                <div className="flex items-center gap-3">
                  <RAGBadge status={dep.rag_status} />
                  <div className="flex-1 min-w-0">
                    <Link
                      href={`/tasks/${dep.id}`}
                      className="font-medium hover:underline flex items-center gap-2"
                    >
                      <span className="font-mono text-xs text-muted-foreground">
                        {dep.ref_no}
                      </span>
                      <ArrowRight className="h-3 w-3 text-muted-foreground" />
                      <span className="truncate">{dep.description}</span>
                    </Link>
                    <p className="text-xs text-muted-foreground">
                      {dep.current_status === 'completed' ? (
                        <span className="text-green-600">Completed</span>
                      ) : (
                        <span className="text-amber-600">Pending</span>
                      )}
                    </p>
                  </div>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  className="opacity-0 group-hover:opacity-100 transition-opacity"
                  onClick={() => handleRemoveDependency(dep.id)}
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
