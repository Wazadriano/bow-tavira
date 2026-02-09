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
  { value: 'daily', label: 'Quotidien' },
  { value: 'weekly', label: 'Hebdomadaire' },
  { value: 'monthly', label: 'Mensuel' },
  { value: 'quarterly', label: 'Trimestriel' },
  { value: 'annually', label: 'Annuel' },
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
          activity: item.activity || '',
          description: item.description || '',
          department: item.department || '',
          frequency: item.frequency || undefined,
          deadline: item.deadline || undefined,
          responsible_party_id: item.responsible_party_id || undefined,
        }
      : {
          activity: '',
          description: '',
          department: '',
        },
  })

  const onSubmit = async (data: GovernanceFormData) => {
    try {
      if (mode === 'create') {
        await create(data)
        toast.success('Element de gouvernance cree')
      } else if (item) {
        await update(item.id, data)
        toast.success('Element de gouvernance mis a jour')
      }
      router.push('/governance')
    } catch {
      toast.error('Une erreur est survenue')
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Informations generales</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="activity">Activite *</Label>
              <Input
                id="activity"
                {...register('activity')}
                placeholder="Nom de l'element"
              />
              {errors.activity && (
                <p className="text-sm text-destructive">{errors.activity.message}</p>
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
            <Label htmlFor="description">Description</Label>
            <textarea
              id="description"
              {...register('description')}
              rows={3}
              className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="Description detaillee"
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Planification</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="frequency">Frequence</Label>
              <Select
                value={watch('frequency') || ''}
                onValueChange={(value) => setValue('frequency', value as GovernanceFormData['frequency'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selectionner une frequence" />
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
              <Label htmlFor="deadline">Echeance</Label>
              <Input
                id="deadline"
                type="date"
                {...register('deadline')}
              />
            </div>

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
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-4">
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push('/governance')}
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
