'use client'

import { useEffect } from 'react'
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
import { riskSchema } from '@/lib/validations'
import type { RiskFormData } from '@/types'
import { useRisksStore } from '@/stores/risks'
import { get } from '@/lib/api'
import type { Risk, User, RiskTheme, RiskCategory } from '@/types'
import { Loader2, AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'

interface RiskFormProps {
  risk?: Risk
  mode: 'create' | 'edit'
}

const IMPACT_LABELS = ['', 'Negligible', 'Minor', 'Moderate', 'Major', 'Critical']
const PROBABILITY_LABELS = ['', 'Rare', 'Unlikely', 'Possible', 'Likely', 'Almost Certain']

export function RiskForm({ risk, mode }: RiskFormProps) {
  const router = useRouter()
  const { create, update, isSaving, themes, categories, fetchThemes, fetchCategories } = useRisksStore()

  const { data: users } = useQuery({
    queryKey: ['users'],
    queryFn: () => get<{ data: User[] }>('/users'),
  })

  useEffect(() => {
    fetchThemes()
  }, [fetchThemes])

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<RiskFormData>({
    resolver: zodResolver(riskSchema),
    defaultValues: risk
      ? {
          category_id: risk.category_id,
          name: risk.name || '',
          description: risk.description || undefined,
          owner_id: risk.owner_id || undefined,
          responsible_party_id: risk.responsible_party_id || undefined,
          financial_impact: risk.financial_impact || 3,
          regulatory_impact: risk.regulatory_impact || 3,
          reputational_impact: risk.reputational_impact || 3,
          inherent_probability: risk.inherent_probability || 3,
        }
      : {
          name: '',
          financial_impact: 3,
          regulatory_impact: 3,
          reputational_impact: 3,
          inherent_probability: 3,
        },
  })

  const watchCategoryId = watch('category_id')
  const selectedCategory = categories.find(c => c.id === watchCategoryId)
  const selectedThemeId = selectedCategory?.theme_id

  useEffect(() => {
    if (selectedThemeId) {
      fetchCategories(selectedThemeId)
    }
  }, [selectedThemeId, fetchCategories])

  const watchFinancialImpact = watch('financial_impact')
  const watchRegulatoryImpact = watch('regulatory_impact')
  const watchReputationalImpact = watch('reputational_impact')
  const watchProbability = watch('inherent_probability')

  // Calculate maximum impact for score
  const maxImpact = Math.max(watchFinancialImpact || 1, watchRegulatoryImpact || 1, watchReputationalImpact || 1)
  const inherentScore = maxImpact * (watchProbability || 1)

  const getScoreColor = (score: number) => {
    if (score <= 4) return 'bg-green-500'
    if (score <= 9) return 'bg-amber-500'
    if (score <= 15) return 'bg-orange-500'
    return 'bg-red-500'
  }

  const onSubmit = async (data: RiskFormData) => {
    try {
      if (mode === 'create') {
        await create(data)
        toast.success('Risk created')
      } else if (risk) {
        await update(risk.id, data)
        toast.success('Risk updated')
      }
      router.push('/risks')
    } catch {
      toast.error('An error occurred')
    }
  }

  const handleThemeChange = (themeId: string) => {
    fetchCategories(parseInt(themeId))
    setValue('category_id', 0)
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Risk Identification</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="theme">Theme (L1)</Label>
              <Select
                value={selectedThemeId?.toString() || ''}
                onValueChange={handleThemeChange}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a theme" />
                </SelectTrigger>
                <SelectContent>
                  {themes.map((theme) => (
                    <SelectItem key={theme.id} value={theme.id.toString()}>
                      {theme.code} - {theme.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="category_id">Category (L2) *</Label>
              <Select
                value={watch('category_id')?.toString() || ''}
                onValueChange={(value) => setValue('category_id', parseInt(value))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a category" />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((cat) => (
                    <SelectItem key={cat.id} value={cat.id.toString()}>
                      {cat.code} - {cat.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.category_id && (
                <p className="text-sm text-destructive">{errors.category_id.message}</p>
              )}
            </div>

          </div>

          <div className="space-y-2">
            <Label htmlFor="name">Risk Name *</Label>
            <Input
              id="name"
              {...register('name')}
              placeholder="Risk name"
            />
            {errors.name && (
              <p className="text-sm text-destructive">{errors.name.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Detailed Description</Label>
            <textarea
              id="description"
              {...register('description')}
              rows={3}
              className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="Complete description of the risk, its causes and potential consequences"
            />
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="owner_id">Risk Owner</Label>
              <Select
                value={watch('owner_id')?.toString() || ''}
                onValueChange={(value) =>
                  setValue('owner_id', value ? parseInt(value) : undefined)
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select an owner" />
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

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <AlertTriangle className="h-5 w-5" />
            Inherent Risk Assessment
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div className="space-y-4">
              <Label>Financial Impact (1-5)</Label>
              <div className="flex items-center gap-2">
                {[1, 2, 3, 4, 5].map((value) => (
                  <button
                    key={value}
                    type="button"
                    onClick={() => setValue('financial_impact', value)}
                    className={`flex h-10 w-10 items-center justify-center rounded-md border-2 font-semibold transition-colors ${
                      watchFinancialImpact === value
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-muted hover:border-primary'
                    }`}
                  >
                    {value}
                  </button>
                ))}
              </div>
              <p className="text-sm text-muted-foreground">
                {IMPACT_LABELS[watchFinancialImpact || 0]}
              </p>
            </div>

            <div className="space-y-4">
              <Label>Regulatory Impact (1-5)</Label>
              <div className="flex items-center gap-2">
                {[1, 2, 3, 4, 5].map((value) => (
                  <button
                    key={value}
                    type="button"
                    onClick={() => setValue('regulatory_impact', value)}
                    className={`flex h-10 w-10 items-center justify-center rounded-md border-2 font-semibold transition-colors ${
                      watchRegulatoryImpact === value
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-muted hover:border-primary'
                    }`}
                  >
                    {value}
                  </button>
                ))}
              </div>
              <p className="text-sm text-muted-foreground">
                {IMPACT_LABELS[watchRegulatoryImpact || 0]}
              </p>
            </div>

            <div className="space-y-4">
              <Label>Reputational Impact (1-5)</Label>
              <div className="flex items-center gap-2">
                {[1, 2, 3, 4, 5].map((value) => (
                  <button
                    key={value}
                    type="button"
                    onClick={() => setValue('reputational_impact', value)}
                    className={`flex h-10 w-10 items-center justify-center rounded-md border-2 font-semibold transition-colors ${
                      watchReputationalImpact === value
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-muted hover:border-primary'
                    }`}
                  >
                    {value}
                  </button>
                ))}
              </div>
              <p className="text-sm text-muted-foreground">
                {IMPACT_LABELS[watchReputationalImpact || 0]}
              </p>
            </div>

            <div className="space-y-4">
              <Label>Probability (1-5)</Label>
              <div className="flex items-center gap-2">
                {[1, 2, 3, 4, 5].map((value) => (
                  <button
                    key={value}
                    type="button"
                    onClick={() => setValue('inherent_probability', value)}
                    className={`flex h-10 w-10 items-center justify-center rounded-md border-2 font-semibold transition-colors ${
                      watchProbability === value
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-muted hover:border-primary'
                    }`}
                  >
                    {value}
                  </button>
                ))}
              </div>
              <p className="text-sm text-muted-foreground">
                {PROBABILITY_LABELS[watchProbability || 0]}
              </p>
            </div>
          </div>

          <div className="flex items-center justify-center gap-4 rounded-lg border p-4">
            <span className="text-sm font-medium">Inherent Score:</span>
            <div
              className={`flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold text-white ${getScoreColor(
                inherentScore
              )}`}
            >
              {inherentScore}
            </div>
            <span className="text-sm text-muted-foreground">/ 25</span>
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-4">
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push('/risks')}
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
