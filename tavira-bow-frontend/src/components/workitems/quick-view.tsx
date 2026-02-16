import { useEffect } from 'react'
import { Link } from 'react-router-dom'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { RAGBadge } from '@/components/shared/rag-badge'
import { useWorkItemsStore } from '@/stores/workitems'
import { formatDate } from '@/lib/utils'
import {
  ExternalLink,
  Edit,
  Calendar,
  User,
  Flag,
  AlertCircle,
  Tag,
  Loader2,
} from 'lucide-react'

const STATUS_LABELS: Record<string, string> = {
  not_started: 'Not Started',
  in_progress: 'In Progress',
  on_hold: 'On Hold',
  completed: 'Completed',
  cancelled: 'Cancelled',
}

interface WorkItemQuickViewProps {
  workItemId: number | null
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function WorkItemQuickView({ workItemId, open, onOpenChange }: WorkItemQuickViewProps) {
  const { selectedItem, isLoadingItem, fetchById } = useWorkItemsStore()

  useEffect(() => {
    if (workItemId && open) {
      fetchById(workItemId)
    }
  }, [workItemId, open, fetchById])

  const item = workItemId && selectedItem?.id === workItemId ? selectedItem : null

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[520px] max-h-[85vh] overflow-y-auto">
        {isLoadingItem || !item ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
          </div>
        ) : (
          <>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2 pr-6">
                <span className="font-mono text-sm text-muted-foreground">{item.ref_no}</span>
                {item.priority_item && (
                  <Badge variant="destructive" className="ml-1">
                    <Flag className="mr-1 h-3 w-3" />
                    Priority
                  </Badge>
                )}
              </DialogTitle>
            </DialogHeader>

            <div className="space-y-4">
              {/* Status row */}
              <div className="flex flex-wrap items-center gap-3">
                <RAGBadge status={item.rag_status} variant="pill" />
                <Badge variant="outline">
                  {item.current_status ? (STATUS_LABELS[item.current_status] || item.current_status) : '-'}
                </Badge>
                {item.bau_or_transformative === 'transformative' && (
                  <Badge variant="secondary">
                    <AlertCircle className="mr-1 h-3 w-3" />
                    Transformative
                  </Badge>
                )}
                {item.impact_level && (
                  <Badge variant="outline" className={
                    item.impact_level === 'high' ? 'border-red-300 text-red-700' :
                    item.impact_level === 'low' ? 'border-green-300 text-green-700' :
                    ''
                  }>
                    {item.impact_level.charAt(0).toUpperCase() + item.impact_level.slice(1)} Impact
                  </Badge>
                )}
              </div>

              {/* Description */}
              {item.description && (
                <p className="text-sm text-muted-foreground leading-relaxed">
                  {item.description}
                </p>
              )}

              <Separator />

              {/* Details grid */}
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <span className="text-muted-foreground">Department</span>
                  <p className="font-medium">{item.department || '-'}</p>
                </div>
                <div>
                  <span className="text-muted-foreground">Responsible</span>
                  <p className="font-medium flex items-center gap-1">
                    <User className="h-3 w-3" />
                    {item.responsible_party?.full_name || '-'}
                  </p>
                </div>
                <div>
                  <span className="text-muted-foreground">Deadline</span>
                  <p className="font-medium flex items-center gap-1">
                    <Calendar className="h-3 w-3" />
                    {formatDate(item.deadline)}
                  </p>
                </div>
                <div>
                  <span className="text-muted-foreground">Completion</span>
                  <p className="font-medium">{formatDate(item.completion_date)}</p>
                </div>
              </div>

              {/* Tags */}
              {item.tags && item.tags.length > 0 && (
                <div className="flex flex-wrap items-center gap-1.5">
                  <Tag className="h-3.5 w-3.5 text-muted-foreground" />
                  {item.tags.map((t) => (
                    <Badge key={t} variant="outline" className="text-xs">
                      {t}
                    </Badge>
                  ))}
                </div>
              )}

              {/* Monthly update */}
              {item.monthly_update && (
                <>
                  <Separator />
                  <div>
                    <span className="text-xs font-medium text-muted-foreground uppercase">Latest Update</span>
                    <p className="text-sm mt-1">{item.monthly_update}</p>
                  </div>
                </>
              )}

              <Separator />

              {/* Action buttons */}
              <div className="flex gap-2">
                <Button asChild className="flex-1">
                  <Link to={`/tasks/${item.id}`}>
                    <ExternalLink className="mr-2 h-4 w-4" />
                    View Full Details
                  </Link>
                </Button>
                <Button variant="outline" asChild>
                  <Link to={`/tasks/${item.id}/edit`}>
                    <Edit className="mr-2 h-4 w-4" />
                    Edit
                  </Link>
                </Button>
              </div>
            </div>
          </>
        )}
      </DialogContent>
    </Dialog>
  )
}
