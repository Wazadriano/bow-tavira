import { useEffect, useRef, useState, useCallback } from 'react'
import { useSearchParams } from 'react-router-dom'
import { Card, CardContent } from '@/components/ui/card'
import { StatsCard, StatsGrid } from '@/components/charts/stats-card'
import { BarChart } from '@/components/charts/bar-chart'
import { DoughnutChart } from '@/components/charts/doughnut-chart'
import {
  Loader2,
  BarChart3,
  Shield,
  Truck,
  FileText,
  CheckCircle,
  Clock,
  AlertTriangle,
  TrendingUp,
  Activity,
  Package,
  Download,
} from 'lucide-react'
import { toPng } from 'html-to-image'
import { Button } from '@/components/ui/button'

interface DashboardData {
  work_items: {
    total: number
    completed: number
    in_progress: number
    overdue: number
    completion_rate: number
    by_rag: { blue: number; green: number; amber: number; red: number }
    by_department: { name: string; total: number; completed: number }[]
  }
  governance: {
    total: number
    completed: number
    overdue: number
    completion_rate: number
  }
  risks: {
    total: number
    high: number
    by_theme: { name: string; count: number }[]
  }
  suppliers: {
    total: number
    active: number
    contracts_expiring_90d: number
  }
  generated_at: string
}

const RAG_COLORS: Record<string, string> = {
  Blue: '#3b82f6',
  Green: '#22c55e',
  Amber: '#f59e0b',
  Red: '#ef4444',
}

