'use client'

import { cn } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import type { HeatmapData } from '@/types'

interface RiskHeatmapProps {
  data: HeatmapData | null
  type: 'inherent' | 'residual'
  onCellClick?: (impact: number, probability: number) => void
}

const IMPACT_LABELS = ['Negligeable', 'Mineur', 'Modere', 'Majeur', 'Critique']
const PROBABILITY_LABELS = ['Rare', 'Peu probable', 'Possible', 'Probable', 'Quasi certain']

function getCellColor(impact: number, probability: number): string {
  const score = impact * probability

  if (score <= 4) return 'bg-green-500 hover:bg-green-600'
  if (score <= 9) return 'bg-amber-400 hover:bg-amber-500'
  if (score <= 15) return 'bg-orange-500 hover:bg-orange-600'
  return 'bg-red-500 hover:bg-red-600'
}

export function RiskHeatmap({ data, type, onCellClick }: RiskHeatmapProps) {
  // Build a map of counts from the data
  const countMap = new Map<string, number>()

  if (data?.matrix) {
    data.matrix.forEach((cell) => {
      const key = `${cell.impact}-${cell.probability}`
      countMap.set(key, cell.count)
    })
  }

  const getCount = (impact: number, probability: number): number => {
    const key = `${impact}-${probability}`
    return countMap.get(key) || 0
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>
          Matrice des risques - {type === 'inherent' ? 'Inherent' : 'Residuel'}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="overflow-x-auto">
          <div className="min-w-[500px]">
            {/* Header row - Impact labels */}
            <div className="mb-2 flex items-end">
              <div className="w-24" /> {/* Empty corner */}
              <div className="flex flex-1 justify-around text-center">
                {IMPACT_LABELS.map((label, i) => (
                  <div key={i} className="flex-1 px-1">
                    <span className="text-xs font-medium text-muted-foreground">
                      {label}
                    </span>
                    <div className="mt-1 text-xs text-muted-foreground">({i + 1})</div>
                  </div>
                ))}
              </div>
            </div>

            {/* Grid rows - from highest probability to lowest */}
            <div className="flex">
              {/* Y-axis label */}
              <div className="flex w-6 items-center justify-center">
                <span className="-rotate-90 whitespace-nowrap text-xs font-medium text-muted-foreground">
                  Probabilite
                </span>
              </div>

              {/* Probability labels column */}
              <div className="flex w-18 flex-col justify-around">
                {[...PROBABILITY_LABELS].reverse().map((label, i) => (
                  <div key={i} className="flex h-16 items-center justify-end pr-2">
                    <div className="text-right">
                      <span className="text-xs font-medium text-muted-foreground">
                        {label}
                      </span>
                      <div className="text-xs text-muted-foreground">({5 - i})</div>
                    </div>
                  </div>
                ))}
              </div>

              {/* Heatmap grid */}
              <div className="flex-1">
                {[5, 4, 3, 2, 1].map((probability) => (
                  <div key={probability} className="flex gap-1 mb-1">
                    {[1, 2, 3, 4, 5].map((impact) => {
                      const count = getCount(impact, probability)
                      return (
                        <button
                          key={`${impact}-${probability}`}
                          onClick={() => onCellClick?.(impact, probability)}
                          className={cn(
                            'flex h-14 flex-1 items-center justify-center rounded-md text-white font-semibold transition-colors',
                            getCellColor(impact, probability),
                            count > 0 ? 'cursor-pointer' : 'cursor-default opacity-80'
                          )}
                        >
                          {count > 0 ? count : ''}
                        </button>
                      )
                    })}
                  </div>
                ))}
              </div>
            </div>

            {/* X-axis label */}
            <div className="mt-2 text-center">
              <span className="text-xs font-medium text-muted-foreground">Impact</span>
            </div>
          </div>
        </div>

        {/* Legend */}
        <div className="mt-6 flex items-center justify-center gap-6">
          <div className="flex items-center gap-2">
            <div className="h-4 w-4 rounded bg-green-500" />
            <span className="text-xs">Faible (1-4)</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="h-4 w-4 rounded bg-amber-400" />
            <span className="text-xs">Modere (5-9)</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="h-4 w-4 rounded bg-orange-500" />
            <span className="text-xs">Eleve (10-15)</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="h-4 w-4 rounded bg-red-500" />
            <span className="text-xs">Critique (16-25)</span>
          </div>
        </div>

        {/* Stats */}
        {data?.summary && (
          <div className="mt-4 flex justify-center gap-8 border-t pt-4">
            <div className="text-center">
              <p className="text-2xl font-bold">{data.summary.total_risks || 0}</p>
              <p className="text-xs text-muted-foreground">Risques total</p>
            </div>
            <div className="text-center">
              <p className="text-2xl font-bold text-red-600">{data.summary.by_rag?.red || 0}</p>
              <p className="text-xs text-muted-foreground">Critiques</p>
            </div>
            <div className="text-center">
              <p className="text-2xl font-bold text-orange-600">{data.summary.by_rag?.amber || 0}</p>
              <p className="text-xs text-muted-foreground">Eleves</p>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  )
}
