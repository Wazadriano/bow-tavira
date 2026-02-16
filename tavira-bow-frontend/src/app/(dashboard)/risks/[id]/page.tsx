import { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Link } from 'react-router-dom'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { PageLoading, ErrorState, FileAttachmentsPanel } from '@/components/shared'
import { useRisksStore } from '@/stores/risks'
import { api } from '@/lib/api'
import type { Attachment } from '@/types'
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
  Loader2,
} from 'lucide-react'
import { toast } from 'sonner'

const TIER_LABELS: Record<string, string> = {
  tier_1: 'Tier 1 - Critical',
  tier_2: 'Tier 2 - Important',
  tier_3: 'Tier 3 - Moderate',
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
  const navigate = useNavigate()
  const id = Number(params.id)

  const [controlDialogOpen, setControlDialogOpen] = useState(false)
  const [actionDialogOpen, setActionDialogOpen] = useState(false)
  const [isSavingForm, setIsSavingForm] = useState(false)
  const [selectedControlId, setSelectedControlId] = useState<string>('')
  const [controlEffectiveness, setControlEffectiveness] = useState<string>('effective')
  const [controlNotes, setControlNotes] = useState('')
  const [actionForm, setActionForm] = useState<{
    title: string
    description: string
    priority: 'low' | 'medium' | 'high' | 'critical'
    due_date: string
  }>({
    title: '',
    description: '',
    priority: 'medium',
    due_date: '',
  })
  const [riskFiles, setRiskFiles] = useState<Attachment[]>([])

  const {
    selectedItem,
    isLoadingItem,
    error,
    controls,
    actions,
    controlLibrary,
    fetchById,
    fetchControls,
    fetchActions,
    fetchControlLibrary,
    addControl,
    createAction,
    remove,
  } = useRisksStore()
  const { showConfirm } = useUIStore()

  const fetchRiskFiles = async () => {
    try {
      const res = await api.get<unknown>(`/risks/${id}/files`)
      const list = Array.isArray(res.data) ? res.data : []
      setRiskFiles(
        list.map((f: unknown) => {
          const row = f as Record<string, unknown>
          return {
            ...row,
            id: row.id as number,
            original_filename: (row.original_filename ?? row.original_name ?? row.filename) as string,
            file_size_formatted: (row.file_size_formatted ?? `${row.file_size ?? 0} B`) as string,
            filename: (row.filename ?? row.original_filename ?? row.original_name) as string,
            file_path: (row.file_path ?? row.path ?? '') as string,
            file_size: (row.file_size ?? 0) as number,
            mime_type: (row.mime_type ?? '') as string,
            uploaded_by_id: (row.uploaded_by_id ?? row.uploaded_by ?? null) as number | null,
            uploaded_by: (row.uploaded_by ?? null) as Attachment['uploaded_by'],
            created_at: (row.created_at ?? '') as string,
          } as Attachment
        })
      )
    } catch {
      setRiskFiles([])
    }
  }

  useEffect(() => {
    if (id) {
      fetchById(id)
      fetchControls(id)
      fetchActions(id)
      fetchControlLibrary()
      fetchRiskFiles()
    }
  }, [id, fetchById, fetchControls, fetchActions, fetchControlLibrary])

  const uploadRiskFile = async (file: File) => {
    const formData = new FormData()
    formData.append('file', file)
    await api.post(`/risks/${id}/files`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    await fetchRiskFiles()
  }

  const deleteRiskFile = async (fileId: number) => {
    await api.delete(`/risks/${id}/files/attachment/${fileId}`)
    await fetchRiskFiles()
  }

  const handleDelete = () => {
    showConfirm({
      title: 'Delete this risk',
      description: 'This action is irreversible. All associated controls and actions will also be deleted.',
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await remove(id)
          toast.success('Risk deleted')
          navigate('/risks')
        } catch {
          toast.error('Error during deletion')
        }
      },
    })
  }

  if (isLoadingItem) {
    return <PageLoading text="Loading risk..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Risk not found"
        description={error || "This risk does not exist or has been deleted."}
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
        description={risk.category?.name || 'Risk'}
      />

      <div className="p-6">
        <div className="mb-6 flex items-center justify-between">
          <Button variant="ghost" onClick={() => navigate(-1)}>
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link to={`/risks/${id}/edit`}>
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
                    <h4 className="text-sm font-medium text-muted-foreground">Category (L2)</h4>
                    <Badge variant="outline" className="mt-1">
                      {risk.category?.code} - {risk.category?.name}
                    </Badge>
                  </div>
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Risk Reference</h4>
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
                  Risk Assessment
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid gap-6 sm:grid-cols-2">
                  {/* Inherent risk */}
                  <div className="rounded-lg border p-4">
                    <h4 className="mb-4 font-semibold">Inherent Risk</h4>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Financial Impact</span>
                        <Badge variant="outline">{risk.financial_impact} / 5</Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Regulatory Impact</span>
                        <Badge variant="outline">{risk.regulatory_impact} / 5</Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Reputational Impact</span>
                        <Badge variant="outline">{risk.reputational_impact} / 5</Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Probability</span>
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
                    <h4 className="mb-4 font-semibold">Residual Risk</h4>
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
                      <span className="font-medium">Appetite Status</span>
                      <Badge variant={risk.appetite_status === 'exceeded' ? 'destructive' : risk.appetite_status === 'approaching' ? 'secondary' : 'default'}>
                        {risk.appetite_status === 'within' ? 'Within Limits' : risk.appetite_status === 'approaching' ? 'Approaching' : 'Exceeded'}
                      </Badge>
                    </div>
                    {risk.appetite_status === 'exceeded' && (
                      <p className="mt-2 text-sm text-destructive">
                        Residual risk exceeds the defined appetite
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
                  Controls ({controls.length})
                </CardTitle>
                <Dialog open={controlDialogOpen} onOpenChange={setControlDialogOpen}>
                  <DialogTrigger asChild>
                    <Button size="sm">
                      <Plus className="mr-2 h-4 w-4" />
                      Add
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Add Control</DialogTitle>
                      <DialogDescription>Link a control from the library to this risk</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                      <div className="space-y-2">
                        <Label>Control *</Label>
                        <Select value={selectedControlId} onValueChange={setSelectedControlId}>
                          <SelectTrigger>
                            <SelectValue placeholder="Select a control" />
                          </SelectTrigger>
                          <SelectContent>
                            {controlLibrary.map((ctrl) => (
                              <SelectItem key={ctrl.id} value={String(ctrl.id)}>
                                {ctrl.code} - {ctrl.name}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <Label>Effectiveness</Label>
                        <Select value={controlEffectiveness} onValueChange={setControlEffectiveness}>
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="effective">Effective</SelectItem>
                            <SelectItem value="partially_effective">Partially Effective</SelectItem>
                            <SelectItem value="ineffective">Ineffective</SelectItem>
                            <SelectItem value="none">None</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <Label>Notes</Label>
                        <Input value={controlNotes} onChange={(e) => setControlNotes(e.target.value)} placeholder="Optional notes" />
                      </div>
                      <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setControlDialogOpen(false)}>Cancel</Button>
                        <Button
                          disabled={!selectedControlId || isSavingForm}
                          onClick={async () => {
                            setIsSavingForm(true)
                            try {
                              await addControl(id, {
                                control_id: Number(selectedControlId),
                                effectiveness: controlEffectiveness as 'effective' | 'partially_effective' | 'ineffective' | 'none',
                                notes: controlNotes || undefined,
                              })
                              toast.success('Control added')
                              setControlDialogOpen(false)
                              setSelectedControlId('')
                              setControlEffectiveness('effective')
                              setControlNotes('')
                            } catch { toast.error('Error adding control') }
                            finally { setIsSavingForm(false) }
                          }}
                        >
                          {isSavingForm && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                          Add
                        </Button>
                      </div>
                    </div>
                  </DialogContent>
                </Dialog>
              </CardHeader>
              <CardContent>
                {controls.length === 0 ? (
                  <p className="py-4 text-center text-muted-foreground">
                    No controls defined for this risk
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
                <Dialog open={actionDialogOpen} onOpenChange={setActionDialogOpen}>
                  <DialogTrigger asChild>
                    <Button size="sm">
                      <Plus className="mr-2 h-4 w-4" />
                      Add
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Add Action</DialogTitle>
                      <DialogDescription>Create a remediation action for this risk</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                      <div className="space-y-2">
                        <Label>Title *</Label>
                        <Input value={actionForm.title} onChange={(e) => setActionForm((f) => ({ ...f, title: e.target.value }))} placeholder="Action title" />
                      </div>
                      <div className="space-y-2">
                        <Label>Description</Label>
                        <Input value={actionForm.description} onChange={(e) => setActionForm((f) => ({ ...f, description: e.target.value }))} placeholder="Optional description" />
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label>Priority</Label>
                          <Select value={actionForm.priority} onValueChange={(v) => setActionForm((f) => ({ ...f, priority: v as 'low' | 'medium' | 'high' | 'critical' }))}>
                            <SelectTrigger>
                              <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="low">Low</SelectItem>
                              <SelectItem value="medium">Medium</SelectItem>
                              <SelectItem value="high">High</SelectItem>
                              <SelectItem value="critical">Critical</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>
                        <div className="space-y-2">
                          <Label>Due Date</Label>
                          <Input type="date" value={actionForm.due_date} onChange={(e) => setActionForm((f) => ({ ...f, due_date: e.target.value }))} />
                        </div>
                      </div>
                      <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setActionDialogOpen(false)}>Cancel</Button>
                        <Button
                          disabled={!actionForm.title.trim() || isSavingForm}
                          onClick={async () => {
                            setIsSavingForm(true)
                            try {
                              await createAction(id, {
                                title: actionForm.title,
                                description: actionForm.description || undefined,
                                priority: actionForm.priority,
                                due_date: actionForm.due_date || undefined,
                              })
                              toast.success('Action created')
                              setActionDialogOpen(false)
                              setActionForm({ title: '', description: '', priority: 'medium', due_date: '' })
                            } catch { toast.error('Error creating action') }
                            finally { setIsSavingForm(false) }
                          }}
                        >
                          {isSavingForm && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                          Create
                        </Button>
                      </div>
                    </div>
                  </DialogContent>
                </Dialog>
              </CardHeader>
              <CardContent>
                {actions.length === 0 ? (
                  <p className="py-4 text-center text-muted-foreground">
                    No actions defined for this risk
                  </p>
                ) : (
                  <div className="space-y-3">
                    {actions.map((action) => (
                      <div key={action.id} className="rounded-lg border p-4">
                        <div className="flex items-start justify-between">
                          <div>
                            <p className="font-medium">{action.title}</p>
                            <p className="text-sm text-muted-foreground">
                              Due Date: {formatDate(action.due_date)}
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

            {/* File Attachments */}
            <FileAttachmentsPanel
              files={riskFiles}
              onUpload={uploadRiskFile}
              onDelete={deleteRiskFile}
              downloadUrlPrefix={`/api/risks/${id}/files/download`}
            />
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Status</CardTitle>
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
                    <span className="text-sm text-muted-foreground">Inherent RAG</span>
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
                    <span className="text-sm text-muted-foreground">Appetite Status</span>
                    <Badge variant={risk.appetite_status === 'within' ? 'default' : risk.appetite_status === 'exceeded' ? 'destructive' : 'secondary'}>
                      {risk.appetite_status === 'within' ? 'Within Limits' : risk.appetite_status === 'approaching' ? 'Approaching' : 'Exceeded'}
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
                  <h4 className="text-sm font-medium text-muted-foreground">Owner</h4>
                  <p className="mt-1 flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    {risk.owner?.full_name || '-'}
                  </p>
                </div>

                <Separator />

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Created</h4>
                  <p className="mt-1 text-sm">{formatDate(risk.created_at)}</p>
                </div>

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Modified</h4>
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
