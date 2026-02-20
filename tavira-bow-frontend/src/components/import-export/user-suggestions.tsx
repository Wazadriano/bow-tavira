import { useState } from 'react'
import { Check, X, ChevronDown, ChevronUp, User } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import type { UserSuggestionGroup } from '@/stores/import'

interface UserSuggestionsProps {
  suggestions: UserSuggestionGroup[]
  overrides: Record<string, number>
  onAccept: (sourceValue: string, userId: number) => void
  onReject: (sourceValue: string) => void
}

export function UserSuggestions({ suggestions, overrides, onAccept, onReject }: UserSuggestionsProps) {
  const [collapsed, setCollapsed] = useState(false)

  if (suggestions.length === 0) {
    return null
  }

  const fuzzyMatches = suggestions.filter((s) => s.status === 'fuzzy_match')
  const noMatches = suggestions.filter((s) => s.status === 'no_match')
  const exactMatches = suggestions.filter((s) => s.status === 'exact_match')

  const needsAttention = fuzzyMatches.length + noMatches.length

  return (
    <div className="border rounded-lg p-4 space-y-3">
      <button
        onClick={() => setCollapsed(!collapsed)}
        className="flex items-center justify-between w-full text-left"
      >
        <div className="flex items-center gap-2">
          <User className="h-4 w-4" />
          <span className="font-medium">User Name Resolution</span>
          {needsAttention > 0 && (
            <Badge variant="secondary">{needsAttention} need review</Badge>
          )}
          <Badge variant="outline" className="text-xs">
            {exactMatches.length} exact, {fuzzyMatches.length} fuzzy, {noMatches.length} unknown
          </Badge>
        </div>
        {collapsed ? <ChevronDown className="h-4 w-4" /> : <ChevronUp className="h-4 w-4" />}
      </button>

      {!collapsed && (
        <div className="space-y-2 pt-2 border-t">
          {suggestions.map((group) => {
            const isAccepted = group.source_value in overrides
            const acceptedUserId = overrides[group.source_value]
            const acceptedSuggestion = group.suggestions.find((s) => s.user_id === acceptedUserId)

            return (
              <div
                key={`${group.field}-${group.source_value}`}
                className={`flex items-center justify-between p-2 rounded border ${
                  group.status === 'exact_match'
                    ? 'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950'
                    : group.status === 'fuzzy_match'
                      ? 'border-orange-200 bg-orange-50 dark:border-orange-900 dark:bg-orange-950'
                      : 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950'
                }`}
              >
                <div className="flex items-center gap-3 flex-1 min-w-0">
                  <StatusBadge status={group.status} />
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="font-mono text-sm truncate">{group.source_value}</span>
                      {group.suggestions.length > 0 && (
                        <>
                          <span className="text-muted-foreground">-&gt;</span>
                          <span className="text-sm font-medium">
                            {isAccepted
                              ? acceptedSuggestion?.full_name ?? group.suggestions[0].full_name
                              : group.suggestions[0].full_name}
                          </span>
                          <span className="text-xs text-muted-foreground">
                            ({isAccepted
                              ? acceptedSuggestion?.confidence ?? group.suggestions[0].confidence
                              : group.suggestions[0].confidence}%)
                          </span>
                        </>
                      )}
                    </div>
                    <div className="text-xs text-muted-foreground">
                      {fieldLabel(group.field)} - rows {group.rows.join(', ')}
                    </div>
                  </div>
                </div>

                {group.status !== 'exact_match' && group.suggestions.length > 0 && (
                  <div className="flex items-center gap-1 ml-2 shrink-0">
                    {group.suggestions.length > 1 && !isAccepted && (
                      <select
                        className="text-xs border rounded px-1 py-0.5"
                        onChange={(e) => onAccept(group.source_value, Number(e.target.value))}
                        defaultValue=""
                      >
                        <option value="" disabled>Choose...</option>
                        {group.suggestions.map((s) => (
                          <option key={s.user_id} value={s.user_id}>
                            {s.full_name} ({s.confidence}%)
                          </option>
                        ))}
                      </select>
                    )}
                    {!isAccepted ? (
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-7 px-2"
                        onClick={() => onAccept(group.source_value, group.suggestions[0].user_id)}
                      >
                        <Check className="h-3 w-3" />
                      </Button>
                    ) : (
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-7 px-2"
                        onClick={() => onReject(group.source_value)}
                      >
                        <X className="h-3 w-3" />
                      </Button>
                    )}
                  </div>
                )}

                {group.status === 'no_match' && (
                  <Badge variant="destructive" className="text-xs ml-2">
                    No match found
                  </Badge>
                )}
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}

function StatusBadge({ status }: { status: string }) {
  if (status === 'exact_match') {
    return <Badge className="bg-green-600 text-white text-xs">Exact</Badge>
  }
  if (status === 'fuzzy_match') {
    return <Badge className="bg-orange-500 text-white text-xs">Fuzzy</Badge>
  }
  return <Badge variant="destructive" className="text-xs">Unknown</Badge>
}

function fieldLabel(field: string): string {
  const labels: Record<string, string> = {
    responsible_party_id: 'Responsible Party',
    department_head_id: 'Department Head',
    back_up_person_id: 'Back Up Person',
  }
  return labels[field] ?? field
}
