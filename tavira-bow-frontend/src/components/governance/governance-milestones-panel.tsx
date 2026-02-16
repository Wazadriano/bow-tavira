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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useGovernanceStore } from '@/stores/governance'
import { formatDate } from '@/lib/utils'
import { toast } from 'sonner'

interface GovernanceMilestonesPanelProps {
  itemId: number
}

const STATUS_OPTIONS = [
  { value: 'not_started', label: 'Not Started' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
]

const statusColors: Record<string, string> = {
  not_started: 'bg-blue-500',
  in_progress: 'bg-amber-500',
  completed: 'bg-green-500',
}

export function GovernanceMilestonesPanel({ itemId }: GovernanceMilestonesPanelProps) {
  const [isOpen, setIsOpen] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [dueDate, setDueDate] = useState('')
  const [status, setStatus] = useState('not_started')

  const { milestones, fetchMilestones, createMilestone, updateMilestone, deleteMilestone } =
    useGovernanceStore()

  useEffect(() => {
    fetchMilestones(itemId)
  }, [itemId, fetchMilestones])

  const handleCreate = async () => {
    if (!title.trim()) return

    setIsLoading(true)
    try {
      await createMilestone(itemId, {
        title: title.trim(),
        description: description.trim() || undefined,
        due_date: dueDate || undefined,
        status: status as 'not_started' | 'in_progress' | 'completed',
      })
      toast.success('Milestone created')
      setTitle('')
      setDescription('')
      setDueDate('')
      setStatus('not_started')
      setIsOpen(false)
    } catch {
      toast.error('Error creating milestone')
    } finally {
      setIsLoading(false)
    }
  }

  const handleStatusChange = async (milestoneId: number, newStatus: string) => {
    try {
      await updateMilestone(milestoneId, { status: newStatus as never })
      toast.success('Milestone updated')
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
                Create a milestone to track progress on this governance item
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label htmlFor="gov-milestone-title">Title *</Label>
                <Input
                  id="gov-milestone-title"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  placeholder="Milestone title"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="gov-milestone-description">Description</Label>
                <textarea
                  id="gov-milestone-description"
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  rows={2}
                  className="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  placeholder="Optional description"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="gov-milestone-due-date">Due Date</Label>
                <Input
                  id="gov-milestone-due-date"
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
                <div className="flex items-center gap-3 flex-1 min-w-0">
                  <div
                    className={`h-2 w-2 shrink-0 rounded-full ${
                      (milestone.status && statusColors[milestone.status]) || 'bg-gray-500'
                    }`}
                  />
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-medium truncate">{milestone.title}</span>
                      <Badge variant="outline" className="text-xs shrink-0">
                        {STATUS_OPTIONS.find((o) => o.value === milestone.status)?.label ||
                          milestone.status}
                      </Badge>
                    </div>
                    {milestone.due_date && (
                      <p className="text-xs text-muted-foreground">
                        Due: {formatDate(milestone.due_date)}
                      </p>
                    )}
                  </div>
                </div>
                <div className="flex items-center gap-1">
                  <Select
                    value={milestone.status || undefined}
                    onValueChange={(value) => handleStatusChange(milestone.id, value)}
                  >
                    <SelectTrigger className="h-8 w-[130px] opacity-0 group-hover:opacity-100 transition-opacity">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {STATUS_OPTIONS.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                          {option.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="opacity-0 group-hover:opacity-100 transition-opacity"
                    onClick={() => handleDelete(milestone.id)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
