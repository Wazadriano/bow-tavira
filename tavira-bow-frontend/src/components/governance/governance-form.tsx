'use client'

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useRouter } from 'next/navigation'
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
import { governanceSchema } from '@/lib/validations'
import { useGovernanceStore } from '@/stores/governance'
import { get } from '@/lib/api'
import type { GovernanceItem, GovernanceFormData, User, SettingList } from '@/types'
import { Loader2 } from 'lucide-react'
import { toast } from 'sonner'

interface GovernanceFormProps {
  item?: GovernanceItem
  mode: 'create' | 'edit'
}

const FREQUENCY_OPTIONS = [
  { value: 'daily', label: 'Daily' },
  { value: 'weekly', label: 'Weekly' },
  { value: 'monthly', label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
  { value: 'annually', label: 'Annually' },
]

export function GovernanceForm({ item, mode }: GovernanceFormProps) {
  const router = useRouter()
  const { create, update, isSaving } = useGovernanceStore()

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
  } = useForm<GovernanceFormData>({
    resolver: zodResolver(governanceSchema),
    defaultValues: item
      ? {
          ref_no: item.ref_no || '',
          activity: item.activity || '',
          description: item.description || '',
          department: item.department || '',
          frequency: item.frequency || undefined,
          deadline: item.deadline || undefined,
          responsible_party_id: item.responsible_party_id || undefined,
        }
      : {
          ref_no: '',
          activity: '',
          description: '',
          department: '',
        },
  })

  const onSubmit = async (data: GovernanceFormData) => {
    try {
      if (mode === 'create') {
        await create(data)
        toast.success('Governance item created')
      } else if (item) {
        await update(item.id, data)
        toast.success('Governance item updated')
      }
      router.push('/governance')
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
                placeholder="GOV-001"
              />
              {errors.ref_no && (
                <p className="text-sm text-destructive">{errors.ref_no.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="activity">Activity *</Label>
              <Input
                id="activity"
                {...register('activity')}
                placeholder="Item name"
              />
              {errors.activity && (
                <p className="text-sm text-destructive">{errors.activity.message}</p>
              )}
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
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
              placeholder="Detailed description"
            />
            {errors.description && (
              <p className="text-sm text-destructive">{errors.description.message}</p>
            )}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Planning</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="frequency">Frequency</Label>
              <Select
                value={watch('frequency') || ''}
                onValueChange={(value) => setValue('frequency', value as GovernanceFormData['frequency'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a frequency" />
                </SelectTrigger>
                <SelectContent>
                  {FREQUENCY_OPTIONS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
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
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-4">
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push('/governance')}
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
