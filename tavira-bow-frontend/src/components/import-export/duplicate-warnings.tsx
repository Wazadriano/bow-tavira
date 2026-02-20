import { AlertTriangle, CheckCircle, RefreshCw } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import type { DuplicateWarning } from '@/stores/import'

interface DuplicateWarningsProps {
  duplicates: DuplicateWarning[]
  acknowledged: boolean
  onAcknowledge: () => void
}

export function DuplicateWarnings({ duplicates, acknowledged, onAcknowledge }: DuplicateWarningsProps) {
  if (duplicates.length === 0) return null

  const exactMatches = duplicates.filter((d) =>
    d.matches.some((m) => m.match_type === 'exact_ref')
  )
  const fuzzyMatches = duplicates.filter((d) =>
    d.matches.every((m) => m.match_type !== 'exact_ref')
  )

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-2">
        <AlertTriangle className="h-5 w-5 text-amber-500" />
        <h3 className="font-semibold text-lg">Duplicate Detection</h3>
        <Badge variant="outline" className="text-amber-600">
          {duplicates.length} match{duplicates.length > 1 ? 'es' : ''}
        </Badge>
      </div>

      {exactMatches.length > 0 && (
        <div className="space-y-2">
          <p className="text-sm font-medium flex items-center gap-2">
            <RefreshCw className="h-4 w-4 text-blue-500" />
            Existing records (will be updated): {exactMatches.length}
          </p>
          <div className="max-h-48 overflow-y-auto space-y-1">
            {exactMatches.slice(0, 20).map((dup) => (
              <div
                key={dup.row_number}
                className="flex items-center justify-between px-3 py-2 rounded border bg-blue-50 dark:bg-blue-950/30 text-sm"
              >
                <div className="flex items-center gap-2">
                  <Badge variant="secondary" className="text-xs">
                    Row {dup.row_number}
                  </Badge>
                  <span className="font-mono text-xs">{dup.imported_ref}</span>
                  <span className="text-muted-foreground truncate max-w-[300px]">
                    {dup.imported_name}
                  </span>
                </div>
                <Badge variant="outline" className="text-blue-600">
                  Update
                </Badge>
              </div>
            ))}
            {exactMatches.length > 20 && (
              <p className="text-xs text-muted-foreground pl-3">
                ... and {exactMatches.length - 20} more
              </p>
            )}
          </div>
        </div>
      )}

      {fuzzyMatches.length > 0 && (
        <div className="space-y-2">
          <p className="text-sm font-medium flex items-center gap-2">
            <AlertTriangle className="h-4 w-4 text-amber-500" />
            Potential duplicates (review required): {fuzzyMatches.length}
          </p>
          <div className="max-h-64 overflow-y-auto space-y-2">
            {fuzzyMatches.slice(0, 15).map((dup) => (
              <div
                key={dup.row_number}
                className="px-3 py-2 rounded border border-amber-200 bg-amber-50 dark:bg-amber-950/30"
              >
                <div className="flex items-center gap-2 mb-1">
                  <Badge variant="secondary" className="text-xs">
                    Row {dup.row_number}
                  </Badge>
                  {dup.imported_ref && (
                    <span className="font-mono text-xs">{dup.imported_ref}</span>
                  )}
                  <span className="text-sm truncate max-w-[300px]">
                    {dup.imported_name}
                  </span>
                </div>
                <div className="pl-6 space-y-1">
                  {dup.matches.map((match) => (
                    <div
                      key={match.id}
                      className="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                      <span>Similar to:</span>
                      {match.ref_no && (
                        <span className="font-mono">{match.ref_no}</span>
                      )}
                      <span className="truncate max-w-[250px]">{match.name}</span>
                      <Badge
                        variant={match.confidence >= 80 ? 'destructive' : 'outline'}
                        className="text-xs"
                      >
                        {match.confidence}% match
                      </Badge>
                    </div>
                  ))}
                </div>
              </div>
            ))}
            {fuzzyMatches.length > 15 && (
              <p className="text-xs text-muted-foreground pl-3">
                ... and {fuzzyMatches.length - 15} more potential duplicates
              </p>
            )}
          </div>
        </div>
      )}

      <div className="flex items-center justify-between pt-3 border-t">
        <p className="text-sm text-muted-foreground">
          {exactMatches.length > 0 && `${exactMatches.length} record(s) will be updated. `}
          {fuzzyMatches.length > 0 && `${fuzzyMatches.length} potential duplicate(s) found. `}
          Please review and acknowledge before importing.
        </p>
        {!acknowledged ? (
          <Button onClick={onAcknowledge} variant="outline">
            <CheckCircle className="h-4 w-4 mr-2" />
            I have reviewed, proceed
          </Button>
        ) : (
          <Badge variant="default" className="gap-1">
            <CheckCircle className="h-3 w-3" />
            Acknowledged
          </Badge>
        )}
      </div>
    </div>
  )
}
