'use client'

import { useEffect } from 'react'
import { useParams, useRouter } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { RAGBadge } from '@/components/shared/rag-badge'
import { PageLoading, ErrorState } from '@/components/shared'
import { useWorkItemsStore } from '@/stores/workitems'
import { useUIStore } from '@/stores/ui'
import { formatDate } from '@/lib/utils'
import {
  ArrowLeft,
  Edit,
  Trash2,
  Calendar,
  User,
  Flag,
  CheckCircle2,
  AlertCircle,
  FileText,
  Target,
} from 'lucide-react'
import { toast } from 'sonner'
import { DependenciesPanel } from '@/components/workitems/dependencies-panel'

const STATUS_LABELS: Record<string, string> = {
  not_started: 'Non commence',
  in_progress: 'En cours',
  on_hold: 'En pause',
  completed: 'Termine',
  cancelled: 'Annule',
}

export default function WorkItemDetailPage() {
  const params = useParams()
  const router = useRouter()
  const id = Number(params.id)

  const { selectedItem, isLoadingItem, error, fetchById, remove } = useWorkItemsStore()
  const { showConfirm } = useUIStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
    }
  }, [id, fetchById])

  const handleDelete = () => {
    showConfirm({
      title: 'Supprimer cette tache',
      description: 'Cette action est irreversible. Voulez-vous vraiment supprimer cette tache?',
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await remove(id)
          toast.success('Tache supprimee')
          router.push('/tasks')
        } catch {
          toast.error('Erreur lors de la suppression')
        }
      },
    })
  }

  if (isLoadingItem) {
    return <PageLoading text="Chargement de la tache..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Tache introuvable"
        description={error || "Cette tache n'existe pas ou a ete supprimee."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  const item = selectedItem

  return (
    <>
      <Header
        title={item.ref_no || `Tache #${item.id}`}
        description={item.description}
      />

      <div className="p-6">
        {/* Navigation et actions */}
        <div className="mb-6 flex items-center justify-between">
          <Button variant="ghost" asChild>
            <Link href="/tasks">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour a la liste
            </Link>
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={`/tasks/${id}/edit`}>
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
          {/* Colonne principale */}
          <div className="space-y-6 lg:col-span-2">
            {/* Informations generales */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <FileText className="h-5 w-5" />
                  Informations generales
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Description</h4>
                  <p className="mt-1">{item.description || '-'}</p>
                </div>

              </CardContent>
            </Card>

            {/* Dates */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Target className="h-5 w-5" />
                  Echeances
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Date cible</h4>
                    <p className="mt-1 flex items-center gap-2">
                      <Calendar className="h-4 w-4 text-muted-foreground" />
                      {formatDate(item.deadline)}
                    </p>
                  </div>
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Date de completion</h4>
                    <p className="mt-1 flex items-center gap-2">
                      <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
                      {formatDate(item.completion_date)}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Dependencies */}
            <DependenciesPanel
              workItemId={item.id}
              currentDependencies={[]}
            />
          </div>

          {/* Colonne laterale */}
          <div className="space-y-6">
            {/* Statut */}
            <Card>
              <CardHeader>
                <CardTitle>Statut</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">RAG</span>
                  <RAGBadge status={item.rag_status} />
                </div>

                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Statut</span>
                  <Badge variant="outline">
                    {item.current_status ? (STATUS_LABELS[item.current_status] || item.current_status) : '-'}
                  </Badge>
                </div>

                {item.priority_item && (
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Priorite</span>
                    <Badge variant="destructive">
                      <Flag className="mr-1 h-3 w-3" />
                      Prioritaire
                    </Badge>
                  </div>
                )}

                {item.bau_or_transformative === 'transformative' && (
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Type</span>
                    <Badge variant="secondary">
                      <AlertCircle className="mr-1 h-3 w-3" />
                      Transformative
                    </Badge>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Details */}
            <Card>
              <CardHeader>
                <CardTitle>Details</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Departement</h4>
                  <Badge variant="secondary" className="mt-1">
                    {item.department}
                  </Badge>
                </div>

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Responsable</h4>
                  <p className="mt-1 flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    {item.responsible_party?.full_name || '-'}
                  </p>
                </div>

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Reference</h4>
                  <p className="mt-1 font-mono text-sm">{item.ref_no || '-'}</p>
                </div>

                <Separator />

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Cree le</h4>
                  <p className="mt-1 text-sm">{formatDate(item.created_at)}</p>
                </div>

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Modifie le</h4>
                  <p className="mt-1 text-sm">{formatDate(item.updated_at)}</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </>
  )
}
