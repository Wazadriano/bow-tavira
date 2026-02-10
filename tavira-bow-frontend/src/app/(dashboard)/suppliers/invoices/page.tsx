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
import { safeDateString } from '@/lib/utils'
import { Plus, Search, Receipt, Upload, Download } from 'lucide-react'

interface Invoice {
  id: number
  invoice_number: string
  supplier_id: number
  supplier_name: string
  invoice_date: string
  due_date: string
  amount: number
  currency: string
  status: 'pending' | 'paid' | 'overdue' | 'cancelled'
  sage_category?: string
}

export default function InvoicesPage() {
  const router = useRouter()
  const [invoices, setInvoices] = useState<Invoice[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')

  useEffect(() => {
    const fetchInvoices = async () => {
      try {
        const response = await api.get<{ data: Invoice[] }>('/invoices')
        setInvoices(response.data.data)
      } catch {
        // Fallback mock data
        setInvoices([
          {
            id: 1,
            invoice_number: 'INV-2026-001',
            supplier_id: 1,
            supplier_name: 'TechCorp Solutions',
            invoice_date: '2026-01-15',
            due_date: '2026-02-15',
            amount: 12500,
            currency: 'EUR',
            status: 'pending',
            sage_category: '626000',
          },
          {
            id: 2,
            invoice_number: 'INV-2026-002',
            supplier_id: 2,
            supplier_name: 'CloudPro Services',
            invoice_date: '2026-01-10',
            due_date: '2026-02-10',
            amount: 8750.5,
            currency: 'EUR',
            status: 'paid',
            sage_category: '615000',
          },
          {
            id: 3,
            invoice_number: 'INV-2025-089',
            supplier_id: 3,
            supplier_name: 'SecurIT GmbH',
            invoice_date: '2025-12-01',
            due_date: '2026-01-01',
            amount: 5200,
            currency: 'EUR',
            status: 'overdue',
            sage_category: '622000',
          },
          {
            id: 4,
            invoice_number: 'INV-2026-003',
            supplier_id: 4,
            supplier_name: 'SoftVendor SA',
            invoice_date: '2026-01-20',
            due_date: '2026-02-20',
            amount: 18000,
            currency: 'EUR',
            status: 'pending',
            sage_category: '651000',
          },
          {
            id: 5,
            invoice_number: 'INV-2026-004',
            supplier_id: 1,
            supplier_name: 'TechCorp Solutions',
            invoice_date: '2026-01-22',
            due_date: '2026-02-22',
            amount: 3500,
            currency: 'EUR',
            status: 'pending',
            sage_category: '626000',
          },
        ])
      } finally {
        setIsLoading(false)
      }
    }
    fetchInvoices()
  }, [])

  const filteredInvoices = invoices.filter((invoice) => {
    const matchesSearch =
      invoice.invoice_number.toLowerCase().includes(search.toLowerCase()) ||
      invoice.supplier_name.toLowerCase().includes(search.toLowerCase())
    const matchesStatus =
      statusFilter === 'all' || invoice.status === statusFilter
    return matchesSearch && matchesStatus
  })

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'paid':
        return <Badge className="bg-green-100 text-green-800">Paid</Badge>
      case 'pending':
        return <Badge className="bg-amber-100 text-amber-800">Pending</Badge>
      case 'overdue':
        return <Badge className="bg-red-100 text-red-800">Overdue</Badge>
      case 'cancelled':
        return <Badge className="bg-gray-100 text-gray-800">Cancelled</Badge>
      default:
        return <Badge variant="outline">{status}</Badge>
    }
  }

  const formatCurrency = (value: number, currency: string) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency,
    }).format(value)
  }

  const totalPending = filteredInvoices
    .filter((i) => i.status === 'pending')
    .reduce((sum, i) => sum + i.amount, 0)

  const totalOverdue = filteredInvoices
    .filter((i) => i.status === 'overdue')
    .reduce((sum, i) => sum + i.amount, 0)

  return (
    <>
      <Header
        title="Invoices"
        description="Supplier invoices management"
        actions={
          <div className="flex gap-2">
            <Button variant="outline">
              <Upload className="h-4 w-4 mr-2" />
              Import CSV
            </Button>
            <Button variant="outline">
              <Download className="h-4 w-4 mr-2" />
              Export
            </Button>
            <Button onClick={() => router.push('/suppliers/invoices/new')}>
              <Plus className="h-4 w-4 mr-2" />
              New Invoice
            </Button>
          </div>
        }
      />

      <div className="p-6 space-y-6">
        {/* Summary Cards */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold">{filteredInvoices.length}</div>
              <p className="text-sm text-muted-foreground">Total Invoices</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold text-amber-600">
                {formatCurrency(totalPending, 'EUR')}
              </div>
              <p className="text-sm text-muted-foreground">Pending</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold text-red-600">
                {formatCurrency(totalOverdue, 'EUR')}
              </div>
              <p className="text-sm text-muted-foreground">Overdue</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold text-green-600">
                {filteredInvoices.filter((i) => i.status === 'paid').length}
              </div>
              <p className="text-sm text-muted-foreground">Paid this month</p>
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
                  placeholder="Search by number or supplier..."
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
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="paid">Paid</SelectItem>
                  <SelectItem value="overdue">Overdue</SelectItem>
                  <SelectItem value="cancelled">Cancelled</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* Invoices Table */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Receipt className="h-5 w-5" />
              Invoices List
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
                    <TableHead>Invoice #</TableHead>
                    <TableHead>Supplier</TableHead>
                    <TableHead>Invoice Date</TableHead>
                    <TableHead>Due Date</TableHead>
                    <TableHead>Amount</TableHead>
                    <TableHead>Sage Category</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredInvoices.map((invoice) => (
                    <TableRow
                      key={invoice.id}
                      className="cursor-pointer hover:bg-muted/50"
                      onClick={() =>
                        router.push(`/suppliers/${invoice.supplier_id}`)
                      }
                    >
                      <TableCell className="font-medium font-mono">
                        {invoice.invoice_number}
                      </TableCell>
                      <TableCell>{invoice.supplier_name}</TableCell>
                      <TableCell>
                        {safeDateString(invoice.invoice_date)}
                      </TableCell>
                      <TableCell>
                        {safeDateString(invoice.due_date)}
                      </TableCell>
                      <TableCell className="font-medium">
                        {formatCurrency(invoice.amount, invoice.currency)}
                      </TableCell>
                      <TableCell>
                        {invoice.sage_category && (
                          <Badge variant="outline">{invoice.sage_category}</Badge>
                        )}
                      </TableCell>
                      <TableCell>{getStatusBadge(invoice.status)}</TableCell>
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
