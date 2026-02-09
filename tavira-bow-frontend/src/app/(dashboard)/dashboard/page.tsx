'use client'

import { useQuery } from '@tanstack/react-query'
import { Header } from '@/components/layout/header'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { get } from '@/lib/api'
import type { DashboardStats } from '@/types'
import {
  ClipboardList,
  CheckCircle,
  AlertTriangle,
  Truck,
  AlertCircle,
  TrendingUp,
  Shield,
} from 'lucide-react'
import { AlertsPanel, AreaStats } from '@/components/dashboard'

export default function DashboardPage() {
  const { data: stats, isLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => get<DashboardStats>('/dashboard/stats'),
  })

  return (
    <>
      <Header
        title="Dashboard"
        description="Vue d'ensemble du Book of Work"
      />

      <div className="p-6 space-y-6">
        {/* Stats Cards */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Taches</CardTitle>
              <ClipboardList className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {isLoading ? '-' : stats?.total_tasks || 0}
              </div>
              <p className="text-xs text-muted-foreground">
                Work items actifs
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Terminees</CardTitle>
              <CheckCircle className="h-4 w-4 text-green-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-600">
                {isLoading ? '-' : stats?.completed_tasks || 0}
              </div>
              <p className="text-xs text-muted-foreground">
                Taches completees
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">En retard</CardTitle>
              <AlertTriangle className="h-4 w-4 text-destructive" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-destructive">
                {isLoading ? '-' : stats?.overdue_tasks || 0}
              </div>
              <p className="text-xs text-muted-foreground">
                Attention requise
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Fournisseurs</CardTitle>
              <Truck className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {isLoading ? '-' : stats?.total_suppliers || 0}
              </div>
              <p className="text-xs text-muted-foreground">
                Fournisseurs actifs
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Risques</CardTitle>
              <Shield className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {isLoading ? '-' : stats?.total_risks || 0}
              </div>
              <p className="text-xs text-muted-foreground">
                Risques identifies
              </p>
            </CardContent>
          </Card>
        </div>

        {/* RAG Distribution & Risks */}
        <div className="grid gap-4 md:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Distribution RAG</CardTitle>
              <CardDescription>Statut actuel des taches</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex gap-4 flex-wrap">
                  <div className="flex items-center gap-2">
                    <Badge variant="blue">Blue</Badge>
                    <span className="text-2xl font-bold">
                      {stats?.tasks_by_rag?.blue || 0}
                    </span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant="green">Green</Badge>
                    <span className="text-2xl font-bold">
                      {stats?.tasks_by_rag?.green || 0}
                    </span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant="amber">Amber</Badge>
                    <span className="text-2xl font-bold">
                      {stats?.tasks_by_rag?.amber || 0}
                    </span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant="red">Red</Badge>
                    <span className="text-2xl font-bold">
                      {stats?.tasks_by_rag?.red || 0}
                    </span>
                  </div>
                </div>

                {/* RAG Progress Bar */}
                {stats?.tasks_by_rag && (
                  <div className="flex h-3 overflow-hidden rounded-full bg-muted">
                    {(() => {
                      const total =
                        (stats.tasks_by_rag.blue || 0) +
                        (stats.tasks_by_rag.green || 0) +
                        (stats.tasks_by_rag.amber || 0) +
                        (stats.tasks_by_rag.red || 0)
                      if (total === 0) return null
                      return (
                        <>
                          <div
                            className="bg-sky-500 transition-all"
                            style={{ width: `${((stats.tasks_by_rag.blue || 0) / total) * 100}%` }}
                          />
                          <div
                            className="bg-green-500 transition-all"
                            style={{ width: `${((stats.tasks_by_rag.green || 0) / total) * 100}%` }}
                          />
                          <div
                            className="bg-amber-500 transition-all"
                            style={{ width: `${((stats.tasks_by_rag.amber || 0) / total) * 100}%` }}
                          />
                          <div
                            className="bg-red-500 transition-all"
                            style={{ width: `${((stats.tasks_by_rag.red || 0) / total) * 100}%` }}
                          />
                        </>
                      )
                    })()}
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Apercu Risques</CardTitle>
              <CardDescription>Resume de la gestion des risques</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-4">
                <div className="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                  <AlertCircle className="h-8 w-8 text-muted-foreground" />
                  <div>
                    <p className="text-sm text-muted-foreground">Total</p>
                    <p className="text-2xl font-bold">
                      {stats?.total_risks || 0}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-3 p-3 rounded-lg bg-red-50">
                  <TrendingUp className="h-8 w-8 text-destructive" />
                  <div>
                    <p className="text-sm text-muted-foreground">Eleves</p>
                    <p className="text-2xl font-bold text-destructive">
                      {stats?.high_risks || 0}
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Alerts & Area Stats */}
        <div className="grid gap-4 md:grid-cols-2">
          <AlertsPanel />
          <AreaStats />
        </div>
      </div>
    </>
  )
}