export default function PublicDashboardPage() {
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token')
  const [data, setData] = useState<DashboardData | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  const ragChartRef = useRef<HTMLDivElement>(null)
  const deptChartRef = useRef<HTMLDivElement>(null)
  const riskChartRef = useRef<HTMLDivElement>(null)

  const exportPng = useCallback(async (ref: React.RefObject<HTMLDivElement | null>, filename: string) => {
    if (!ref.current) return
    try {
      const dataUrl = await toPng(ref.current, { backgroundColor: '#ffffff' })
      const link = document.createElement('a')
      link.download = `${filename}.png`
      link.href = dataUrl
      link.click()
    } catch {
      // silently fail
    }
  }, [])

  useEffect(() => {
    if (!token) {
      setError('No access token provided')
      setIsLoading(false)
      return
    }

    const baseUrl = import.meta.env.VITE_API_URL || '/api'
    fetch(`${baseUrl}/public/dashboard`, {
      headers: { Authorization: `Bearer ${token}` },
    })
      .then((res) => {
        if (!res.ok) throw new Error(res.status === 401 ? 'Invalid or expired token' : 'Failed to load dashboard')
        return res.json()
      })
      .then((json) => setData(json))
      .catch((err) => setError(err.message))
      .finally(() => setIsLoading(false))
  }, [token])

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <div className="text-center space-y-4">
          <Loader2 className="h-8 w-8 animate-spin mx-auto text-primary" />
          <p className="text-muted-foreground">Loading dashboard...</p>
        </div>
      </div>
    )
  }

  if (error || !data) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <Card className="max-w-md w-full mx-4">
          <CardContent className="pt-6 text-center space-y-4">
            <Shield className="h-12 w-12 mx-auto text-muted-foreground" />
            <h2 className="text-xl font-semibold">Access Denied</h2>
            <p className="text-muted-foreground">{error || 'Unable to load dashboard data.'}</p>
          </CardContent>
        </Card>
      </div>
    )
  }

  const ragData = [
    { name: 'Blue', value: data.work_items.by_rag.blue, color: RAG_COLORS.Blue },
    { name: 'Green', value: data.work_items.by_rag.green, color: RAG_COLORS.Green },
    { name: 'Amber', value: data.work_items.by_rag.amber, color: RAG_COLORS.Amber },
    { name: 'Red', value: data.work_items.by_rag.red, color: RAG_COLORS.Red },
  ].filter((d) => d.value > 0)

  const deptBarData = data.work_items.by_department.map((d) => ({
    name: d.name,
    value: d.total,
  }))

  const riskThemeData = data.risks.by_theme.map((t) => ({
    name: t.name,
    value: t.count,
  }))

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="border-b bg-card px-6 py-4">
        <div className="flex items-center justify-between max-w-7xl mx-auto">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-12 items-center justify-center rounded bg-primary text-sm font-bold text-primary-foreground">
              BOW
            </div>
            <div>
              <h1 className="text-lg font-semibold">Book of Work - Dashboard</h1>
              <p className="text-xs text-muted-foreground">Tavira Financial Solutions</p>
            </div>
          </div>
          <p className="text-xs text-muted-foreground">
            Generated: {new Date(data.generated_at).toLocaleString()}
          </p>
        </div>
      </header>

      <main className="max-w-7xl mx-auto p-6 space-y-8">
        {/* Work Items Section */}
        <section>
          <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
            <BarChart3 className="h-5 w-5" />
            Work Items
          </h2>
          <StatsGrid>
            <StatsCard title="Total" value={data.work_items.total} icon={BarChart3} />
            <StatsCard title="Completed" value={data.work_items.completed} icon={CheckCircle} variant="success" />
            <StatsCard title="In Progress" value={data.work_items.in_progress} icon={Clock} variant="info" />
            <StatsCard title="Overdue" value={data.work_items.overdue} icon={AlertTriangle} variant={data.work_items.overdue > 0 ? 'danger' : 'default'} />
          </StatsGrid>
          <div className="grid gap-6 mt-6 md:grid-cols-2">
            <div ref={ragChartRef} className="relative">
              <Button variant="ghost" size="icon" className="absolute top-3 right-3 z-10" onClick={() => exportPng(ragChartRef, 'rag-status')} title="Export PNG">
                <Download className="h-4 w-4" />
              </Button>
              <DoughnutChart title="RAG Status Distribution" data={ragData} />
            </div>
            <div ref={deptChartRef} className="relative">
              <Button variant="ghost" size="icon" className="absolute top-3 right-3 z-10" onClick={() => exportPng(deptChartRef, 'by-department')} title="Export PNG">
                <Download className="h-4 w-4" />
              </Button>
              <BarChart title="By Department (Top 10)" data={deptBarData} bars={[{ dataKey: 'value', name: 'Count', color: '#3b82f6' }]} />
            </div>
          </div>
        </section>

        {/* Governance Section */}
        <section>
          <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
            <FileText className="h-5 w-5" />
            Governance
          </h2>
          <StatsGrid>
            <StatsCard title="Total Items" value={data.governance.total} icon={FileText} />
            <StatsCard title="Completed" value={data.governance.completed} icon={CheckCircle} variant="success" />
            <StatsCard title="Overdue" value={data.governance.overdue} icon={AlertTriangle} variant={data.governance.overdue > 0 ? 'danger' : 'default'} />
            <StatsCard title="Completion Rate" value={`${data.governance.completion_rate}%`} icon={TrendingUp} />
          </StatsGrid>
        </section>

        {/* Risk Section */}
        <section>
          <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
            <Shield className="h-5 w-5" />
            Risk Management
          </h2>
          <StatsGrid columns={2}>
            <StatsCard title="Active Risks" value={data.risks.total} icon={Activity} />
            <StatsCard title="High Risks" value={data.risks.high} icon={AlertTriangle} variant={data.risks.high > 0 ? 'danger' : 'default'} />
          </StatsGrid>
          {riskThemeData.length > 0 && (
            <div className="mt-6 relative" ref={riskChartRef}>
              <Button variant="ghost" size="icon" className="absolute top-3 right-3 z-10" onClick={() => exportPng(riskChartRef, 'risks-by-theme')} title="Export PNG">
                <Download className="h-4 w-4" />
              </Button>
              <BarChart title="Risks by Theme" data={riskThemeData} bars={[{ dataKey: 'value', name: 'Count', color: '#ef4444' }]} />
            </div>
          )}
        </section>

        {/* Suppliers Section */}
        <section>
          <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
            <Truck className="h-5 w-5" />
            Suppliers
          </h2>
          <StatsGrid columns={3}>
            <StatsCard title="Total Suppliers" value={data.suppliers.total} icon={Package} />
            <StatsCard title="Active" value={data.suppliers.active} icon={Truck} variant="success" />
            <StatsCard title="Contracts Expiring (90d)" value={data.suppliers.contracts_expiring_90d} icon={AlertTriangle} variant={data.suppliers.contracts_expiring_90d > 0 ? 'warning' : 'default'} />
          </StatsGrid>
        </section>
      </main>
    </div>
  )
}
