'use client'

import { AlertCircle, AlertTriangle, CheckCircle } from 'lucide-react'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import type { ImportPreview, ColumnMapping } from '@/stores/import'
import { cn } from '@/lib/utils'

interface PreviewTableProps {
  preview: ImportPreview
  mapping: ColumnMapping[]
}

export function PreviewTable({ preview, mapping }: PreviewTableProps) {
  const mappedColumns = mapping.filter((m) => m.targetField)

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="font-semibold">Apercu des donnees</h3>
        <div className="flex items-center gap-4 text-sm">
          <span className="flex items-center gap-1">
            <CheckCircle className="h-4 w-4 text-green-500" />
            {preview.valid_rows} valides
          </span>
          {preview.error_rows > 0 && (
            <span className="flex items-center gap-1 text-destructive">
              <AlertCircle className="h-4 w-4" />
              {preview.error_rows} erreurs
            </span>
          )}
          <span className="text-muted-foreground">
            Total: {preview.total_rows} lignes
          </span>
        </div>
      </div>

      <div className="border rounded-lg overflow-hidden">
        <div className="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-16">#</TableHead>
                <TableHead className="w-24">Statut</TableHead>
                {mappedColumns.map((col) => (
                  <TableHead key={col.sourceColumn}>
                    <div className="flex flex-col">
                      <span className="font-medium">{col.targetField}</span>
                      <span className="text-xs text-muted-foreground font-normal">
                        {col.sourceColumn}
                      </span>
                    </div>
                  </TableHead>
                ))}
              </TableRow>
            </TableHeader>
            <TableBody>
              {preview.preview_data.map((row) => {
                const hasErrors = row.errors.length > 0
                const hasWarnings = row.warnings.length > 0

                return (
                  <TableRow
                    key={row.row_number}
                    className={cn(
                      hasErrors && 'bg-destructive/5',
                      hasWarnings && !hasErrors && 'bg-yellow-500/5'
                    )}
                  >
                    <TableCell className="font-mono text-sm">
                      {row.row_number}
                    </TableCell>
                    <TableCell>
                      {hasErrors ? (
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger>
                              <Badge variant="destructive" className="gap-1">
                                <AlertCircle className="h-3 w-3" />
                                Erreur
                              </Badge>
                            </TooltipTrigger>
                            <TooltipContent>
                              <ul className="list-disc pl-4">
                                {row.errors.map((err, i) => (
                                  <li key={i}>{err}</li>
                                ))}
                              </ul>
                            </TooltipContent>
                          </Tooltip>
                        </TooltipProvider>
                      ) : hasWarnings ? (
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger>
                              <Badge
                                variant="outline"
                                className="gap-1 border-yellow-500 text-yellow-600"
                              >
                                <AlertTriangle className="h-3 w-3" />
                                Attention
                              </Badge>
                            </TooltipTrigger>
                            <TooltipContent>
                              <ul className="list-disc pl-4">
                                {row.warnings.map((warn, i) => (
                                  <li key={i}>{warn}</li>
                                ))}
                              </ul>
                            </TooltipContent>
                          </Tooltip>
                        </TooltipProvider>
                      ) : (
                        <Badge
                          variant="outline"
                          className="gap-1 border-green-500 text-green-600"
                        >
                          <CheckCircle className="h-3 w-3" />
                          OK
                        </Badge>
                      )}
                    </TableCell>
                    {mappedColumns.map((col) => (
                      <TableCell
                        key={col.sourceColumn}
                        className="max-w-[200px] truncate"
                      >
                        {row.data[col.sourceColumn] || '-'}
                      </TableCell>
                    ))}
                  </TableRow>
                )
              })}
            </TableBody>
          </Table>
        </div>
      </div>

      {preview.preview_data.length < preview.total_rows && (
        <p className="text-sm text-muted-foreground text-center">
          Affichage des {preview.preview_data.length} premieres lignes sur{' '}
          {preview.total_rows}
        </p>
      )}
    </div>
  )
}
