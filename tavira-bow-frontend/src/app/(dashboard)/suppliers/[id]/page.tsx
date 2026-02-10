'use client'

import { useEffect, useState } from 'react'
import { useParams, useRouter } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { PageLoading, ErrorState, AccessManagementPanel } from '@/components/shared'
import { useSuppliersStore } from '@/stores/suppliers'
import { useUIStore } from '@/stores/ui'
import { formatDate, formatCurrency } from '@/lib/utils'
import {
  ArrowLeft,
  Edit,
  Trash2,
  Building2,
  FileText,
  Receipt,
  Plus,
} from 'lucide-react'
import { toast } from 'sonner'

const STATUS_LABELS: Record<string, string> = {
  active: 'Active',
  inactive: 'Inactive',
  pending: 'Pending',
}

const STATUS_COLORS: Record<string, string> = {
  active: 'bg-green-100 text-green-800',
  inactive: 'bg-gray-100 text-gray-800',
  pending: 'bg-amber-100 text-amber-800',
}

export default function SupplierDetailPage() {
  const params = useParams()
  const router = useRouter()
  const id = Number(params.id)
  const [activeTab, setActiveTab] = useState('info')

  const {
    selectedItem,
    isLoadingItem,
    error,
    contracts,
    invoices,
    fetchById,
    fetchContracts,
    fetchInvoices,
    remove,
  } = useSuppliersStore()
  const { showConfirm } = useUIStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
      fetchContracts(id)
      fetchInvoices(id)
    }
  }, [id, fetchById, fetchContracts, fetchInvoices])

  const handleDelete = () => {
    showConfirm({
      title: 'Delete this supplier',
      description: 'This action is irreversible. All associated contracts and invoices will also be deleted.',
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await remove(id)
          toast.success('Supplier deleted')
          router.push('/suppliers')
        } catch {
          toast.error('Error during deletion')
        }
      },
    })
  }

  if (isLoadingItem) {
    return <PageLoading text="Loading supplier..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Supplier not found"
        description={error || "This supplier does not exist or has been deleted."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  const supplier = selectedItem
  const totalContracts = contracts.length
  const activeContracts = contracts.filter(c => c.status?.toLowerCase() === 'active').length
  const totalInvoicesAmount = invoices.reduce((sum, inv) => sum + (inv.amount || 0), 0)

  return (
    <>
      <Header
        title={supplier.name}
        description={`Supplier ${supplier.status ? (STATUS_LABELS[supplier.status.toLowerCase()] || supplier.status) : '-'}`}
      />

      <div className="p-6">
        <div className="mb-6 flex items-center justify-between">
          <Button variant="ghost" asChild>
            <Link href="/suppliers">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to list
            </Link>
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={`/suppliers/${id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </Button>
            <Button variant="destructive" onClick={handleDelete}>
              <Trash2 className="mr-2 h-4 w-4" />
              Delete
            </Button>
          </div>
        </div>

        {/* Stats cards */}
        <div className="mb-6 grid gap-4 md:grid-cols-4">
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-4">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                  <FileText className="h-6 w-6 text-blue-600" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Contracts</p>
                  <p className="text-2xl font-bold">{totalContracts}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-4">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                  <FileText className="h-6 w-6 text-green-600" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Active Contracts</p>
                  <p className="text-2xl font-bold">{activeContracts}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-4">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                  <Receipt className="h-6 w-6 text-purple-600" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Invoices</p>
                  <p className="text-2xl font-bold">{invoices.length}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-4">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                  <Receipt className="h-6 w-6 text-amber-600" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Total Invoice</p>
                  <p className="text-2xl font-bold">{formatCurrency(totalInvoicesAmount)}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList>
            <TabsTrigger value="info">Information</TabsTrigger>
            <TabsTrigger value="contracts">Contracts ({totalContracts})</TabsTrigger>
            <TabsTrigger value="invoices">Invoices ({invoices.length})</TabsTrigger>
            <TabsTrigger value="access">Acces</TabsTrigger>
          </TabsList>

          <TabsContent value="info" className="mt-6">
            <div className="grid gap-6 lg:grid-cols-2">
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Building2 className="h-5 w-5" />
                    General Information
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Statut</span>
                    <Badge className={supplier.status ? (STATUS_COLORS[supplier.status.toLowerCase()] || '') : ''}>
                      {supplier.status ? (STATUS_LABELS[supplier.status.toLowerCase()] || supplier.status) : '-'}
                    </Badge>
                  </div>

                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Localisation</span>
                    <span>{supplier.location}</span>
                  </div>

                  {supplier.sage_category && (
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-muted-foreground">Categorie SAGE</span>
                      <Badge variant="outline">{supplier.sage_category.name}</Badge>
                    </div>
                  )}

                  {supplier.notes && (
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">Notes</h4>
                      <p className="mt-1 whitespace-pre-wrap text-sm">{supplier.notes}</p>
                    </div>
                  )}
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Additional Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  {supplier.responsible_party && (
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">Responsible Party</h4>
                      <p className="mt-1">{supplier.responsible_party.full_name}</p>
                    </div>
                  )}

                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Common Supplier</span>
                    <Badge variant={supplier.is_common_provider ? 'default' : 'secondary'}>
                      {supplier.is_common_provider ? 'Yes' : 'No'}
                    </Badge>
                  </div>

                  <Separator />

                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Cree le</h4>
                    <p className="mt-1 text-sm">{formatDate(supplier.created_at)}</p>
                  </div>

                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Modifie le</h4>
                    <p className="mt-1 text-sm">{formatDate(supplier.updated_at)}</p>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <TabsContent value="contracts" className="mt-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle>Contracts</CardTitle>
                <Button size="sm">
                  <Plus className="mr-2 h-4 w-4" />
                  New Contract
                </Button>
              </CardHeader>
              <CardContent>
                {contracts.length === 0 ? (
                  <p className="py-8 text-center text-muted-foreground">
                    No contracts for this supplier
                  </p>
                ) : (
                  <div className="space-y-4">
                    {contracts.map((contract) => (
                      <div
                        key={contract.id}
                        className="flex items-center justify-between rounded-lg border p-4"
                      >
                        <div>
                          <p className="font-medium">{contract.description || contract.contract_ref}</p>
                          <p className="text-sm text-muted-foreground">
                            {contract.contract_ref} | {formatDate(contract.start_date)} - {formatDate(contract.end_date)}
                          </p>
                        </div>
                        <div className="text-right">
                          <Badge variant={contract.status === 'active' ? 'default' : 'secondary'}>
                            {contract.status}
                          </Badge>
                          {contract.value && (
                            <p className="mt-1 font-semibold">
                              {formatCurrency(contract.value, contract.currency)}
                            </p>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="invoices" className="mt-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle>Invoices</CardTitle>
                <Button size="sm">
                  <Plus className="mr-2 h-4 w-4" />
                  New Invoice
                </Button>
              </CardHeader>
              <CardContent>
                {invoices.length === 0 ? (
                  <p className="py-8 text-center text-muted-foreground">
                    No invoices for this supplier
                  </p>
                ) : (
                  <div className="space-y-4">
                    {invoices.map((invoice) => (
                      <div
                        key={invoice.id}
                        className="flex items-center justify-between rounded-lg border p-4"
                      >
                        <div>
                          <p className="font-medium">{invoice.invoice_ref}</p>
                          <p className="text-sm text-muted-foreground">
                            {formatDate(invoice.invoice_date)}
                            {invoice.description && ` - ${invoice.description}`}
                          </p>
                        </div>
                        <div className="text-right">
                          <Badge
                            variant={
                              invoice.status === 'paid'
                                ? 'default'
                                : invoice.status === 'overdue'
                                ? 'destructive'
                                : 'secondary'
                            }
                          >
                            {invoice.status}
                          </Badge>
                          <p className="mt-1 font-semibold">
                            {formatCurrency(invoice.amount, invoice.currency)}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="access" className="mt-6">
            <AccessManagementPanel
              resourceType="supplier"
              resourceId={supplier.id}
              currentAccess={[]}
              onAccessUpdated={() => fetchById(id)}
            />
          </TabsContent>
        </Tabs>
      </div>
    </>
  )
}
