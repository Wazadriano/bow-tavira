import { useEffect, useState } from 'react'
import { Header } from '@/components/layout/header'
import { BarChart, DoughnutChart, StatsCard, StatsGrid } from '@/components/charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { api } from '@/lib/api'
import { ErrorState } from '@/components/shared'
import {
  Shield,
  AlertTriangle,
  CheckCircle,
  Clock,
  Target,
  TrendingUp,
  Download,
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

const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export default function RisksDashboardPage() {
  const [stats, setStats] = useState<RiskStats | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await api.get<{ data: RiskStats }>('/risks/dashboard')
        setStats(response.data.data ?? response.data)
      } catch {
        setStats(null)
      } finally {
        setIsLoading(false)
      }
    }
    fetchStats()
  }, [])

  if (isLoading) {
    return (
      <>
        <Header
          title="Dashboard"
          description="Risk Management Statistics"
          actions={
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                  <Download className="mr-2 h-4 w-4" />
                  Export
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => window.open(`${apiBase}/export/risks`, '_blank')}>
                  Export Excel
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => window.open(`${apiBase}/reports/risks`, '_blank')}>
                  Export PDF
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          }
        />
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

  if (!stats) {
    return (
      <>
        <Header
          title="Dashboard"
          description="Risk Management Statistics"
          actions={
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                  <Download className="mr-2 h-4 w-4" />
                  Export
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => window.open(`${apiBase}/export/risks`, '_blank')}>
                  Export Excel
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => window.open(`${apiBase}/reports/risks`, '_blank')}>
                  Export PDF
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          }
        />
        <div className="p-6">
          <ErrorState
            title="Data unavailable"
            description="Unable to load risk statistics. Check the API connection."
          />
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
      <Header
          title="Dashboard"
          description="Risk Management Statistics"
          actions={
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                  <Download className="mr-2 h-4 w-4" />
                  Export
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => window.open(`${apiBase}/export/risks`, '_blank')}>
                  Export Excel
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => window.open(`${apiBase}/reports/risks`, '_blank')}>
                  Export PDF
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          }
        />

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
