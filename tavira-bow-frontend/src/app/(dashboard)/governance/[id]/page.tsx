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
import { PageLoading, ErrorState, AccessManagementPanel } from '@/components/shared'
import { useGovernanceStore } from '@/stores/governance'
import { useUIStore } from '@/stores/ui'
import { formatDate } from '@/lib/utils'
import {
  ArrowLeft,
  Edit,
  Trash2,
  Calendar,
  User,
  Clock,
  RefreshCw,
  FileText,
  CheckCircle,
} from 'lucide-react'
import { toast } from 'sonner'
import { GovernanceMilestonesPanel } from '@/components/governance/governance-milestones-panel'

const STATUS_LABELS: Record<string, string> = {
  not_started: 'Not Started',
  in_progress: 'In Progress',
  on_hold: 'On Hold',
  completed: 'Completed',
  cancelled: 'Cancelled',
}

const FREQUENCY_LABELS: Record<string, string> = {
  daily: 'Daily',
  weekly: 'Weekly',
  monthly: 'Monthly',
  quarterly: 'Quarterly',
  annually: 'Annually',
  ad_hoc: 'Ad Hoc',
}

export default function GovernanceDetailPage() {
  const params = useParams()
  const router = useRouter()
  const id = Number(params.id)

  const { selectedItem, isLoadingItem, error, fetchById, remove } = useGovernanceStore()
  const { showConfirm } = useUIStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
    }
  }, [id, fetchById])

  const handleDelete = () => {
    showConfirm({
      title: 'Delete this item',
      description: 'This action is irreversible. Are you sure you want to delete this governance item?',
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await remove(id)
          toast.success('Item deleted')
          router.push('/governance')
        } catch {
          toast.error('Error during deletion')
        }
      },
    })
  }

  if (isLoadingItem) {
    return <PageLoading text="Loading..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Item not found"
        description={error || "This item does not exist or has been deleted."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  const item = selectedItem

  return (
    <>
      <Header
        title={item.activity || `Governance #${item.id}`}
        description={item.description || undefined}
      />

      <div className="p-6">
        <div className="mb-6 flex items-center justify-between">
          <Button variant="ghost" asChild>
            <Link href="/governance">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to list
            </Link>
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={`/governance/${id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </Button>
            <Button variant="destructive" onClick={handleDelete}>
              <Trash2 className="mr-2 h-4 w-4" />
              Delete
            </Button>
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          <div className="space-y-6 lg:col-span-2">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <FileText className="h-5 w-5" />
                  Information
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Description</h4>
                  <p className="mt-1">{item.description || '-'}</p>
                </div>

                {item.monthly_update && (
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Update</h4>
                    <p className="mt-1 whitespace-pre-wrap">{item.monthly_update}</p>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Calendar className="h-5 w-5" />
                  Schedule
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid gap-4 sm:grid-cols-3">
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Frequency</h4>
                    <p className="mt-1 flex items-center gap-2">
                      <RefreshCw className="h-4 w-4 text-muted-foreground" />
                      {item.frequency ? (FREQUENCY_LABELS[item.frequency] || item.frequency) : '-'}
                    </p>
                  </div>
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Next Deadline</h4>
                    <p className="mt-1 flex items-center gap-2">
                      <Clock className="h-4 w-4 text-muted-foreground" />
                      {formatDate(item.deadline)}
                    </p>
                  </div>
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Last Completion</h4>
                    <p className="mt-1 flex items-center gap-2">
                      <CheckCircle className="h-4 w-4 text-muted-foreground" />
                      {formatDate(item.completion_date)}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Milestones */}
            <GovernanceMilestonesPanel itemId={item.id} />

            {/* Access Management */}
            <AccessManagementPanel
              resourceType="governance"
              resourceId={item.id}
              currentAccess={(item.access || []) as never[]}
              onAccessUpdated={() => fetchById(id)}
            />
          </div>

          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Status</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">RAG</span>
                  <RAGBadge status={item.rag_status} />
                </div>

                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Status</span>
                  <Badge variant="outline">
                    {item.current_status ? (STATUS_LABELS[item.current_status] || item.current_status) : '-'}
                  </Badge>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Details</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Department</h4>
                  <Badge variant="secondary" className="mt-1">
                    {item.department}
                  </Badge>
                </div>

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Responsible Party</h4>
                  <p className="mt-1 flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    {item.responsible_party?.full_name || '-'}
                  </p>
                </div>

                <Separator />

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Created</h4>
                  <p className="mt-1 text-sm">{formatDate(item.created_at)}</p>
                </div>

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Modified</h4>
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
