import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
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
import { safeDateString, safeParseDate } from '@/lib/utils'
import { Plus, Search, FileText, AlertTriangle } from 'lucide-react'

interface Contract {
  id: number
  name: string
  supplier_id: number
  supplier_name: string
  start_date: string
  end_date: string
  value: number
  currency: string
  status: 'active' | 'expired' | 'pending'
  rag_status?: string
}

export default function ContractsPage() {
  const navigate = useNavigate()
  const [contracts, setContracts] = useState<Contract[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')

  useEffect(() => {
    const fetchContracts = async () => {
      try {
        const response = await api.get<{ data: Contract[] }>('/contracts')
        const list = response.data.data ?? response.data
        setContracts(Array.isArray(list) ? list : [])
      } catch {
        setContracts([])
      } finally {
        setIsLoading(false)
      }
    }
    fetchContracts()
  }, [])

  const filteredContracts = contracts.filter((contract) => {
    const matchesSearch =
      (contract.name ?? '').toLowerCase().includes(search.toLowerCase()) ||
      (contract.supplier_name ?? '').toLowerCase().includes(search.toLowerCase())
    const matchesStatus =
      statusFilter === 'all' || contract.status === statusFilter
    return matchesSearch && matchesStatus
  })

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return <Badge className="bg-green-100 text-green-800">Active</Badge>
      case 'expired':
        return <Badge className="bg-red-100 text-red-800">Expired</Badge>
      case 'pending':
        return <Badge className="bg-blue-100 text-blue-800">Pending</Badge>
      default:
        return <Badge variant="outline">{status}</Badge>
    }
  }

  const getRagBadge = (rag: string | undefined) => {
    const colors: Record<string, string> = {
      blue: 'bg-sky-100 text-sky-800',
      green: 'bg-green-100 text-green-800',
      amber: 'bg-amber-100 text-amber-800',
      red: 'bg-red-100 text-red-800',
    }
    if (!rag) return null
    return (
      <span
        className={`inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold ${colors[rag] || ''}`}
      >
        {rag.charAt(0).toUpperCase()}
      </span>
    )
  }

  const formatCurrency = (value: number, currency: string) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency,
    }).format(value)
  }

  const getDaysUntilEnd = (endDate: string) => {
    const end = safeParseDate(endDate)
    if (!end) return null
    const now = new Date()
    const days = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
    return days
  }

  return (
    <>
      <Header
        title="Contracts"
        description="Supplier contracts management"
        actions={
          <Button onClick={() => navigate('/suppliers/contracts/new')}>
            <Plus className="h-4 w-4 mr-2" />
            New Contract
          </Button>
        }
      />

      <div className="p-6 space-y-6">
        {/* Filters */}
        <Card>
          <CardContent className="pt-6">
            <div className="flex gap-4">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search by name or supplier..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="pl-10"
                />
              </div>
              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All statuses</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="expired">Expired</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* Contracts Table */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="h-5 w-5" />
              Contracts List ({filteredContracts.length})
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
                    <TableHead>RAG</TableHead>
                    <TableHead>Contract Name</TableHead>
                    <TableHead>Supplier</TableHead>
                    <TableHead>Start</TableHead>
                    <TableHead>End</TableHead>
                    <TableHead>Days Remaining</TableHead>
                    <TableHead>Amount</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredContracts.map((contract) => {
                    const daysLeft = getDaysUntilEnd(contract.end_date)
                    return (
                      <TableRow
                        key={contract.id}
                        className="cursor-pointer hover:bg-muted/50"
                        onClick={() =>
                          navigate(`/suppliers/${contract.supplier_id}`)
                        }
                      >
                        <TableCell>{getRagBadge(contract.rag_status)}</TableCell>
                        <TableCell className="font-medium">
                          {contract.name}
                        </TableCell>
                        <TableCell>{contract.supplier_name}</TableCell>
                        <TableCell>
                          {safeDateString(contract.start_date)}
                        </TableCell>
                        <TableCell>
                          {safeDateString(contract.end_date)}
                        </TableCell>
                        <TableCell>
                          {daysLeft === null ? (
                            <span className="text-muted-foreground">-</span>
                          ) : daysLeft > 0 ? (
                            <span
                              className={
                                daysLeft <= 30
                                  ? 'text-amber-600 font-medium'
                                  : ''
                              }
                            >
                              {daysLeft <= 30 && (
                                <AlertTriangle className="inline h-4 w-4 mr-1" />
                              )}
                              {daysLeft} days
                            </span>
                          ) : (
                            <span className="text-red-600 font-medium">
                              Expired
                            </span>
                          )}
                        </TableCell>
                        <TableCell>
                          {formatCurrency(contract.value, contract.currency)}
                        </TableCell>
                        <TableCell>{getStatusBadge(contract.status)}</TableCell>
                      </TableRow>
                    )
                  })}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  )
}
