import { useRef, useState } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Paperclip, Upload, Trash2, Download, FileText, Image, FileSpreadsheet, File, Eye } from 'lucide-react'
import { formatDate } from '@/lib/utils'
import type { Attachment } from '@/types'
import { FilePreviewDialog } from './file-preview-dialog'

interface FileAttachmentsPanelProps {
  files: Attachment[]
  onUpload: (file: File) => Promise<void>
  onDelete: (fileId: number) => Promise<void>
  downloadUrlPrefix: string
}

function getMimeIcon(mimeType: string) {
  if (mimeType.startsWith('image/')) return Image
  if (mimeType.includes('spreadsheet') || mimeType.includes('excel') || mimeType.includes('csv')) return FileSpreadsheet
  if (mimeType.includes('pdf') || mimeType.includes('document') || mimeType.includes('text')) return FileText
  return File
}

export function FileAttachmentsPanel({
  files,
  onUpload,
  onDelete,
  downloadUrlPrefix,
}: FileAttachmentsPanelProps) {
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [previewFile, setPreviewFile] = useState<{ url: string; filename: string; mimeType: string } | null>(null)

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return
    await onUpload(file)
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="flex items-center gap-2">
          <Paperclip className="h-5 w-5" />
          Attachments
          {files.length > 0 && (
            <Badge variant="secondary" className="ml-2">
              {files.length}
            </Badge>
          )}
        </CardTitle>
        <div>
          <input
            ref={fileInputRef}
            type="file"
            className="hidden"
            onChange={handleFileChange}
          />
          <Button
            variant="outline"
            size="sm"
            onClick={() => fileInputRef.current?.click()}
          >
            <Upload className="h-4 w-4 mr-1" />
            Upload
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        {files.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-4">
            No files attached
          </p>
        ) : (
          <div className="space-y-2">
            {files.map((file) => {
              const Icon = getMimeIcon(file.mime_type)
              return (
                <div
                  key={file.id}
                  className="flex items-center justify-between p-3 rounded-lg border bg-muted/30 group"
                >
                  <div className="flex items-center gap-3 flex-1 min-w-0">
                    <Icon className="h-5 w-5 shrink-0 text-muted-foreground" />
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <p className="text-sm font-medium truncate">{file.original_filename}</p>
                        {typeof file.version === 'number' && file.version > 1 && (
                          <Badge variant="outline" className="shrink-0 text-xs">
                            v{file.version}
                          </Badge>
                        )}
                      </div>
                      <p className="text-xs text-muted-foreground">
                        {file.file_size_formatted} - {formatDate(file.created_at)}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-1">
                    {(file.mime_type.startsWith('image/') || file.mime_type.includes('pdf')) && (
                      <Button
                        variant="ghost"
                        size="icon"
                        className="opacity-0 group-hover:opacity-100 transition-opacity"
                        onClick={() =>
                          setPreviewFile({
                            url: `${downloadUrlPrefix}/${file.id}`,
                            filename: file.original_filename,
                            mimeType: file.mime_type,
                          })
                        }
                      >
                        <Eye className="h-4 w-4" />
                      </Button>
                    )}
                    <Button
                      variant="ghost"
                      size="icon"
                      className="opacity-0 group-hover:opacity-100 transition-opacity"
                      asChild
                    >
                      <a
                        href={`${downloadUrlPrefix}/${file.id}`}
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        <Download className="h-4 w-4" />
                      </a>
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="opacity-0 group-hover:opacity-100 transition-opacity"
                      onClick={() => onDelete(file.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              )
            })}
          </div>
        )}
      </CardContent>
      {previewFile && (
        <FilePreviewDialog
          url={previewFile.url}
          filename={previewFile.filename}
          mimeType={previewFile.mimeType}
          open={!!previewFile}
          onClose={() => setPreviewFile(null)}
        />
      )}
    </Card>
  )
}
