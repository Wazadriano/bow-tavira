'use client'

import { Loader2, CheckCircle, AlertCircle, Upload, FileSearch } from 'lucide-react'
import { Progress } from '@/components/ui/progress'
import type { ImportProgress as ImportProgressType } from '@/stores/import'
import { cn } from '@/lib/utils'

interface ImportProgressProps {
  progress: ImportProgressType
}

export function ImportProgress({ progress }: ImportProgressProps) {
  const getStatusIcon = () => {
    switch (progress.status) {
      case 'uploading':
        return <Upload className="h-5 w-5 animate-pulse" />
      case 'previewing':
        return <FileSearch className="h-5 w-5 animate-pulse" />
      case 'importing':
        return <Loader2 className="h-5 w-5 animate-spin" />
      case 'completed':
        return <CheckCircle className="h-5 w-5 text-green-500" />
      case 'error':
        return <AlertCircle className="h-5 w-5 text-destructive" />
      default:
        return null
    }
  }

  const getStatusColor = () => {
    switch (progress.status) {
      case 'completed':
        return 'text-green-600'
      case 'error':
        return 'text-destructive'
      default:
        return 'text-primary'
    }
  }

  if (progress.status === 'idle') return null

  return (
    <div className="border rounded-lg p-4 space-y-3">
      <div className="flex items-center gap-3">
        {getStatusIcon()}
        <div className="flex-1">
          <p className={cn('font-medium', getStatusColor())}>{progress.message}</p>
          {progress.status === 'importing' && progress.total > 0 && (
            <p className="text-sm text-muted-foreground">
              {progress.processed} / {progress.total} lignes traitees
              {progress.errors > 0 && ` (${progress.errors} erreurs)`}
            </p>
          )}
        </div>
      </div>

      {(progress.status === 'importing' || progress.status === 'uploading') && (
        <Progress
          value={progress.progress > 0 ? progress.progress : (progress.total > 0 ? (progress.processed / progress.total) * 100 : undefined)}
          className="h-2"
        />
      )}

      {progress.status === 'completed' && (
        <div className="grid grid-cols-3 gap-4 pt-2">
          <div className="text-center p-3 bg-green-500/10 rounded-lg">
            <p className="text-2xl font-bold text-green-600">{progress.processed}</p>
            <p className="text-sm text-muted-foreground">Importes</p>
          </div>
          <div className="text-center p-3 bg-muted rounded-lg">
            <p className="text-2xl font-bold">{progress.total - progress.processed - progress.errors}</p>
            <p className="text-sm text-muted-foreground">Ignores</p>
          </div>
          <div className="text-center p-3 bg-destructive/10 rounded-lg">
            <p className="text-2xl font-bold text-destructive">{progress.errors}</p>
            <p className="text-sm text-muted-foreground">Erreurs</p>
          </div>
        </div>
      )}
    </div>
  )
}
