'use client'

import { useState, useEffect } from 'react'
import { Plus, Trash2, Loader2, Milestone } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { RAGBadge } from '@/components/shared/rag-badge'
import { useWorkItemsStore } from '@/stores/workitems'
import { formatDate } from '@/lib/utils'
import { toast } from 'sonner'

interface MilestonesPanelProps {
  workItemId: number
}

export function MilestonesPanel({ workItemId }: MilestonesPanelProps) {
  const [isOpen, setIsOpen] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [dueDate, setDueDate] = useState('')

  const { milestones, fetchMilestones, createMilestone, updateMilestone, deleteMilestone } =
    useWorkItemsStore()

  useEffect(() => {
    fetchMilestones(workItemId)
  }, [workItemId, fetchMilestones])

  const handleCreate = async () => {
    if (!title.trim()) return

    setIsLoading(true)
    try {
      await createMilestone(workItemId, {
        title: title.trim(),
        description: description.trim() || undefined,
        due_date: dueDate || undefined,
      })
      toast.success('Milestone created')
      setTitle('')
      setDescription('')
      setDueDate('')
      setIsOpen(false)
    } catch {
      toast.error('Error creating milestone')
    } finally {
      setIsLoading(false)
    }
  }

  const handleToggleCompleted = async (milestoneId: number, isCompleted: boolean) => {
    try {
      await updateMilestone(milestoneId, { is_completed: !isCompleted } as never)
      toast.success(isCompleted ? 'Milestone reopened' : 'Milestone completed')
    } catch {
      toast.error('Error updating milestone')
    }
  }

  const handleDelete = async (milestoneId: number) => {
    try {
      await deleteMilestone(milestoneId)
      toast.success('Milestone deleted')
    } catch {
      toast.error('Error deleting milestone')
    }
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="flex items-center gap-2">
          <Milestone className="h-5 w-5" />
          Milestones
          {milestones.length > 0 && (
            <Badge variant="secondary" className="ml-2">
              {milestones.length}
            </Badge>
          )}
        </CardTitle>
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
          <DialogTrigger asChild>
            <Button variant="outline" size="sm">
              <Plus className="h-4 w-4 mr-1" />
              Add
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Add Milestone</DialogTitle>
              <DialogDescription>
                Create a milestone to track progress on this work item
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label htmlFor="milestone-title">Title *</Label>
                <Input
                  id="milestone-title"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  placeholder="Milestone title"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="milestone-description">Description</Label>
                <textarea
                  id="milestone-description"
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  rows={2}
                  className="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  placeholder="Optional description"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="milestone-due-date">Due Date</Label>
                <Input
                  id="milestone-due-date"
                  type="date"
                  value={dueDate}
                  onChange={(e) => setDueDate(e.target.value)}
                />
              </div>
              <div className="flex justify-end gap-2">
                <Button variant="outline" onClick={() => setIsOpen(false)}>
                  Cancel
                </Button>
                <Button onClick={handleCreate} disabled={!title.trim() || isLoading}>
                  {isLoading && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                  Add
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </CardHeader>
      <CardContent>
        {milestones.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-4">
            No milestones configured
          </p>
        ) : (
          <div className="space-y-2">
            {milestones.map((milestone) => (
              <div
                key={milestone.id}
                className="flex items-center justify-between p-3 rounded-lg border bg-muted/30 group"
              >
                <div className="flex items-center gap-3">
                  <input
                    type="checkbox"
                    checked={milestone.is_completed}
                    onChange={() => handleToggleCompleted(milestone.id, milestone.is_completed)}
                    className="h-4 w-4 rounded border-gray-300"
                  />
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span
                        className={
                          milestone.is_completed
                            ? 'line-through text-muted-foreground'
                            : 'font-medium'
                        }
                      >
                        {milestone.title}
                      </span>
                      {milestone.rag_status && (
                        <RAGBadge status={milestone.rag_status} />
                      )}
                    </div>
                    {milestone.due_date && (
                      <p className="text-xs text-muted-foreground">
                        Due: {formatDate(milestone.due_date)}
                      </p>
                    )}
                  </div>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  className="opacity-0 group-hover:opacity-100 transition-opacity"
                  onClick={() => handleDelete(milestone.id)}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
