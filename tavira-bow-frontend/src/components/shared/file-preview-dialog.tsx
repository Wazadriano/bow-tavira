import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Download } from 'lucide-react'
import { Button } from '@/components/ui/button'

interface FilePreviewDialogProps {
  url: string
  filename: string
  mimeType: string
  open: boolean
  onClose: () => void
}

export function FilePreviewDialog({
  url,
  filename,
  mimeType,
  open,
  onClose,
}: FilePreviewDialogProps) {
  const renderPreview = () => {
    if (mimeType.startsWith('image/')) {
      return (
        <img
          src={url}
          alt={filename}
          className="max-w-full max-h-[70vh] object-contain"
        />
      )
    }

    if (mimeType.includes('pdf')) {
      return (
        <iframe
          src={url}
          className="w-full h-[70vh]"
          title={filename}
        />
      )
    }

    return (
      <div className="flex flex-col items-center gap-4 py-8">
        <p className="text-sm text-muted-foreground">
          Preview not available for this file type.
        </p>
        <Button variant="outline" asChild>
          <a href={url} target="_blank" rel="noopener noreferrer">
            <Download className="h-4 w-4 mr-2" />
            Download
          </a>
        </Button>
      </div>
    )
  }

  return (
    <Dialog open={open} onOpenChange={(isOpen) => { if (!isOpen) onClose() }}>
      <DialogContent className="max-w-4xl">
        <DialogHeader>
          <DialogTitle className="truncate">{filename}</DialogTitle>
        </DialogHeader>
        {renderPreview()}
      </DialogContent>
    </Dialog>
  )
}
