'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { Header } from '@/components/layout/header'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { api } from '@/lib/api'
import { Grid3X3, Info } from 'lucide-react'

interface HeatmapCell {
  impact: number
  probability: number
  risks: Array<{
    id: number
    name: string
    code: string
    score: number
  }>
}

interface HeatmapData {
  cells: HeatmapCell[]
  total_risks: number
  type: 'inherent' | 'residual'
}

export default function RiskHeatmapPage() {
  const router = useRouter()
  const [heatmapData, setHeatmapData] = useState<HeatmapData | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [heatmapType, setHeatmapType] = useState<'inherent' | 'residual'>('inherent')
  const [selectedCell, setSelectedCell] = useState<HeatmapCell | null>(null)

  useEffect(() => {
    const fetchHeatmap = async () => {
      setIsLoading(true)
      try {
        const response = await api.get<{ data: HeatmapData }>(
          `/risks/heatmap?type=${heatmapType}`
        )
        setHeatmapData(response.data.data)
      } catch {
        // Fallback mock data
        const mockCells: HeatmapCell[] = []
        for (let impact = 1; impact <= 5; impact++) {
          for (let prob = 1; prob <= 5; prob++) {
            const risks = []
            // Add some mock risks to certain cells
            if (impact >= 4 && prob >= 4) {
              risks.push({ id: 1, name: 'Critical System Failure', code: 'RSK-001', score: impact * prob })
            }
            if (impact === 3 && prob === 4) {
              risks.push({ id: 2, name: 'Data Breach', code: 'RSK-002', score: impact * prob })
              risks.push({ id: 3, name: 'Compliance Violation', code: 'RSK-003', score: impact * prob })
            }
            if (impact === 4 && prob === 3) {
              risks.push({ id: 4, name: 'Vendor Dependency', code: 'RSK-004', score: impact * prob })
            }
            if (impact === 2 && prob === 3) {
              risks.push({ id: 5, name: 'Minor Process Gap', code: 'RSK-005', score: impact * prob })
            }
            mockCells.push({ impact, probability: prob, risks })
          }
        }
        setHeatmapData({
          cells: mockCells,
          total_risks: 45,
          type: heatmapType,
        })
      } finally {
        setIsLoading(false)
      }
    }
    fetchHeatmap()
  }, [heatmapType])

  const getCellColor = (impact: number, probability: number) => {
    const score = impact * probability
    if (score >= 20) return 'bg-red-500 hover:bg-red-600'
    if (score >= 12) return 'bg-orange-500 hover:bg-orange-600'
    if (score >= 8) return 'bg-amber-400 hover:bg-amber-500'
    if (score >= 4) return 'bg-yellow-300 hover:bg-yellow-400'
    return 'bg-green-400 hover:bg-green-500'
  }

  const getCellByPosition = (impact: number, probability: number) => {
    return heatmapData?.cells.find(
      (c) => c.impact === impact && c.probability === probability
    )
  }

  const impactLabels = ['Negligible', 'Minor', 'Moderate', 'Major', 'Severe']
  const probabilityLabels = ['Rare', 'Unlikely', 'Possible', 'Likely', 'Almost Certain']

  return (
    <>
      <Header
        title="Heatmap"
        description="Matrice des risques 5x5"
        actions={
          <Select
            value={heatmapType}
            onValueChange={(v) => setHeatmapType(v as 'inherent' | 'residual')}
          >
            <SelectTrigger className="w-[180px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="inherent">Risque Inherent</SelectItem>
              <SelectItem value="residual">Risque Residuel</SelectItem>
            </SelectContent>
          </Select>
        }
      />

      <div className="p-6 space-y-6">
        <div className="grid gap-6 lg:grid-cols-3">
          {/* Heatmap Grid */}
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Grid3X3 className="h-5 w-5" />
                Matrice {heatmapType === 'inherent' ? 'Inherente' : 'Residuelle'}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {isLoading ? (
                <div className="animate-pulse">
                  <div className="h-[400px] bg-muted rounded-lg" />
                </div>
              ) : (
                <div className="relative">
                  {/* Y-axis label */}
                  <div className="absolute -left-12 top-1/2 -translate-y-1/2 -rotate-90 text-sm font-medium text-muted-foreground whitespace-nowrap">
                    IMPACT
                  </div>

                  <div className="ml-8">
                    {/* Grid */}
                    <div className="grid grid-cols-6 gap-1">
                      {/* Header row */}
                      <div className="h-10" />
                      {[1, 2, 3, 4, 5].map((prob) => (
                        <div
                          key={`header-${prob}`}
                          className="h-10 flex items-center justify-center text-xs font-medium text-muted-foreground"
                        >
                          {prob}
                        </div>
                      ))}

                      {/* Grid rows (impact 5 to 1, top to bottom) */}
                      {[5, 4, 3, 2, 1].map((impact) => (
                        <>
                          <div
                            key={`label-${impact}`}
                            className="h-16 flex items-center justify-center text-xs font-medium text-muted-foreground"
                          >
                            {impact}
                          </div>
                          {[1, 2, 3, 4, 5].map((prob) => {
                            const cell = getCellByPosition(impact, prob)
                            const riskCount = cell?.risks.length || 0
                            return (
                              <button
                                key={`cell-${impact}-${prob}`}
                                className={`h-16 rounded-md flex items-center justify-center text-white font-bold transition-colors ${getCellColor(impact, prob)}`}
                                onClick={() => cell && setSelectedCell(cell)}
                              >
                                {riskCount > 0 && (
                                  <span className="bg-white/30 rounded-full px-2 py-0.5 text-sm">
                                    {riskCount}
                                  </span>
                                )}
                              </button>
                            )
                          })}
                        </>
                      ))}
                    </div>

                    {/* X-axis label */}
                    <div className="text-center mt-2 text-sm font-medium text-muted-foreground">
                      PROBABILITE
                    </div>
                  </div>
                </div>
              )}

              {/* Legend */}
              <div className="mt-6 flex items-center justify-center gap-4">
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 rounded bg-green-400" />
                  <span className="text-sm">Faible (1-3)</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 rounded bg-yellow-300" />
                  <span className="text-sm">Modere (4-7)</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 rounded bg-amber-400" />
                  <span className="text-sm">Eleve (8-11)</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 rounded bg-orange-500" />
                  <span className="text-sm">Tres Eleve (12-19)</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 rounded bg-red-500" />
                  <span className="text-sm">Critique (20-25)</span>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Selected Cell Details */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Info className="h-5 w-5" />
                Details
              </CardTitle>
            </CardHeader>
            <CardContent>
              {selectedCell ? (
                <div className="space-y-4">
                  <div className="p-4 rounded-lg bg-muted">
                    <div className="grid grid-cols-2 gap-2 text-sm">
                      <div>
                        <span className="text-muted-foreground">Impact:</span>
                        <p className="font-medium">
                          {selectedCell.impact} - {impactLabels[selectedCell.impact - 1]}
                        </p>
                      </div>
                      <div>
                        <span className="text-muted-foreground">Probabilite:</span>
                        <p className="font-medium">
                          {selectedCell.probability} - {probabilityLabels[selectedCell.probability - 1]}
                        </p>
                      </div>
                      <div className="col-span-2">
                        <span className="text-muted-foreground">Score:</span>
                        <p className="font-medium text-lg">
                          {selectedCell.impact * selectedCell.probability}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div>
                    <h4 className="font-medium mb-2">
                      Risques ({selectedCell.risks.length})
                    </h4>
                    {selectedCell.risks.length > 0 ? (
                      <div className="space-y-2">
                        {selectedCell.risks.map((risk) => (
                          <div
                            key={risk.id}
                            className="p-3 rounded-lg border cursor-pointer hover:bg-muted/50"
                            onClick={() => router.push(`/risks/${risk.id}`)}
                          >
                            <div className="flex items-center justify-between">
                              <Badge variant="outline" className="font-mono">
                                {risk.code}
                              </Badge>
                              <Badge>{risk.score}</Badge>
                            </div>
                            <p className="text-sm mt-1">{risk.name}</p>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="text-sm text-muted-foreground">
                        Aucun risque dans cette cellule
                      </p>
                    )}
                  </div>
                </div>
              ) : (
                <div className="text-center py-8 text-muted-foreground">
                  <Info className="h-8 w-8 mx-auto mb-2 opacity-50" />
                  <p>Cliquez sur une cellule pour voir les details</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Impact/Probability Scales */}
        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Echelle d'Impact</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {impactLabels.map((label, index) => (
                  <div key={label} className="flex items-center gap-3 p-2 rounded-lg hover:bg-muted/50">
                    <Badge variant="outline" className="w-8 justify-center">{index + 1}</Badge>
                    <span className="font-medium">{label}</span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Echelle de Probabilite</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {probabilityLabels.map((label, index) => (
                  <div key={label} className="flex items-center gap-3 p-2 rounded-lg hover:bg-muted/50">
                    <Badge variant="outline" className="w-8 justify-center">{index + 1}</Badge>
                    <span className="font-medium">{label}</span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </>
  )
}
