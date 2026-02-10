'use client'

import { useEffect, useState } from 'react'
import { Header } from '@/components/layout/header'
import { BarChart, DoughnutChart, StatsCard, StatsGrid } from '@/components/charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { api } from '@/lib/api'
import { safeDateString } from '@/lib/utils'
import {
  Truck,
  FileText,
  Receipt,
  AlertTriangle,
  TrendingUp,
  Calendar,
} from 'lucide-react'

interface SupplierStats {
  total_suppliers: number
  active_suppliers: number
  total_contracts: number
  expiring_soon: number
  total_invoices: number
  pending_invoices: number
  by_location: Array<{ name: string; count: number }>
  by_category: Array<{ name: string; count: number }>
  by_status: { active: number; inactive: number; pending: number }
  expiring_contracts: Array<{
    id: number
    name: string
    supplier: string
    end_date: string
  }>
}

export default function SuppliersDashboardPage() {
  const [stats, setStats] = useState<SupplierStats | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await api.get<{ data: SupplierStats }>('/suppliers/dashboard/stats')
        setStats(response.data.data)
      } catch {
        // Fallback mock data
        setStats({
          total_suppliers: 67,
          active_suppliers: 52,
          total_contracts: 89,
          expiring_soon: 8,
          total_invoices: 234,
          pending_invoices: 15,
          by_location: [
            { name: 'France', count: 28 },
            { name: 'Belgium', count: 15 },
            { name: 'Luxembourg', count: 12 },
            { name: 'Germany', count: 8 },
            { name: 'UK', count: 4 },
          ],
          by_category: [
            { name: 'IT Services', count: 18 },
            { name: 'Consulting', count: 14 },
            { name: 'Software', count: 12 },
            { name: 'Hardware', count: 10 },
            { name: 'Telecom', count: 8 },
            { name: 'Other', count: 5 },
          ],
          by_status: { active: 52, inactive: 10, pending: 5 },
          expiring_contracts: [
            { id: 1, name: 'IT Support Contract', supplier: 'TechCorp', end_date: '2026-02-15' },
            { id: 2, name: 'Cloud Services', supplier: 'CloudPro', end_date: '2026-02-28' },
            { id: 3, name: 'Security Audit', supplier: 'SecurIT', end_date: '2026-03-10' },
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
        <Header title="Dashboard" description="Suppliers Statistics" />
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

  const statusData = [
    { name: 'Active', value: stats.by_status.active, color: '#22c55e' },
    { name: 'Inactive', value: stats.by_status.inactive, color: '#6b7280' },
    { name: 'Pending', value: stats.by_status.pending, color: '#f59e0b' },
  ]

  const locationData = stats.by_location.map((l) => ({
    name: l.name,
    count: l.count,
  }))

  const categoryData = stats.by_category.map((c) => ({
    name: c.name,
    value: c.count,
    color: [
      '#3b82f6',
      '#8b5cf6',
      '#06b6d4',
      '#22c55e',
      '#f59e0b',
      '#6b7280',
    ][stats.by_category.indexOf(c) % 6],
  }))

  return (
    <>
      <Header title="Dashboard" description="Supplier Statistics" />

      <div className="p-6 space-y-6">
        {/* KPIs */}
        <StatsGrid columns={6}>
          <StatsCard
            title="Total Suppliers"
            value={stats.total_suppliers}
            icon={Truck}
            variant="info"
          />
          <StatsCard
            title="Active"
            value={stats.active_suppliers}
            icon={TrendingUp}
            variant="success"
          />
          <StatsCard
            title="Contracts"
            value={stats.total_contracts}
            icon={FileText}
            variant="info"
          />
          <StatsCard
            title="Expiring Soon"
            value={stats.expiring_soon}
            icon={AlertTriangle}
            variant="warning"
          />
          <StatsCard
            title="Invoices"
            value={stats.total_invoices}
            icon={Receipt}
            variant="info"
          />
          <StatsCard
            title="Pending"
            value={stats.pending_invoices}
            icon={Calendar}
            variant="warning"
          />
        </StatsGrid>

        {/* Charts row */}
        <div className="grid gap-6 lg:grid-cols-2">
          <BarChart
            title="Suppliers by Location"
            data={locationData}
            bars={[{ dataKey: 'count', name: 'Suppliers', color: '#8b5cf6' }]}
          />

          <DoughnutChart
            title="Distribution by Status"
            data={statusData}
          />
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <DoughnutChart
            title="Top Categories"
            description="Distribution by service type"
            data={categoryData}
          />

          {/* Expiring Contracts */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <AlertTriangle className="h-5 w-5 text-amber-500" />
                Expiring Soon Contracts
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {stats.expiring_contracts.map((contract) => (
                  <div
                    key={contract.id}
                    className="flex items-center justify-between p-3 rounded-lg border"
                  >
                    <div>
                      <p className="font-medium">{contract.name}</p>
                      <p className="text-sm text-muted-foreground">
                        {contract.supplier}
                      </p>
                    </div>
                    <Badge variant="outline" className="text-amber-600">
                      {safeDateString(contract.end_date)}
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
