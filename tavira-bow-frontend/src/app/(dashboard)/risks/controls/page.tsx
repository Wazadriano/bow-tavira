'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { api } from '@/lib/api'
import { Plus, Search, ShieldCheck, Link } from 'lucide-react'

interface ControlLibraryItem {
  id: number
  code: string
  name: string
  description: string
  type: 'preventive' | 'detective' | 'corrective'
  frequency: 'continuous' | 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'annually'
  owner: string
  linked_risks_count: number
  effectiveness: 'low' | 'medium' | 'high'
}

export default function ControlLibraryPage() {
  const router = useRouter()
  const [controls, setControls] = useState<ControlLibraryItem[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [typeFilter, setTypeFilter] = useState<string>('all')
  const [effectivenessFilter, setEffectivenessFilter] = useState<string>('all')

  useEffect(() => {
    const fetchControls = async () => {
      try {
        const response = await api.get<{ data: ControlLibraryItem[] }>('/risks/controls/library')
        setControls(response.data.data)
      } catch {
        // Fallback mock data
        setControls([
          {
            id: 1,
            code: 'CTRL-001',
            name: 'Data Encryption Standard',
            description: 'Encrypt all sensitive data using AES-256',
            type: 'preventive',
            frequency: 'continuous',
            owner: 'IT Security',
            linked_risks_count: 5,
            effectiveness: 'high',
          },
          {
            id: 2,
            code: 'CTRL-002',
            name: 'Access Review',
            description: 'Quarterly review of user access rights',
            type: 'detective',
            frequency: 'quarterly',
            owner: 'Compliance',
            linked_risks_count: 8,
            effectiveness: 'medium',
          },
          {
            id: 3,
            code: 'CTRL-003',
            name: 'Incident Response Plan',
            description: 'Documented procedures for security incidents',
            type: 'corrective',
            frequency: 'annually',
            owner: 'Risk Management',
            linked_risks_count: 12,
            effectiveness: 'high',
          },
          {
            id: 4,
            code: 'CTRL-004',
            name: 'Firewall Rules',
            description: 'Network firewall configuration and monitoring',
            type: 'preventive',
            frequency: 'continuous',
            owner: 'IT Infrastructure',
            linked_risks_count: 3,
            effectiveness: 'high',
          },
          {
            id: 5,
            code: 'CTRL-005',
            name: 'Log Monitoring',
            description: 'Real-time monitoring of system logs for anomalies',
            type: 'detective',
            frequency: 'continuous',
            owner: 'SOC Team',
            linked_risks_count: 7,
            effectiveness: 'medium',
          },
          {
            id: 6,
            code: 'CTRL-006',
            name: 'Backup Procedures',
            description: 'Daily backup of critical systems and data',
            type: 'corrective',
            frequency: 'daily',
            owner: 'IT Operations',
            linked_risks_count: 4,
            effectiveness: 'high',
          },
        ])
      } finally {
        setIsLoading(false)
      }
    }
    fetchControls()
  }, [])

  const filteredControls = controls.filter((control) => {
    const matchesSearch =
      control.name.toLowerCase().includes(search.toLowerCase()) ||
      control.code.toLowerCase().includes(search.toLowerCase()) ||
      control.description.toLowerCase().includes(search.toLowerCase())
    const matchesType =
      typeFilter === 'all' || control.type === typeFilter
    const matchesEffectiveness =
      effectivenessFilter === 'all' || control.effectiveness === effectivenessFilter
    return matchesSearch && matchesType && matchesEffectiveness
  })

  const getTypeBadge = (type: string) => {
    const colors: Record<string, string> = {
      preventive: 'bg-blue-100 text-blue-800',
      detective: 'bg-purple-100 text-purple-800',
      corrective: 'bg-orange-100 text-orange-800',
    }
    const labels: Record<string, string> = {
      preventive: 'Preventive',
      detective: 'Detective',
      corrective: 'Corrective',
    }
    return <Badge className={colors[type]}>{labels[type]}</Badge>
  }

  const getEffectivenessBadge = (effectiveness: string) => {
    const colors: Record<string, string> = {
      high: 'bg-green-100 text-green-800',
      medium: 'bg-yellow-100 text-yellow-800',
      low: 'bg-red-100 text-red-800',
    }
    const labels: Record<string, string> = {
      high: 'High',
      medium: 'Medium',
      low: 'Low',
    }
    return <Badge className={colors[effectiveness]}>{labels[effectiveness]}</Badge>
  }

  const getFrequencyLabel = (frequency: string) => {
    const labels: Record<string, string> = {
      continuous: 'Continuous',
      daily: 'Daily',
      weekly: 'Weekly',
      monthly: 'Monthly',
      quarterly: 'Quarterly',
      annually: 'Annually',
    }
    return labels[frequency] || frequency
  }

  const summaryStats = {
    total: controls.length,
    preventive: controls.filter((c) => c.type === 'preventive').length,
    detective: controls.filter((c) => c.type === 'detective').length,
    corrective: controls.filter((c) => c.type === 'corrective').length,
  }

  return (
    <>
      <Header
        title="Control Library"
        description="Risk controls management"
        actions={
          <Button onClick={() => router.push('/risks/controls/new')}>
            <Plus className="h-4 w-4 mr-2" />
            New Control
          </Button>
        }
      />

      <div className="p-6 space-y-6">
        {/* Summary Cards */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold">{summaryStats.total}</div>
              <p className="text-sm text-muted-foreground">Total Controls</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold text-blue-600">{summaryStats.preventive}</div>
              <p className="text-sm text-muted-foreground">Preventive</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold text-purple-600">{summaryStats.detective}</div>
              <p className="text-sm text-muted-foreground">Detective</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold text-orange-600">{summaryStats.corrective}</div>
              <p className="text-sm text-muted-foreground">Corrective</p>
            </CardContent>
          </Card>
        </div>

        {/* Filters */}
        <Card>
          <CardContent className="pt-6">
            <div className="flex gap-4">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search by code, name or description..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="pl-10"
                />
              </div>
              <Select value={typeFilter} onValueChange={setTypeFilter}>
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All types</SelectItem>
                  <SelectItem value="preventive">Preventive</SelectItem>
                  <SelectItem value="detective">Detective</SelectItem>
                  <SelectItem value="corrective">Corrective</SelectItem>
                </SelectContent>
              </Select>
              <Select value={effectivenessFilter} onValueChange={setEffectivenessFilter}>
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Effectiveness" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All effectiveness</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="low">Low</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* Controls Table */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <ShieldCheck className="h-5 w-5" />
              Control Library ({filteredControls.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="animate-pulse space-y-4">
                {[...Array(5)].map((_, i) => (
                  <div key={i} className="h-12 bg-muted rounded" />
                ))}
              </div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Code</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Frequency</TableHead>
                    <TableHead>Owner</TableHead>
                    <TableHead>Effectiveness</TableHead>
                    <TableHead>
                      <div className="flex items-center gap-1">
                        <Link className="h-4 w-4" />
                        Risks
                      </div>
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredControls.map((control) => (
                    <TableRow
                      key={control.id}
                      className="cursor-pointer hover:bg-muted/50"
                    >
                      <TableCell className="font-mono font-medium">
                        {control.code}
                      </TableCell>
                      <TableCell>
                        <div>
                          <p className="font-medium">{control.name}</p>
                          <p className="text-sm text-muted-foreground truncate max-w-xs">
                            {control.description}
                          </p>
                        </div>
                      </TableCell>
                      <TableCell>{getTypeBadge(control.type)}</TableCell>
                      <TableCell>{getFrequencyLabel(control.frequency)}</TableCell>
                      <TableCell>{control.owner}</TableCell>
                      <TableCell>{getEffectivenessBadge(control.effectiveness)}</TableCell>
                      <TableCell>
                        <Badge variant="outline">{control.linked_risks_count}</Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  )
}
