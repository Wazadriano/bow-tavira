'use client'

import { useEffect, useState } from 'react'
import { Header } from '@/components/layout/header'
import { BarChart, DoughnutChart, StatsCard, StatsGrid } from '@/components/charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { api } from '@/lib/api'
import {
  ClipboardList,
  CheckCircle,
  Clock,
  AlertTriangle,
  Flag,
  TrendingUp,
} from 'lucide-react'

interface DashboardStats {
  total_tasks: number
  completed: number
  in_progress: number
  not_started: number
  overdue: number
  priority_count: number
  by_department: Array<{ name: string; total: number; priority: number }>
  by_activity: Array<{ name: string; count: number }>
  by_rag: { blue: number; green: number; amber: number; red: number }
  priority_by_dept: Array<{ department: string; total: number; priority: number }>
}

export default function TasksDashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await api.get<{ data: DashboardStats }>('/tasks/dashboard/stats')
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
        <Header title="Dashboard" description="Book of Work Statistics" />
        <div className="p-6">
          <div className="animate-pulse space-y-6">
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
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

  const deptData = stats.by_department.map((d) => ({
    name: d.name,
    Total: d.total,
    Priority: d.priority,
  }))

  const activityData = stats.by_activity.map((a) => ({
    name: a.name,
    count: a.count,
  }))

  return (
    <>
      <Header title="Dashboard" description="Book of Work Statistics" />

      <div className="p-6 space-y-6">
        {/* KPIs */}
        <StatsGrid columns={5}>
          <StatsCard
            title="Total Work Items"
            value={stats.total_tasks}
            icon={ClipboardList}
            variant="info"
          />
          <StatsCard
            title="Completed"
            value={stats.completed}
            icon={CheckCircle}
            variant="success"
            description={`${Math.round((stats.completed / stats.total_tasks) * 100)}%`}
          />
          <StatsCard
            title="In Progress"
            value={stats.in_progress}
            icon={Clock}
            variant="info"
          />
          <StatsCard
            title="Overdue"
            value={stats.overdue}
            icon={AlertTriangle}
            variant="danger"
          />
          <StatsCard
            title="Priority"
            value={stats.priority_count}
            icon={Flag}
            variant="warning"
          />
        </StatsGrid>

        {/* Charts row */}
        <div className="grid gap-6 lg:grid-cols-2">
          <BarChart
            title="Work Items by Department"
            description="Distribution with priority items"
            data={deptData}
            bars={[
              { dataKey: 'Total', name: 'Total', color: '#3b82f6' },
              { dataKey: 'Priority', name: 'Priority', color: '#ef4444' },
            ]}
          />

          <DoughnutChart
            title="RAG Distribution"
            description="RAG status of work items"
            data={ragData}
          />
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <BarChart
            title="Work Items by Activity"
            description="Distribution by activity type"
            data={activityData}
            bars={[{ dataKey: 'count', name: 'Work Items', color: '#8b5cf6' }]}
          />

          {/* Priority Items Table */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Flag className="h-5 w-5 text-red-500" />
                Priority Items by Department
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {stats.priority_by_dept.map((item) => (
                  <div
                    key={item.department}
                    className="flex items-center justify-between p-3 rounded-lg border"
                  >
                    <div>
                      <p className="font-medium">{item.department}</p>
                      <p className="text-sm text-muted-foreground">
                        {item.total} work items total
                      </p>
                    </div>
                    <Badge variant="destructive" className="text-lg px-3 py-1">
                      {item.priority}
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
