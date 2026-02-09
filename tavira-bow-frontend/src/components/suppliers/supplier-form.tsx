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
import { supplierSchema } from '@/lib/validations'
import type { SupplierFormData } from '@/types'
import { useSuppliersStore } from '@/stores/suppliers'
import { get } from '@/lib/api'
import type { Supplier, SettingList, User } from '@/types'
import { Loader2 } from 'lucide-react'
import { toast } from 'sonner'

interface SupplierFormProps {
  supplier?: Supplier
  mode: 'create' | 'edit'
}

const STATUS_OPTIONS = [
  { value: 'active', label: 'Actif' },
  { value: 'inactive', label: 'Inactif' },
  { value: 'pending', label: 'En attente' },
]

const LOCATION_OPTIONS = [
  { value: 'local', label: 'Local' },
  { value: 'overseas', label: 'International' },
]

export function SupplierForm({ supplier, mode }: SupplierFormProps) {
  const router = useRouter()
  const { create, update, isSaving } = useSuppliersStore()

  const { data: sageCategories } = useQuery({
    queryKey: ['sage-categories'],
    queryFn: () => get<{ data: SettingList[] }>('/settings/sage-categories'),
  })

  const { data: users } = useQuery({
    queryKey: ['users'],
    queryFn: () => get<{ data: User[] }>('/users'),
  })

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<SupplierFormData>({
    resolver: zodResolver(supplierSchema),
    defaultValues: supplier
      ? {
          name: supplier.name || '',
          sage_category_id: supplier.sage_category_id || undefined,
          location: supplier.location || 'local',
          is_common_provider: supplier.is_common_provider || false,
          status: supplier.status || 'active',
          responsible_party_id: supplier.responsible_party_id || undefined,
          notes: supplier.notes || '',
          entities: supplier.entities?.map(e => e.entity) || [],
        }
      : {
          name: '',
          location: 'local',
          status: 'active',
          is_common_provider: false,
          entities: [],
        },
  })

  const onSubmit = async (data: SupplierFormData) => {
    try {
      if (mode === 'create') {
        await create(data)
        toast.success('Fournisseur cree')
      } else if (supplier) {
        await update(supplier.id, data)
        toast.success('Fournisseur mis a jour')
      }
      router.push('/suppliers')
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
              <Label htmlFor="name">Nom du fournisseur *</Label>
              <Input
                id="name"
                {...register('name')}
                placeholder="Nom du fournisseur"
              />
              {errors.name && (
                <p className="text-sm text-destructive">{errors.name.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="status">Statut *</Label>
              <Select
                value={watch('status')}
                onValueChange={(value) => setValue('status', value as SupplierFormData['status'])}
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
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="location">Localisation *</Label>
              <Select
                value={watch('location') || ''}
                onValueChange={(value) => setValue('location', value as 'local' | 'overseas')}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selectionner une localisation" />
                </SelectTrigger>
                <SelectContent>
                  {LOCATION_OPTIONS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.location && (
                <p className="text-sm text-destructive">{errors.location.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="sage_category_id">Categorie SAGE</Label>
              <Select
                value={watch('sage_category_id')?.toString() || ''}
                onValueChange={(value) =>
                  setValue('sage_category_id', value ? parseInt(value) : undefined)
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selectionner une categorie" />
                </SelectTrigger>
                <SelectContent>
                  {sageCategories?.data?.map((cat) => (
                    <SelectItem key={cat.id} value={cat.id.toString()}>
                      {cat.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
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

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="is_common_provider"
              {...register('is_common_provider')}
              className="h-4 w-4 rounded border-gray-300"
            />
            <Label htmlFor="is_common_provider">Fournisseur commun</Label>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Notes</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            <Label htmlFor="notes">Notes additionnelles</Label>
            <textarea
              id="notes"
              {...register('notes')}
              rows={4}
              className="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="Notes et commentaires"
            />
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-4">
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push('/suppliers')}
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
