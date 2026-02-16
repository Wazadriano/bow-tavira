import { Check, AlertCircle, HelpCircle } from 'lucide-react'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import type { ColumnMapping, ImportType } from '@/stores/import'
import { targetFields } from '@/stores/import'

interface ColumnMapperProps {
  importType: ImportType
  mapping: ColumnMapping[]
  onUpdateMapping: (index: number, targetField: string) => void
}

export function ColumnMapper({
  importType,
  mapping,
  onUpdateMapping,
}: ColumnMapperProps) {
  const fields = targetFields[importType]
  const usedFields = mapping.map((m) => m.targetField).filter(Boolean)

  const getMappingStatus = (m: ColumnMapping) => {
    if (!m.targetField) return 'unmapped'
    const field = fields.find((f) => f.field === m.targetField)
    if (field?.required) return 'required-mapped'
    return 'optional-mapped'
  }

  const requiredFields = fields.filter((f) => f.required)
  const mappedRequired = requiredFields.filter((f) =>
    mapping.some((m) => m.targetField === f.field)
  )
  const allRequiredMapped = mappedRequired.length === requiredFields.length

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="font-semibold">Column Mapping</h3>
        <div className="flex items-center gap-2">
          {allRequiredMapped ? (
            <Badge variant="default" className="bg-green-500">
              <Check className="h-3 w-3 mr-1" />
              Required fields mapped
            </Badge>
          ) : (
            <Badge variant="destructive">
              <AlertCircle className="h-3 w-3 mr-1" />
              {requiredFields.length - mappedRequired.length} required fields missing
            </Badge>
          )}
        </div>
      </div>

      <div className="border rounded-lg divide-y">
        <div className="grid grid-cols-3 gap-4 p-3 bg-muted/50 font-medium text-sm">
          <div>Source Column</div>
          <div>Target Field</div>
          <div>Status</div>
        </div>

        {mapping.map((m, index) => {
          const status = getMappingStatus(m)
          const selectedField = fields.find((f) => f.field === m.targetField)

          return (
            <div key={index} className="grid grid-cols-3 gap-4 p-3 items-center">
              <div className="font-mono text-sm">{m.sourceColumn}</div>
              <div>
                <Select
                  value={m.targetField || 'none'}
                  onValueChange={(value) =>
                    onUpdateMapping(index, value === 'none' ? '' : value)
                  }
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Select a field" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">-- Skip --</SelectItem>
                    {fields.map((field) => (
                      <SelectItem
                        key={field.field}
                        value={field.field}
                        disabled={
                          usedFields.includes(field.field) &&
                          m.targetField !== field.field
                        }
                      >
                        {field.label}
                        {field.required && ' *'}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="flex items-center gap-2">
                {status === 'unmapped' && (
                  <Badge variant="secondary">Unmapped</Badge>
                )}
                {status === 'required-mapped' && (
                  <Badge variant="default" className="bg-green-500">
                    <Check className="h-3 w-3 mr-1" />
                    Required
                  </Badge>
                )}
                {status === 'optional-mapped' && (
                  <Badge variant="outline">
                    <Check className="h-3 w-3 mr-1" />
                    Optional
                  </Badge>
                )}
                {m.detected && (
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger>
                        <HelpCircle className="h-4 w-4 text-muted-foreground" />
                      </TooltipTrigger>
                      <TooltipContent>
                        <p>Automatically detected mapping</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                )}
              </div>
            </div>
          )
        })}
      </div>

      <div className="text-sm text-muted-foreground">
        <p>* Required fields</p>
      </div>
    </div>
  )
}
