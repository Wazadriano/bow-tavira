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
import { workItemSchema } from '@/lib/validations'
import type { WorkItemFormData } from '@/types'
import { useWorkItemsStore } from '@/stores/workitems'
import { get } from '@/lib/api'
import type { WorkItem, User, SettingList } from '@/types'
import { Loader2 } from 'lucide-react'
import { toast } from 'sonner'

interface WorkItemFormProps {
  workItem?: WorkItem
  mode: 'create' | 'edit'
}

const STATUS_OPTIONS = [
  { value: 'not_started', label: 'Non commence' },
  { value: 'in_progress', label: 'En cours' },
  { value: 'on_hold', label: 'En pause' },
  { value: 'completed', label: 'Termine' },
]

const IMPACT_LEVEL_OPTIONS = [
  { value: 'low', label: 'Faible' },
  { value: 'medium', label: 'Moyen' },
  { value: 'high', label: 'Eleve' },
]

const BAU_OPTIONS = [
  { value: 'bau', label: 'BAU' },
  { value: 'transformative', label: 'Transformatif' },
]

export function WorkItemForm({ workItem, mode }: WorkItemFormProps) {
  const router = useRouter()
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
        toast.success('Tache creee avec succes')
      } else if (workItem) {
        await update(workItem.id, data)
        toast.success('Tache mise a jour avec succes')
      }
      router.push('/tasks')
    } catch {
      toast.error('Une erreur est survenue')
    }
  }

  const watchStatus = watch('current_status')

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Informations generales</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="ref_no">Reference *</Label>
              <Input
                id="ref_no"
                {...register('ref_no')}
                placeholder="Reference de la tache"
              />
              {errors.ref_no && (
                <p className="text-sm text-destructive">{errors.ref_no.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="department">Departement *</Label>
              <Select
                value={watch('department')}
                onValueChange={(value) => setValue('department', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selectionner un departement" />
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
              placeholder="Description detaillee de la tache"
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
                placeholder="Type de tache"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="activity">Activite</Label>
              <Input
                id="activity"
                {...register('activity')}
                placeholder="Activite"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Statut et planification</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="current_status">Statut</Label>
              <Select
                value={watchStatus}
                onValueChange={(value) => setValue('current_status', value as WorkItemFormData['current_status'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selectionner un statut" />
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
              <Label htmlFor="impact_level">Niveau d&apos;impact</Label>
              <Select
                value={watch('impact_level') || ''}
                onValueChange={(value) => setValue('impact_level', value as WorkItemFormData['impact_level'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selectionner un niveau" />
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
              <Label htmlFor="bau_or_transformative">BAU / Transformatif</Label>
              <Select
                value={watch('bau_or_transformative') || ''}
                onValueChange={(value) => setValue('bau_or_transformative', value as WorkItemFormData['bau_or_transformative'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selectionner" />
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
              <Label htmlFor="responsible_party_id">Responsable</Label>
              <Select
                value={watch('responsible_party_id')?.toString() || ''}
                onValueChange={(value) =>
                  setValue('responsible_party_id', value ? parseInt(value) : undefined)
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selectionner un responsable" />
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
              <Label htmlFor="deadline">Date limite</Label>
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
              <span className="text-sm font-medium">Prioritaire</span>
            </label>
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-4">
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push('/tasks')}
        >
          Annuler
        </Button>
        <Button type="submit" disabled={isSaving}>
          {isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          {mode === 'create' ? 'Creer' : 'Enregistrer'}
        </Button>
      </div>
    </form>
  )
}
