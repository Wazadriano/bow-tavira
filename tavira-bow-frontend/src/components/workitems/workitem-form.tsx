import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { workItemSchema } from '@/lib/validations'
import type { WorkItemFormData } from '@/types'
import { useWorkItemsStore } from '@/stores/workitems'
import { get } from '@/lib/api'
import type { WorkItem, User, SettingList } from '@/types'
import { Loader2, Info, X } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { useState } from 'react'
import { toast } from 'sonner'
import { MilestonesPanel } from './milestones-panel'

interface WorkItemFormProps {
  workItem?: WorkItem
  mode: 'create' | 'edit'
}

const STATUS_OPTIONS = [
  { value: 'not_started', label: 'Not Started' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'on_hold', label: 'On Hold' },
  { value: 'completed', label: 'Completed' },
]

const IMPACT_LEVEL_OPTIONS = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
]

const BAU_OPTIONS = [
  { value: 'bau', label: 'BAU' },
  { value: 'transformative', label: 'Transformative' },
]

export function WorkItemForm({ workItem, mode }: WorkItemFormProps) {
  const navigate = useNavigate()
  const { create, update, isSaving } = useWorkItemsStore()

  const { data: users } = useQuery({
    queryKey: ['users'],
    queryFn: () => get<{ data: User[] }>('/users'),
  })

  const { data: departments } = useQuery({
    queryKey: ['settings', 'departments'],
    queryFn: () => get<{ data: SettingList[] }>('/settings/lists?type=department'),
  })

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<WorkItemFormData>({
    resolver: zodResolver(workItemSchema),
    defaultValues: workItem
      ? {
          ref_no: workItem.ref_no || '',
          type: workItem.type || undefined,
          activity: workItem.activity || undefined,
          department: workItem.department || '',
          description: workItem.description || '',
          bau_or_transformative: workItem.bau_or_transformative || undefined,
          impact_level: workItem.impact_level || undefined,
          current_status: workItem.current_status || 'not_started',
          deadline: workItem.deadline || undefined,
          responsible_party_id: workItem.responsible_party_id || undefined,
          tags: workItem.tags || [],
          priority_item: workItem.priority_item || false,
        }
      : {
          ref_no: '',
          description: '',
          department: '',
          current_status: 'not_started',
          priority_item: false,
          tags: [],
        },
  })

  const onSubmit = async (data: WorkItemFormData) => {
    try {
      if (mode === 'create') {
        await create(data)
        toast.success('Work item created successfully')
      } else if (workItem) {
        await update(workItem.id, data)
        toast.success('Work item updated successfully')
      }
      navigate('/tasks')
    } catch (error: unknown) {
      const axiosError = error as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } }
      if (axiosError.response?.status === 422 && axiosError.response.data?.errors) {
        const serverErrors = axiosError.response.data.errors
        Object.entries(serverErrors).forEach(([field, messages]) => {
          toast.error(`${field}: ${messages.join(', ')}`)
        })
      } else {
        toast.error(axiosError.response?.data?.message || 'An error occurred')
      }
    }
  }

  const [tagInput, setTagInput] = useState('')
  const watchStatus = watch('current_status')
  const watchBauOrTransformative = watch('bau_or_transformative')
  const watchTags = watch('tags') || []

  const addTag = () => {
    const tag = tagInput.trim()
    if (tag && !watchTags.includes(tag)) {
      setValue('tags', [...watchTags, tag])
    }
    setTagInput('')
  }

  const removeTag = (tag: string) => {
    setValue('tags', watchTags.filter((t) => t !== tag))
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>General Information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="ref_no">Reference *</Label>
              <Input
                id="ref_no"
                {...register('ref_no')}
                placeholder="Work item reference"
              />
              {errors.ref_no && (
                <p className="text-sm text-destructive">{errors.ref_no.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="department">Department *</Label>
              <Select
                value={watch('department')}
                onValueChange={(value) => setValue('department', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a department" />
                </SelectTrigger>
                <SelectContent>
                  {departments?.data?.map((dept) => (
                    <SelectItem key={dept.id} value={dept.value}>
                      {dept.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.department && (
                <p className="text-sm text-destructive">{errors.department.message}</p>
              )}
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description *</Label>
            <textarea
              id="description"
              {...register('description')}
              rows={3}
              className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="Detailed description of the work item"
            />
            {errors.description && (
              <p className="text-sm text-destructive">{errors.description.message}</p>
            )}
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="type">Type</Label>
              <Input
                id="type"
                {...register('type')}
                placeholder="Work item type"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="activity">Activity</Label>
              <Input
                id="activity"
                {...register('activity')}
                placeholder="Activity"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Status and Planning</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="current_status">Status</Label>
              <Select
                value={watchStatus}
                onValueChange={(value) => setValue('current_status', value as WorkItemFormData['current_status'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a status" />
                </SelectTrigger>
                <SelectContent>
                  {STATUS_OPTIONS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="impact_level">Impact Level</Label>
              <Select
                value={watch('impact_level') || ''}
                onValueChange={(value) => setValue('impact_level', value as WorkItemFormData['impact_level'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a level" />
                </SelectTrigger>
                <SelectContent>
                  {IMPACT_LEVEL_OPTIONS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="bau_or_transformative">BAU / Transformative</Label>
              <Select
                value={watch('bau_or_transformative') || ''}
                onValueChange={(value) => setValue('bau_or_transformative', value as WorkItemFormData['bau_or_transformative'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                  {BAU_OPTIONS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="responsible_party_id">Responsible Party</Label>
              <Select
                value={watch('responsible_party_id')?.toString() || ''}
                onValueChange={(value) =>
                  setValue('responsible_party_id', value ? parseInt(value) : undefined)
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select responsible party" />
                </SelectTrigger>
                <SelectContent>
                  {users?.data?.map((user) => (
                    <SelectItem key={user.id} value={user.id.toString()}>
                      {user.full_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="deadline">Deadline</Label>
              <Input
                id="deadline"
                type="date"
                {...register('deadline')}
              />
            </div>
          </div>

          <div className="flex items-center gap-6">
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                {...register('priority_item')}
                className="h-4 w-4 rounded border-gray-300"
              />
              <span className="text-sm font-medium">Priority Item</span>
            </label>
          </div>

          <div className="space-y-2">
            <Label>Tags</Label>
            <div className="flex flex-wrap gap-1 mb-2">
              {watchTags.map((tag) => (
                <Badge key={tag} variant="secondary" className="gap-1">
                  {tag}
                  <button
                    type="button"
                    onClick={() => removeTag(tag)}
                    className="ml-1 hover:text-destructive"
                  >
                    <X className="h-3 w-3" />
                  </button>
                </Badge>
              ))}
            </div>
            <div className="flex gap-2">
              <Input
                value={tagInput}
                onChange={(e) => setTagInput(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    e.preventDefault()
                    addTag()
                  }
                }}
                placeholder="Add a tag and press Enter"
                className="flex-1"
              />
              <Button type="button" variant="outline" size="sm" onClick={addTag}>
                Add
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Milestones - only for Transformative work items */}
      {watchBauOrTransformative === 'transformative' && (
        mode === 'edit' && workItem ? (
          <MilestonesPanel workItemId={workItem.id} />
        ) : (
          <Card>
            <CardHeader>
              <CardTitle>Milestones</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Info className="h-4 w-4 shrink-0" />
                <p>Please save the task first before adding milestones.</p>
              </div>
            </CardContent>
          </Card>
        )
      )}

      <div className="flex justify-end gap-4">
        <Button
          type="button"
          variant="outline"
          onClick={() => navigate('/tasks')}
        >
          Cancel
        </Button>
        <Button type="submit" disabled={isSaving}>
          {isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          {mode === 'create' ? 'Create' : 'Save'}
        </Button>
      </div>
    </form>
  )
}
