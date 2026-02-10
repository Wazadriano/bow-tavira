'use client'

import { useEffect, useState } from 'react'
import { Header } from '@/components/layout/header'
import { BarChart, DoughnutChart, StatsCard, StatsGrid } from '@/components/charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { api } from '@/lib/api'
import {
  Shield,
  AlertTriangle,
  CheckCircle,
  Clock,
  Target,
  TrendingUp,
} from 'lucide-react'

interface RiskStats {
  total_risks: number
  high_risks: number
  medium_risks: number
  low_risks: number
  open_actions: number
  overdue_actions: number
  by_theme: Array<{ name: string; code: string; count: number }>
  by_tier: Array<{ name: string; count: number }>
  by_rag: { blue: number; green: number; amber: number; red: number }
  appetite_breaches: Array<{
    id: number
    name: string
    theme: string
    score: number
    appetite: number
  }>
}

export default function RisksDashboardPage() {
  const [stats, setStats] = useState<RiskStats | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await api.get<{ data: RiskStats }>('/risks/dashboard/stats')
        setStats(response.data.data)
      } catch {
        // Fallback mock data
        setStats({
          total_risks: 45,
          high_risks: 8,
          medium_risks: 22,
          low_risks: 15,
          open_actions: 34,
          overdue_actions: 6,
          by_theme: [
            { name: 'Regulatory', code: 'REG', count: 12 },
            { name: 'Governance', code: 'GOV', count: 10 },
            { name: 'Operational', code: 'OPS', count: 15 },
            { name: 'Business', code: 'BUS', count: 5 },
            { name: 'Capital', code: 'CAP', count: 3 },
          ],
          by_tier: [
            { name: 'Tier 1', count: 8 },
            { name: 'Tier 2', count: 15 },
            { name: 'Tier 3', count: 22 },
          ],
          by_rag: { blue: 10, green: 18, amber: 12, red: 5 },
          appetite_breaches: [
            { id: 1, name: 'Data Privacy Non-Compliance', theme: 'REG', score: 20, appetite: 12 },
            { id: 2, name: 'System Availability', theme: 'OPS', score: 16, appetite: 10 },
            { id: 3, name: 'Third Party Dependency', theme: 'OPS', score: 15, appetite: 12 },
          ],
        })
      } finally {
        setIsLoading(false)
      }
    }
    fetchStats()
  }, [])

  if (isLoading || !stats) {
    return (
      <>
        <Header title="Dashboard" description="Statistiques Risk Management" />
        <div className="p-6">
          <div className="animate-pulse space-y-6">
            <div className="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
              {[...Array(6)].map((_, i) => (
                <div key={i} className="h-24 bg-muted rounded-lg" />
              ))}
            </div>
          </div>
        </div>
      </>
    )
  }

  const ragData = [
    { name: 'Blue', value: stats.by_rag.blue, color: '#0ea5e9' },
    { name: 'Green', value: stats.by_rag.green, color: '#22c55e' },
    { name: 'Amber', value: stats.by_rag.amber, color: '#f59e0b' },
    { name: 'Red', value: stats.by_rag.red, color: '#ef4444' },
  ]

  const themeData = stats.by_theme.map((t) => ({
    name: t.code,
    count: t.count,
  }))

  const tierData = stats.by_tier.map((t) => ({
    name: t.name,
    value: t.count,
    color: ['#3b82f6', '#8b5cf6', '#06b6d4'][stats.by_tier.indexOf(t) % 3],
  }))

  return (
    <>
      <Header title="Dashboard" description="Statistiques Risk Management" />

      <div className="p-6 space-y-6">
        {/* KPIs */}
        <StatsGrid columns={6}>
          <StatsCard
            title="Total Risks"
            value={stats.total_risks}
            icon={Shield}
            variant="info"
          />
          <StatsCard
            title="High Risks"
            value={stats.high_risks}
            icon={AlertTriangle}
            variant="danger"
          />
          <StatsCard
            title="Medium Risks"
            value={stats.medium_risks}
            icon={Target}
            variant="warning"
          />
          <StatsCard
            title="Low Risks"
            value={stats.low_risks}
            icon={CheckCircle}
            variant="success"
          />
          <StatsCard
            title="Open Actions"
            value={stats.open_actions}
            icon={Clock}
            variant="info"
          />
          <StatsCard
            title="Overdue Actions"
            value={stats.overdue_actions}
            icon={TrendingUp}
            variant="danger"
          />
        </StatsGrid>

        {/* Charts row */}
        <div className="grid gap-6 lg:grid-cols-2">
          <BarChart
            title="Risks by Theme"
            description="L1 Distribution"
            data={themeData}
            bars={[{ dataKey: 'count', name: 'Risks', color: '#8b5cf6' }]}
          />

          <DoughnutChart
            title="RAG Distribution"
            description="RAG status of risks"
            data={ragData}
          />
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <DoughnutChart
            title="Distribution by Tier"
            description="Criticality level"
            data={tierData}
          />

          {/* Appetite Breaches */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <AlertTriangle className="h-5 w-5 text-red-500" />
                Appetite Breaches
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {stats.appetite_breaches.map((risk) => (
                  <div
                    key={risk.id}
                    className="flex items-center justify-between p-3 rounded-lg border border-red-200 bg-red-50"
                  >
                    <div>
                      <p className="font-medium">{risk.name}</p>
                      <p className="text-sm text-muted-foreground">
                        Theme: {risk.theme}
                      </p>
                    </div>
                    <div className="text-right">
                      <Badge variant="destructive" className="text-lg px-3 py-1">
                        {risk.score}
                      </Badge>
                      <p className="text-xs text-muted-foreground mt-1">
                        Appetite: {risk.appetite}
                      </p>
                    </div>
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
