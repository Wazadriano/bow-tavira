'use client'

import { useEffect, useState } from 'react'
import { BarChart3, TrendingUp, TrendingDown, Minus } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { Progress } from '@/components/ui/progress'
import { api } from '@/lib/api'
import { cn } from '@/lib/utils'

interface AreaStat {
  department: string
  total_tasks: number
  completed: number
  in_progress: number
  overdue: number
  completion_rate: number
  trend: 'up' | 'down' | 'stable'
  rag_distribution: {
    blue: number
    green: number
    amber: number
    red: number
  }
}

interface AreaStatsResponse {
  data: AreaStat[]
}

const trendIcons = {
  up: TrendingUp,
  down: TrendingDown,
  stable: Minus,
}

const trendColors = {
  up: 'text-green-500',
  down: 'text-red-500',
  stable: 'text-muted-foreground',
}

export function AreaStats() {
  const [stats, setStats] = useState<AreaStat[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const fetchStats = async () => {
      try {
        setIsLoading(true)
        const response = await api.get<AreaStatsResponse>('/dashboard/by-area')
        setStats(response.data.data)
      } catch {
        // Fallback to mock data for demo
        setStats([
          {
            department: 'IT',
            total_tasks: 45,
            completed: 32,
            in_progress: 10,
            overdue: 3,
            completion_rate: 71,
            trend: 'up',
            rag_distribution: { blue: 12, green: 20, amber: 10, red: 3 },
          },
          {
            department: 'Finance',
            total_tasks: 28,
            completed: 22,
            in_progress: 4,
            overdue: 2,
            completion_rate: 79,
            trend: 'up',
            rag_distribution: { blue: 8, green: 14, amber: 4, red: 2 },
          },
          {
            department: 'Operations',
            total_tasks: 52,
            completed: 35,
            in_progress: 12,
            overdue: 5,
            completion_rate: 67,
            trend: 'down',
            rag_distribution: { blue: 10, green: 25, amber: 12, red: 5 },
          },
          {
            department: 'Compliance',
            total_tasks: 18,
            completed: 15,
            in_progress: 2,
            overdue: 1,
            completion_rate: 83,
            trend: 'stable',
            rag_distribution: { blue: 6, green: 9, amber: 2, red: 1 },
          },
        ])
      } finally {
        setIsLoading(false)
      }
    }

    fetchStats()
  }, [])

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <BarChart3 className="h-5 w-5" />
            Performance par Departement
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="space-y-2">
              <Skeleton className="h-4 w-1/4" />
              <Skeleton className="h-2 w-full" />
              <div className="flex gap-2">
                <Skeleton className="h-4 w-16" />
                <Skeleton className="h-4 w-16" />
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <BarChart3 className="h-5 w-5" />
          Performance par Departement
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-6">
          {stats.map((stat) => {
            const TrendIcon = trendIcons[stat.trend]
            const total =
              stat.rag_distribution.blue +
              stat.rag_distribution.green +
              stat.rag_distribution.amber +
              stat.rag_distribution.red

            return (
              <div key={stat.department} className="space-y-2">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="font-medium">{stat.department}</span>
                    <TrendIcon
                      className={cn('h-4 w-4', trendColors[stat.trend])}
                    />
                  </div>
                  <span className="text-sm text-muted-foreground">
                    {stat.completion_rate}% complete
                  </span>
                </div>

                {/* RAG Distribution Bar */}
                <div className="flex h-2 overflow-hidden rounded-full bg-muted">
                  <div
                    className="bg-sky-500 transition-all"
                    style={{
                      width: `${(stat.rag_distribution.blue / total) * 100}%`,
                    }}
                  />
                  <div
                    className="bg-green-500 transition-all"
                    style={{
                      width: `${(stat.rag_distribution.green / total) * 100}%`,
                    }}
                  />
                  <div
                    className="bg-amber-500 transition-all"
                    style={{
                      width: `${(stat.rag_distribution.amber / total) * 100}%`,
                    }}
                  />
                  <div
                    className="bg-red-500 transition-all"
                    style={{
                      width: `${(stat.rag_distribution.red / total) * 100}%`,
                    }}
                  />
                </div>

                {/* Stats row */}
                <div className="flex items-center gap-4 text-xs text-muted-foreground">
                  <span>{stat.total_tasks} taches</span>
                  <span className="text-green-600">
                    {stat.completed} terminees
                  </span>
                  <span className="text-blue-600">
                    {stat.in_progress} en cours
                  </span>
                  {stat.overdue > 0 && (
                    <span className="text-red-600">
                      {stat.overdue} en retard
                    </span>
                  )}
                </div>
              </div>
            )
          })}
        </div>
      </CardContent>
    </Card>
  )
}
