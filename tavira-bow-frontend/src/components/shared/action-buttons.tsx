import { Eye, Pencil, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

interface ActionButtonsProps {
  onView?: () => void
  onEdit?: () => void
  onDelete?: () => void
  showView?: boolean
  showEdit?: boolean
  showDelete?: boolean
  className?: string
}

export function ActionButtons({
  onView,
  onEdit,
  onDelete,
  showView = true,
  showEdit = true,
  showDelete = true,
  className,
}: ActionButtonsProps) {
  return (
    <div
      className={cn(
        'flex justify-end gap-1 opacity-50 group-hover:opacity-100 transition-opacity',
        className
      )}
    >
      {showView && onView && (
        <Button
          variant="ghost"
          size="icon"
          className="h-8 w-8"
          onClick={(e) => {
            e.stopPropagation()
            onView()
          }}
          title="View"
        >
          <Eye className="h-4 w-4" />
        </Button>
      )}
      {showEdit && onEdit && (
        <Button
          variant="ghost"
          size="icon"
          className="h-8 w-8"
          onClick={(e) => {
            e.stopPropagation()
            onEdit()
          }}
          title="Edit"
        >
          <Pencil className="h-4 w-4" />
        </Button>
      )}
      {showDelete && onDelete && (
        <Button
          variant="ghost"
          size="icon"
          className="h-8 w-8 hover:bg-destructive hover:text-destructive-foreground"
          onClick={(e) => {
            e.stopPropagation()
            onDelete()
          }}
          title="Delete"
        >
          <Trash2 className="h-4 w-4" />
        </Button>
      )}
    </div>
  )
}
