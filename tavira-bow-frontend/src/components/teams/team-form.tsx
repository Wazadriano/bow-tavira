'use client'

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { teamSchema } from '@/lib/validations'
import type { TeamFormData } from '@/types'
import { useTeamsStore } from '@/stores/teams'
import type { Team } from '@/types'
import { Loader2 } from 'lucide-react'
import { toast } from 'sonner'

interface TeamFormProps {
  team?: Team
  mode: 'create' | 'edit'
}

export function TeamForm({ team, mode }: TeamFormProps) {
  const router = useRouter()
  const { create, update, isSaving } = useTeamsStore()

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors },
  } = useForm<TeamFormData>({
    resolver: zodResolver(teamSchema),
    defaultValues: team
      ? {
          name: team.name || '',
          description: team.description || '',
          is_active: team.is_active ?? true,
        }
      : {
          name: '',
          description: '',
          is_active: true,
        },
  })

  const onSubmit = async (data: TeamFormData) => {
    try {
      if (mode === 'create') {
        await create(data)
        toast.success('Team created')
      } else if (team) {
        await update(team.id, data)
        toast.success('Team updated')
      }
      router.push('/teams')
    } catch {
      toast.error('An error occurred')
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Team Information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="name">Team Name *</Label>
            <Input
              id="name"
              {...register('name')}
              placeholder="Team name"
            />
            {errors.name && (
              <p className="text-sm text-destructive">{errors.name.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <textarea
              id="description"
              {...register('description')}
              rows={3}
              className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="Team description and responsibilities"
            />
          </div>

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="is_active"
              {...register('is_active')}
              className="h-4 w-4 rounded border-gray-300"
            />
            <Label htmlFor="is_active">Active Team</Label>
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-4">
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push('/teams')}
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
