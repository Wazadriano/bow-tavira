'use client'

import { useEffect } from 'react'
import { useParams, useRouter } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { PageLoading, ErrorState } from '@/components/shared'
import { useRisksStore } from '@/stores/risks'
import { useUIStore } from '@/stores/ui'
import { formatDate } from '@/lib/utils'
import {
  ArrowLeft,
  Edit,
  Trash2,
  Shield,
  User,
  AlertTriangle,
  Target,
  Activity,
  Plus,
} from 'lucide-react'
import { toast } from 'sonner'

const TIER_LABELS: Record<string, string> = {
  tier_1: 'Tier 1 - Critique',
  tier_2: 'Tier 2 - Important',
  tier_3: 'Tier 3 - Modere',
}

const TIER_COLORS: Record<string, string> = {
  tier_1: 'bg-red-100 text-red-800',
  tier_2: 'bg-amber-100 text-amber-800',
  tier_3: 'bg-blue-100 text-blue-800',
}

function getScoreColor(score: number): string {
  if (score <= 4) return 'bg-green-500'
  if (score <= 9) return 'bg-amber-500'
  if (score <= 15) return 'bg-orange-500'
  return 'bg-red-500'
}

export default function RiskDetailPage() {
  const params = useParams()
  const router = useRouter()
  const id = Number(params.id)

  const {
    selectedItem,
    isLoadingItem,
    error,
    controls,
    actions,
    fetchById,
    fetchControls,
    fetchActions,
    remove,
  } = useRisksStore()
  const { showConfirm } = useUIStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
      fetchControls(id)
      fetchActions(id)
    }
  }, [id, fetchById, fetchControls, fetchActions])

  const handleDelete = () => {
    showConfirm({
      title: 'Supprimer ce risque',
      description: 'Cette action est irreversible. Tous les controles et actions associes seront egalement supprimes.',
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await remove(id)
          toast.success('Risque supprime')
          router.push('/risks')
        } catch {
          toast.error('Erreur lors de la suppression')
        }
      },
    })
  }

  if (isLoadingItem) {
    return <PageLoading text="Chargement du risque..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Risque introuvable"
        description={error || "Ce risque n'existe pas ou a ete supprime."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  const risk = selectedItem
  // Calculate inherent impact as max of financial, regulatory, reputational
  const inherentImpact = Math.max(risk.financial_impact || 1, risk.regulatory_impact || 1, risk.reputational_impact || 1)
  const inherentScore = risk.inherent_risk_score || (inherentImpact * (risk.inherent_probability || 1))
  const residualScore = risk.residual_risk_score || inherentScore

  return (
    <>
      <Header
        title={`${risk.ref_no} - ${risk.name}`}
        description={risk.category?.name || 'Risque'}
      />

      <div className="p-6">
        <div className="mb-6 flex items-center justify-between">
          <Button variant="ghost" asChild>
            <Link href="/risks">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour a la liste
            </Link>
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={`/risks/${id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Modifier
              </Link>
            </Button>
            <Button variant="destructive" onClick={handleDelete}>
              <Trash2 className="mr-2 h-4 w-4" />
              Supprimer
            </Button>
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          {/* Main content */}
          <div className="space-y-6 lg:col-span-2">
            {/* Risk identification */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Shield className="h-5 w-5" />
                  Identification
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-3">
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Theme (L1)</h4>
                    <Badge variant="outline" className="mt-1">
                      {risk.category?.theme?.code} - {risk.category?.theme?.name}
                    </Badge>
                  </div>
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Categorie (L2)</h4>
                    <Badge variant="outline" className="mt-1">
                      {risk.category?.code} - {risk.category?.name}
                    </Badge>
                  </div>
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Reference risque</h4>
                    <p className="mt-1 font-mono font-semibold">{risk.ref_no}</p>
                  </div>
                </div>

                <Separator />

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Description</h4>
                  <p className="mt-1 whitespace-pre-wrap">{risk.description || '-'}</p>
                </div>

              </CardContent>
            </Card>

            {/* Risk scoring */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <AlertTriangle className="h-5 w-5" />
                  Evaluation des risques
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid gap-6 sm:grid-cols-2">
                  {/* Inherent risk */}
                  <div className="rounded-lg border p-4">
                    <h4 className="mb-4 font-semibold">Risque inherent</h4>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Impact Financier</span>
                        <Badge variant="outline">{risk.financial_impact} / 5</Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Impact Reglementaire</span>
                        <Badge variant="outline">{risk.regulatory_impact} / 5</Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Impact Reputationnel</span>
                        <Badge variant="outline">{risk.reputational_impact} / 5</Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Probabilite</span>
                        <Badge variant="outline">{risk.inherent_probability} / 5</Badge>
                      </div>
                      <Separator />
                      <div className="flex items-center justify-between">
                        <span className="font-medium">Score</span>
                        <div
                          className={`flex h-10 w-10 items-center justify-center rounded-full text-white font-bold ${getScoreColor(
                            inherentScore
                          )}`}
                        >
                          {inherentScore}
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Residual risk */}
                  <div className="rounded-lg border p-4">
                    <h4 className="mb-4 font-semibold">Risque residuel</h4>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">RAG Status</span>
                        <Badge
                          className={
                            risk.residual_rag === 'green'
                              ? 'bg-green-100 text-green-800'
                              : risk.residual_rag === 'amber'
                              ? 'bg-amber-100 text-amber-800'
                              : risk.residual_rag === 'red'
                              ? 'bg-red-100 text-red-800'
                              : ''
                          }
                        >
                          {risk.residual_rag?.toUpperCase() || '-'}
                        </Badge>
                      </div>
                      <Separator />
                      <div className="flex items-center justify-between">
                        <span className="font-medium">Score</span>
                        <div
                          className={`flex h-10 w-10 items-center justify-center rounded-full text-white font-bold ${getScoreColor(
                            residualScore
                          )}`}
                        >
                          {residualScore}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {risk.appetite_status && (
                  <div className="mt-4 rounded-lg bg-muted p-4">
                    <div className="flex items-center justify-between">
                      <span className="font-medium">Statut d'appetence</span>
                      <Badge variant={risk.appetite_status === 'exceeded' ? 'destructive' : risk.appetite_status === 'approaching' ? 'secondary' : 'default'}>
                        {risk.appetite_status === 'within' ? 'Dans les limites' : risk.appetite_status === 'approaching' ? 'Approche' : 'Depasse'}
                      </Badge>
                    </div>
                    {risk.appetite_status === 'exceeded' && (
                      <p className="mt-2 text-sm text-destructive">
                        Le risque residuel depasse l'appetence definie
                      </p>
                    )}
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Controls */}
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2">
                  <Target className="h-5 w-5" />
                  Controles ({controls.length})
                </CardTitle>
                <Button size="sm">
                  <Plus className="mr-2 h-4 w-4" />
                  Ajouter
                </Button>
              </CardHeader>
              <CardContent>
                {controls.length === 0 ? (
                  <p className="py-4 text-center text-muted-foreground">
                    Aucun controle defini pour ce risque
                  </p>
                ) : (
                  <div className="space-y-3">
                    {controls.map((control) => (
                      <div key={control.id} className="rounded-lg border p-4">
                        <div className="flex items-start justify-between">
                          <div>
                            <p className="font-medium">{control.control?.name || '-'}</p>
                            <p className="text-sm text-muted-foreground">{control.control?.description || '-'}</p>
                          </div>
                          <div className="flex gap-2">
                            <Badge variant="outline">{control.control?.control_type || '-'}</Badge>
                            <Badge
                              variant={
                                control.effectiveness === 'effective'
                                  ? 'default'
                                  : control.effectiveness === 'partially_effective'
                                  ? 'secondary'
                                  : 'destructive'
                              }
                            >
                              {control.effectiveness || '-'}
                            </Badge>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Actions */}
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2">
                  <Activity className="h-5 w-5" />
                  Actions ({actions.length})
                </CardTitle>
                <Button size="sm">
                  <Plus className="mr-2 h-4 w-4" />
                  Ajouter
                </Button>
              </CardHeader>
              <CardContent>
                {actions.length === 0 ? (
                  <p className="py-4 text-center text-muted-foreground">
                    Aucune action definie pour ce risque
                  </p>
                ) : (
                  <div className="space-y-3">
                    {actions.map((action) => (
                      <div key={action.id} className="rounded-lg border p-4">
                        <div className="flex items-start justify-between">
                          <div>
                            <p className="font-medium">{action.title}</p>
                            <p className="text-sm text-muted-foreground">
                              Echeance: {formatDate(action.due_date)}
                            </p>
                          </div>
                          <div className="flex gap-2">
                            <Badge variant="outline">{action.priority}</Badge>
                            <Badge
                              variant={
                                action.status === 'completed'
                                  ? 'default'
                                  : action.status === 'in_progress'
                                  ? 'secondary'
                                  : 'outline'
                              }
                            >
                              {action.status || '-'}
                            </Badge>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Statut</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {risk.tier && (
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Tier</span>
                    <Badge className={TIER_COLORS[risk.tier] || ''}>
                      {TIER_LABELS[risk.tier] || risk.tier}
                    </Badge>
                  </div>
                )}

                {risk.inherent_rag && (
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">RAG Inherent</span>
                    <Badge
                      className={
                        risk.inherent_rag === 'green'
                          ? 'bg-green-100 text-green-800'
                          : risk.inherent_rag === 'amber'
                          ? 'bg-amber-100 text-amber-800'
                          : 'bg-red-100 text-red-800'
                      }
                    >
                      {risk.inherent_rag.toUpperCase()}
                    </Badge>
                  </div>
                )}

                {risk.appetite_status && (
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Statut appetence</span>
                    <Badge variant={risk.appetite_status === 'within' ? 'default' : risk.appetite_status === 'exceeded' ? 'destructive' : 'secondary'}>
                      {risk.appetite_status === 'within' ? 'Dans les limites' : risk.appetite_status === 'approaching' ? 'Approche' : 'Depasse'}
                    </Badge>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Details</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Proprietaire</h4>
                  <p className="mt-1 flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    {risk.owner?.full_name || '-'}
                  </p>
                </div>

                <Separator />

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Cree le</h4>
                  <p className="mt-1 text-sm">{formatDate(risk.created_at)}</p>
                </div>

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Modifie le</h4>
                  <p className="mt-1 text-sm">{formatDate(risk.updated_at)}</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </>
  )
}
