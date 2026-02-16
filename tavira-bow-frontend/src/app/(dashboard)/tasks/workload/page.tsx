import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Header } from '@/components/layout/header'
import { Card, CardContent } from '@/components/ui/card'
import { get } from '@/lib/api'
import type { WorkItem, PaginatedResponse } from '@/types'
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts'

interface WorkloadData {
  name: string
  not_started: number
  in_progress: number
  on_hold: number
  completed: number
  total: number
}

export default function WorkloadPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['workitems-workload'],
    queryFn: () => get<PaginatedResponse<WorkItem>>('/workitems?per_page=200'),
  })

  const items = data?.data || []

  const workloadData = useMemo((): WorkloadData[] => {
    const byUser: Record<string, WorkloadData> = {}

    items.forEach((item) => {
      const name = item.responsible_party?.full_name || 'Unassigned'
      if (!byUser[name]) {
        byUser[name] = {
          name,
          not_started: 0,
          in_progress: 0,
          on_hold: 0,
          completed: 0,
          total: 0,
        }
      }
      const status = item.current_status || 'not_started'
      if (status in byUser[name]) {
        byUser[name][status as keyof Omit<WorkloadData, 'name' | 'total'>]++
      }
      byUser[name].total++
    })

    return Object.values(byUser).sort((a, b) => b.total - a.total)
  }, [items])

  const departmentData = useMemo(() => {
    const byDept: Record<string, { name: string; count: number; overdue: number }> = {}

    items.forEach((item) => {
      const dept = item.department || 'Unknown'
      if (!byDept[dept]) {
        byDept[dept] = { name: dept, count: 0, overdue: 0 }
      }
      byDept[dept].count++
      if (item.deadline && new Date(item.deadline) < new Date() && item.current_status !== 'completed') {
        byDept[dept].overdue++
      }
    })

    return Object.values(byDept).sort((a, b) => b.count - a.count)
  }, [items])

  return (
    <div className="flex flex-col">
      <Header title="Workload" description="Team workload distribution" />

      <div className="p-6 space-y-6">
        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
          </div>
        ) : (
          <>
            {/* Summary cards */}
            <div className="grid grid-cols-4 gap-4">
              <Card>
                <CardContent className="p-4">
                  <p className="text-sm text-muted-foreground">Total Tasks</p>
                  <p className="text-2xl font-bold">{items.length}</p>
                </CardContent>
              </Card>
              <Card>
                <CardContent className="p-4">
                  <p className="text-sm text-muted-foreground">In Progress</p>
                  <p className="text-2xl font-bold text-blue-600">
                    {items.filter((i) => i.current_status === 'in_progress').length}
                  </p>
                </CardContent>
              </Card>
              <Card>
                <CardContent className="p-4">
                  <p className="text-sm text-muted-foreground">Completed</p>
                  <p className="text-2xl font-bold text-green-600">
                    {items.filter((i) => i.current_status === 'completed').length}
                  </p>
                </CardContent>
              </Card>
              <Card>
                <CardContent className="p-4">
                  <p className="text-sm text-muted-foreground">Overdue</p>
                  <p className="text-2xl font-bold text-red-600">
                    {items.filter(
                      (i) => i.deadline && new Date(i.deadline) < new Date() && i.current_status !== 'completed'
                    ).length}
                  </p>
                </CardContent>
              </Card>
            </div>

            {/* Workload by User */}
            <Card>
              <CardContent className="p-6">
                <h3 className="mb-4 text-lg font-semibold">Workload by Team Member</h3>
                <ResponsiveContainer width="100%" height={400}>
                  <BarChart data={workloadData} layout="vertical" margin={{ left: 100 }}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis type="number" />
                    <YAxis type="category" dataKey="name" width={100} tick={{ fontSize: 12 }} />
                    <Tooltip />
                    <Legend />
                    <Bar dataKey="not_started" name="Not Started" stackId="a" fill="#9ca3af" />
                    <Bar dataKey="in_progress" name="In Progress" stackId="a" fill="#3b82f6" />
                    <Bar dataKey="on_hold" name="On Hold" stackId="a" fill="#f59e0b" />
                    <Bar dataKey="completed" name="Completed" stackId="a" fill="#22c55e" />
                  </BarChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

            {/* Workload by Department */}
            <Card>
              <CardContent className="p-6">
                <h3 className="mb-4 text-lg font-semibold">Tasks by Department</h3>
                <ResponsiveContainer width="100%" height={300}>
                  <BarChart data={departmentData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                    <YAxis />
                    <Tooltip />
                    <Legend />
                    <Bar dataKey="count" name="Total" fill="#3b82f6" />
                    <Bar dataKey="overdue" name="Overdue" fill="#ef4444" />
                  </BarChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

            {/* Table summary */}
            <Card>
              <CardContent className="p-6">
                <h3 className="mb-4 text-lg font-semibold">Details</h3>
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b">
                        <th className="text-left p-2">Team Member</th>
                        <th className="text-center p-2">Total</th>
                        <th className="text-center p-2">Not Started</th>
                        <th className="text-center p-2">In Progress</th>
                        <th className="text-center p-2">On Hold</th>
                        <th className="text-center p-2">Completed</th>
                        <th className="text-center p-2">Completion %</th>
                      </tr>
                    </thead>
                    <tbody>
                      {workloadData.map((row) => (
                        <tr key={row.name} className="border-b hover:bg-muted/30">
                          <td className="p-2 font-medium">{row.name}</td>
                          <td className="text-center p-2">{row.total}</td>
                          <td className="text-center p-2 text-muted-foreground">{row.not_started}</td>
                          <td className="text-center p-2 text-blue-600">{row.in_progress}</td>
                          <td className="text-center p-2 text-amber-600">{row.on_hold}</td>
                          <td className="text-center p-2 text-green-600">{row.completed}</td>
                          <td className="text-center p-2">
                            {row.total > 0
                              ? `${Math.round((row.completed / row.total) * 100)}%`
                              : '-'}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
          </>
        )}
      </div>
    </div>
  )
}
