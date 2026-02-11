'use client'

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
import { safeDateString } from '@/lib/utils'
import {
  Shield,
  CheckCircle,
  Clock,
  AlertTriangle,
  Calendar,
  Download,
} from 'lucide-react'

interface GovernanceStats {
  total_items: number
  completed: number
  pending: number
  overdue: number
  by_department: Array<{ name: string; count: number }>
  by_frequency: Array<{ name: string; count: number }>
  by_status: { completed: number; in_progress: number; pending: number; overdue: number }
  upcoming: Array<{ id: number; title: string; next_due: string; department: string }>
}

const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'

export default function GovernanceDashboardPage() {
  const [stats, setStats] = useState<GovernanceStats | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await api.get<{ data: GovernanceStats }>('/governance/dashboard/stats')
        setStats(response.data.data)
      } catch {
        setStats(null)
      } finally {
        setIsLoading(false)
      }
    }
    fetchStats()
  }, [])

  if (isLoading || !stats) {
    return (
      <>
        <Header
          title="Dashboard"
          description="Governance Statistics"
          actions={
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                  <Download className="mr-2 h-4 w-4" />
                  Export
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => window.open(`${apiBase}/export/governance`, '_blank')}>
                  Export Excel
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => window.open(`${apiBase}/reports/governance`, '_blank')}>
                  Export PDF
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          }
        />
        <div className="p-6">
          <div className="animate-pulse space-y-6">
            <div className="grid gap-4 md:grid-cols-4">
              {[...Array(4)].map((_, i) => (
                <div key={i} className="h-24 bg-muted rounded-lg" />
              ))}
            </div>
          </div>
        </div>
      </>
    )
  }

  const statusData = [
    { name: 'Completed', value: stats.by_status.completed, color: '#22c55e' },
    { name: 'In Progress', value: stats.by_status.in_progress, color: '#3b82f6' },
    { name: 'Pending', value: stats.by_status.pending, color: '#f59e0b' },
    { name: 'Overdue', value: stats.by_status.overdue, color: '#ef4444' },
  ]

  const deptData = stats.by_department.map((d) => ({
    name: d.name,
    count: d.count,
  }))

  const freqData = stats.by_frequency.map((f) => ({
    name: f.name,
    count: f.count,
  }))

  return (
    <>
      <Header
        title="Dashboard"
        description="Statistiques Governance"
        actions={
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" size="sm">
                <Download className="mr-2 h-4 w-4" />
                Export
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => window.open(`${apiBase}/export/governance`, '_blank')}>
                Export Excel
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => window.open(`${apiBase}/reports/governance`, '_blank')}>
                Export PDF
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        }
      />

      <div className="p-6 space-y-6">
        {/* KPIs */}
        <StatsGrid columns={4}>
          <StatsCard
            title="Total Items"
            value={stats.total_items}
            icon={Shield}
            variant="info"
          />
          <StatsCard
            title="Completed"
            value={stats.completed}
            icon={CheckCircle}
            variant="success"
          />
          <StatsCard
            title="Pending"
            value={stats.pending}
            icon={Clock}
            variant="warning"
          />
          <StatsCard
            title="Overdue"
            value={stats.overdue}
            icon={AlertTriangle}
            variant="danger"
          />
        </StatsGrid>

        {/* Charts row */}
        <div className="grid gap-6 lg:grid-cols-2">
          <BarChart
            title="Items by Department"
            data={deptData}
            bars={[{ dataKey: 'count', name: 'Items', color: '#8b5cf6' }]}
          />

          <DoughnutChart
            title="Distribution by Status"
            data={statusData}
          />
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <BarChart
            title="Items by Frequency"
            data={freqData}
            bars={[{ dataKey: 'count', name: 'Items', color: '#06b6d4' }]}
          />

          {/* Upcoming Reviews */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Calendar className="h-5 w-5" />
                Upcoming Due Dates
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {stats.upcoming.map((item) => (
                  <div
                    key={item.id}
                    className="flex items-center justify-between p-3 rounded-lg border"
                  >
                    <div>
                      <p className="font-medium">{item.title}</p>
                      <p className="text-sm text-muted-foreground">
                        {item.department}
                      </p>
                    </div>
                    <Badge variant="outline">
                      {safeDateString(item.next_due)}
                    </Badge>
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
