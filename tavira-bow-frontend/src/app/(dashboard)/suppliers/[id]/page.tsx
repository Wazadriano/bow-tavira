import { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Link } from 'react-router-dom'
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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  ArrowLeft,
  Edit,
  Trash2,
  Building2,
  FileText,
  Receipt,
  Plus,
  Loader2,
} from 'lucide-react'
import { toast } from 'sonner'
import type { SupplierContract, SupplierInvoice } from '@/types'

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
  const navigate = useNavigate()
  const id = Number(params.id)
  const [activeTab, setActiveTab] = useState('info')
  const [contractDialogOpen, setContractDialogOpen] = useState(false)
  const [invoiceDialogOpen, setInvoiceDialogOpen] = useState(false)
  const [contractEditOpen, setContractEditOpen] = useState(false)
  const [invoiceEditOpen, setInvoiceEditOpen] = useState(false)
  const [contractToEdit, setContractToEdit] = useState<SupplierContract | null>(null)
  const [invoiceToEdit, setInvoiceToEdit] = useState<SupplierInvoice | null>(null)
  const [isSavingForm, setIsSavingForm] = useState(false)
  const [contractForm, setContractForm] = useState({
    contract_ref: '',
    description: '',
    start_date: '',
    end_date: '',
    value: '',
    currency: 'EUR',
  })
  const [invoiceForm, setInvoiceForm] = useState({
    invoice_ref: '',
    description: '',
    amount: '',
    currency: 'EUR',
    invoice_date: '',
    due_date: '',
  })

  const {
    selectedItem,
    isLoadingItem,
    error,
    contracts,
    invoices,
    fetchById,
    fetchContracts,
    fetchInvoices,
    createContract,
    createInvoice,
    updateContract,
    deleteContract,
    updateInvoice,
    deleteInvoice,
    update,
    remove,
  } = useSuppliersStore()
  const { showConfirm } = useUIStore()
  const [newEntity, setNewEntity] = useState('')
  const [isSavingEntities, setIsSavingEntities] = useState(false)

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
          navigate('/suppliers')
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
  const activeContracts = contracts.filter(c => (c.status ?? '').toLowerCase() === 'active').length
  const totalInvoicesAmount = invoices.reduce((sum, inv) => sum + (inv.amount || 0), 0)

  return (
    <>
      <Header
        title={supplier.name}
        description={`Supplier ${supplier.status ? (STATUS_LABELS[(supplier.status ?? '').toLowerCase()] || supplier.status) : '-'}`}
      />

      <div className="p-6">
        <div className="mb-6 flex items-center justify-between">
          <Button variant="ghost" onClick={() => navigate(-1)}>
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link to={`/suppliers/${id}/edit`}>
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
            <TabsTrigger value="entities">Entities ({(supplier as { entities?: { entity: string }[] }).entities?.length ?? 0})</TabsTrigger>
            <TabsTrigger value="contracts">Contracts ({totalContracts})</TabsTrigger>
            <TabsTrigger value="invoices">Invoices ({invoices.length})</TabsTrigger>
            <TabsTrigger value="access">Access</TabsTrigger>
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
                    <span className="text-sm text-muted-foreground">Status</span>
                    <Badge className={supplier.status ? (STATUS_COLORS[(supplier.status ?? '').toLowerCase()] || '') : ''}>
                      {supplier.status ? (STATUS_LABELS[(supplier.status ?? '').toLowerCase()] || supplier.status) : '-'}
                    </Badge>
                  </div>

                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Location</span>
                    <span>{supplier.location}</span>
                  </div>

                  {supplier.sage_category && (
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-muted-foreground">SAGE Category</span>
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
                    <h4 className="text-sm font-medium text-muted-foreground">Created</h4>
                    <p className="mt-1 text-sm">{formatDate(supplier.created_at)}</p>
                  </div>

                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Modified</h4>
                    <p className="mt-1 text-sm">{formatDate(supplier.updated_at)}</p>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <TabsContent value="entities" className="mt-6">
            <Card>
              <CardHeader>
                <CardTitle>Entities</CardTitle>
              </CardHeader>
              <CardContent>
                  <p className="text-sm text-muted-foreground mb-4">
                    Entities associated with this supplier (multi-entity).
                  </p>
                  <div className="flex gap-2 mb-4">
                    <Input
                      placeholder="Entity name"
                      value={newEntity}
                      onChange={(e) => setNewEntity(e.target.value)}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                          e.preventDefault()
                          const v = newEntity.trim()
                          if (!v) return
                          const entities = ((supplier as { entities?: { entity: string }[] }).entities ?? []).map((x) => x.entity)
                          if (entities.includes(v)) return
                          setIsSavingEntities(true)
                          update(id, { entities: [...entities, v] })
                            .then(() => { fetchById(id); setNewEntity(''); toast.success('Entity added') })
                            .catch(() => toast.error('Error'))
                            .finally(() => setIsSavingEntities(false))
                        }
                      }}
                    />
                    <Button
                      disabled={!newEntity.trim() || isSavingEntities}
                      onClick={async () => {
                        const v = newEntity.trim()
                        if (!v) return
                        const entities = ((supplier as { entities?: { entity: string }[] }).entities ?? []).map((x) => x.entity)
                        if (entities.includes(v)) {
                          toast.error('This entity already exists')
                          return
                        }
                        setIsSavingEntities(true)
                        try {
                          await update(id, { entities: [...entities, v] })
                          await fetchById(id)
                          setNewEntity('')
                          toast.success('Entity added')
                        } catch {
                          toast.error('Error adding entity')
                        } finally {
                          setIsSavingEntities(false)
                        }
                      }}
                    >
                      Add
                    </Button>
                  </div>
                  {((supplier as { entities?: { id: number; entity: string }[] }).entities ?? []).length === 0 ? (
                    <p className="text-sm text-muted-foreground py-4">No entities.</p>
                  ) : (
                    <ul className="space-y-2">
                      {((supplier as { entities?: { id: number; entity: string }[] }).entities ?? []).map((ent) => (
                        <li
                          key={ent.id}
                          className="flex items-center justify-between rounded-lg border p-3"
                        >
                          <span>{ent.entity}</span>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="text-destructive"
                            disabled={isSavingEntities}
                            onClick={async () => {
                              const entities = ((supplier as { entities?: { entity: string }[] }).entities ?? [])
                                .map((x) => x.entity)
                                .filter((e) => e !== ent.entity)
                              setIsSavingEntities(true)
                              try {
                                await update(id, { entities })
                                await fetchById(id)
                                toast.success('Entity removed')
                              } catch {
                                toast.error('Error')
                              } finally {
                                setIsSavingEntities(false)
                              }
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </li>
                      ))}
                    </ul>
                  )}
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="contracts" className="mt-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle>Contracts</CardTitle>
                <Dialog open={contractDialogOpen} onOpenChange={setContractDialogOpen}>
                  <DialogTrigger asChild>
                    <Button size="sm">
                      <Plus className="mr-2 h-4 w-4" />
                      New Contract
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>New Contract</DialogTitle>
                      <DialogDescription>Add a contract for this supplier</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                      <div className="space-y-2">
                        <Label>Contract Ref *</Label>
                        <Input value={contractForm.contract_ref} onChange={(e) => setContractForm((f) => ({ ...f, contract_ref: e.target.value }))} />
                      </div>
                      <div className="space-y-2">
                        <Label>Description</Label>
                        <Input value={contractForm.description} onChange={(e) => setContractForm((f) => ({ ...f, description: e.target.value }))} />
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label>Start Date</Label>
                          <Input type="date" value={contractForm.start_date} onChange={(e) => setContractForm((f) => ({ ...f, start_date: e.target.value }))} />
                        </div>
                        <div className="space-y-2">
                          <Label>End Date</Label>
                          <Input type="date" value={contractForm.end_date} onChange={(e) => setContractForm((f) => ({ ...f, end_date: e.target.value }))} />
                        </div>
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label>Value</Label>
                          <Input type="number" value={contractForm.value} onChange={(e) => setContractForm((f) => ({ ...f, value: e.target.value }))} />
                        </div>
                        <div className="space-y-2">
                          <Label>Currency</Label>
                          <Input value={contractForm.currency} onChange={(e) => setContractForm((f) => ({ ...f, currency: e.target.value }))} />
                        </div>
                      </div>
                      <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setContractDialogOpen(false)}>Cancel</Button>
                        <Button
                          disabled={!contractForm.contract_ref.trim() || isSavingForm}
                          onClick={async () => {
                            setIsSavingForm(true)
                            try {
                              await createContract({
                                supplier_id: id,
                                contract_ref: contractForm.contract_ref,
                                description: contractForm.description || undefined,
                                start_date: contractForm.start_date || undefined,
                                end_date: contractForm.end_date || undefined,
                                value: contractForm.value ? Number(contractForm.value) : undefined,
                                currency: contractForm.currency || undefined,
                              })
                              toast.success('Contract created')
                              setContractDialogOpen(false)
                              setContractForm({ contract_ref: '', description: '', start_date: '', end_date: '', value: '', currency: 'EUR' })
                            } catch { toast.error('Error creating contract') }
                            finally { setIsSavingForm(false) }
                          }}
                        >
                          {isSavingForm && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                          Create
                        </Button>
                      </div>
                    </div>
                  </DialogContent>
                </Dialog>
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
                        <div className="flex items-center gap-2 text-right">
                          <Badge variant={contract.status === 'active' ? 'default' : 'secondary'}>
                            {contract.status}
                          </Badge>
                          {contract.value != null && (
                            <p className="mt-1 font-semibold">
                              {formatCurrency(contract.value, contract.currency)}
                            </p>
                          )}
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => {
                              setContractToEdit(contract)
                              setContractForm({
                                contract_ref: contract.contract_ref ?? '',
                                description: contract.description ?? '',
                                start_date: contract.start_date ? String(contract.start_date).slice(0, 10) : '',
                                end_date: contract.end_date ? String(contract.end_date).slice(0, 10) : '',
                                value: contract.value != null ? String(contract.value) : '',
                                currency: contract.currency ?? 'EUR',
                              })
                              setContractEditOpen(true)
                            }}
                          >
                            <Edit className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="text-destructive"
                            onClick={() => {
                              showConfirm({
                                title: 'Delete this contract',
                                description: 'This action cannot be undone.',
                                variant: 'destructive',
                                onConfirm: async () => {
                                  try {
                                    await deleteContract(id, contract.id)
                                    toast.success('Contract deleted')
                                  } catch {
                                    toast.error('Error deleting contract')
                                  }
                                },
                              })
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Edit Contract Dialog */}
            <Dialog open={contractEditOpen} onOpenChange={setContractEditOpen}>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Edit Contract</DialogTitle>
                  <DialogDescription>Update contract details</DialogDescription>
                </DialogHeader>
                <div className="space-y-4 py-4">
                  <div className="space-y-2">
                    <Label>Contract Ref *</Label>
                    <Input value={contractForm.contract_ref} onChange={(e) => setContractForm((f) => ({ ...f, contract_ref: e.target.value }))} />
                  </div>
                  <div className="space-y-2">
                    <Label>Description</Label>
                    <Input value={contractForm.description} onChange={(e) => setContractForm((f) => ({ ...f, description: e.target.value }))} />
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Start Date</Label>
                      <Input type="date" value={contractForm.start_date} onChange={(e) => setContractForm((f) => ({ ...f, start_date: e.target.value }))} />
                    </div>
                    <div className="space-y-2">
                      <Label>End Date</Label>
                      <Input type="date" value={contractForm.end_date} onChange={(e) => setContractForm((f) => ({ ...f, end_date: e.target.value }))} />
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Value</Label>
                      <Input type="number" value={contractForm.value} onChange={(e) => setContractForm((f) => ({ ...f, value: e.target.value }))} />
                    </div>
                    <div className="space-y-2">
                      <Label>Currency</Label>
                      <Input value={contractForm.currency} onChange={(e) => setContractForm((f) => ({ ...f, currency: e.target.value }))} />
                    </div>
                  </div>
                  <div className="flex justify-end gap-2">
                    <Button variant="outline" onClick={() => setContractEditOpen(false)}>Cancel</Button>
                    <Button
                      disabled={!contractForm.contract_ref.trim() || !contractToEdit || isSavingForm}
                      onClick={async () => {
                        if (!contractToEdit) return
                        setIsSavingForm(true)
                        try {
                          await updateContract(id, contractToEdit.id, {
                            contract_ref: contractForm.contract_ref,
                            description: contractForm.description || undefined,
                            start_date: contractForm.start_date || undefined,
                            end_date: contractForm.end_date || undefined,
                            value: contractForm.value ? Number(contractForm.value) : undefined,
                            currency: contractForm.currency || undefined,
                          })
                          toast.success('Contract updated')
                          setContractEditOpen(false)
                          setContractToEdit(null)
                        } catch {
                          toast.error('Error updating contract')
                        } finally {
                          setIsSavingForm(false)
                        }
                      }}
                    >
                      {isSavingForm && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                      Save
                    </Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          </TabsContent>

          <TabsContent value="invoices" className="mt-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle>Invoices</CardTitle>
                <Dialog open={invoiceDialogOpen} onOpenChange={setInvoiceDialogOpen}>
                  <DialogTrigger asChild>
                    <Button size="sm">
                      <Plus className="mr-2 h-4 w-4" />
                      New Invoice
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>New Invoice</DialogTitle>
                      <DialogDescription>Add an invoice for this supplier</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                      <div className="space-y-2">
                        <Label>Invoice Ref *</Label>
                        <Input value={invoiceForm.invoice_ref} onChange={(e) => setInvoiceForm((f) => ({ ...f, invoice_ref: e.target.value }))} />
                      </div>
                      <div className="space-y-2">
                        <Label>Description</Label>
                        <Input value={invoiceForm.description} onChange={(e) => setInvoiceForm((f) => ({ ...f, description: e.target.value }))} />
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label>Amount *</Label>
                          <Input type="number" value={invoiceForm.amount} onChange={(e) => setInvoiceForm((f) => ({ ...f, amount: e.target.value }))} />
                        </div>
                        <div className="space-y-2">
                          <Label>Currency</Label>
                          <Input value={invoiceForm.currency} onChange={(e) => setInvoiceForm((f) => ({ ...f, currency: e.target.value }))} />
                        </div>
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label>Invoice Date</Label>
                          <Input type="date" value={invoiceForm.invoice_date} onChange={(e) => setInvoiceForm((f) => ({ ...f, invoice_date: e.target.value }))} />
                        </div>
                        <div className="space-y-2">
                          <Label>Due Date</Label>
                          <Input type="date" value={invoiceForm.due_date} onChange={(e) => setInvoiceForm((f) => ({ ...f, due_date: e.target.value }))} />
                        </div>
                      </div>
                      <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setInvoiceDialogOpen(false)}>Cancel</Button>
                        <Button
                          disabled={!invoiceForm.invoice_ref.trim() || !invoiceForm.amount || isSavingForm}
                          onClick={async () => {
                            setIsSavingForm(true)
                            try {
                              await createInvoice({
                                supplier_id: id,
                                invoice_ref: invoiceForm.invoice_ref,
                                description: invoiceForm.description || undefined,
                                amount: Number(invoiceForm.amount),
                                currency: invoiceForm.currency || undefined,
                                invoice_date: invoiceForm.invoice_date || new Date().toISOString().split('T')[0],
                                due_date: invoiceForm.due_date || undefined,
                              })
                              toast.success('Invoice created')
                              setInvoiceDialogOpen(false)
                              setInvoiceForm({ invoice_ref: '', description: '', amount: '', currency: 'EUR', invoice_date: '', due_date: '' })
                            } catch { toast.error('Error creating invoice') }
                            finally { setIsSavingForm(false) }
                          }}
                        >
                          {isSavingForm && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                          Create
                        </Button>
                      </div>
                    </div>
                  </DialogContent>
                </Dialog>
              </CardHeader>
              <CardContent>
                {invoices.length === 0 ? (
                  <p className="py-8 text-center text-muted-foreground">
                    No invoices for this supplier
                  </p>
                ) : (
                  <div className="space-y-4">
                    {invoices.map((invoice) => {
                      const invRef = (invoice as { invoice_ref?: string; invoice_number?: string }).invoice_ref ?? (invoice as { invoice_number?: string }).invoice_number ?? ''
                      return (
                        <div
                          key={invoice.id}
                          className="flex items-center justify-between rounded-lg border p-4"
                        >
                          <div>
                            <p className="font-medium">{invRef}</p>
                            <p className="text-sm text-muted-foreground">
                              {formatDate(invoice.invoice_date)}
                              {invoice.description && ` - ${invoice.description}`}
                            </p>
                          </div>
                          <div className="flex items-center gap-2 text-right">
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
                            {(() => {
                              const sage = (invoice as unknown as { sage_category?: { name: string } }).sage_category
                              return sage?.name ? (
                                <Badge variant="outline" className="text-xs">
                                  Sage: {sage.name}
                                </Badge>
                              ) : null
                            })()}
                            <p className="mt-1 font-semibold">
                              {formatCurrency(invoice.amount, invoice.currency)}
                            </p>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => {
                                setInvoiceToEdit(invoice)
                                setInvoiceForm({
                                  invoice_ref: invRef,
                                  description: invoice.description ?? '',
                                  amount: invoice.amount != null ? String(invoice.amount) : '',
                                  currency: invoice.currency ?? 'EUR',
                                  invoice_date: invoice.invoice_date ? String(invoice.invoice_date).slice(0, 10) : '',
                                  due_date: invoice.due_date ? String(invoice.due_date).slice(0, 10) : '',
                                })
                                setInvoiceEditOpen(true)
                              }}
                            >
                              <Edit className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-destructive"
                              onClick={() => {
                                showConfirm({
                                  title: 'Delete this invoice',
                                  description: 'This action cannot be undone.',
                                  variant: 'destructive',
                                  onConfirm: async () => {
                                    try {
                                      await deleteInvoice(id, invoice.id)
                                      toast.success('Invoice deleted')
                                    } catch {
                                      toast.error('Error deleting invoice')
                                    }
                                  },
                                })
                              }}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      )
                    })}
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Edit Invoice Dialog */}
            <Dialog open={invoiceEditOpen} onOpenChange={setInvoiceEditOpen}>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Edit Invoice</DialogTitle>
                  <DialogDescription>Update invoice details</DialogDescription>
                </DialogHeader>
                <div className="space-y-4 py-4">
                  <div className="space-y-2">
                    <Label>Invoice Ref *</Label>
                    <Input value={invoiceForm.invoice_ref} onChange={(e) => setInvoiceForm((f) => ({ ...f, invoice_ref: e.target.value }))} />
                  </div>
                  <div className="space-y-2">
                    <Label>Description</Label>
                    <Input value={invoiceForm.description} onChange={(e) => setInvoiceForm((f) => ({ ...f, description: e.target.value }))} />
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Amount *</Label>
                      <Input type="number" value={invoiceForm.amount} onChange={(e) => setInvoiceForm((f) => ({ ...f, amount: e.target.value }))} />
                    </div>
                    <div className="space-y-2">
                      <Label>Currency</Label>
                      <Input value={invoiceForm.currency} onChange={(e) => setInvoiceForm((f) => ({ ...f, currency: e.target.value }))} />
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Invoice Date</Label>
                      <Input type="date" value={invoiceForm.invoice_date} onChange={(e) => setInvoiceForm((f) => ({ ...f, invoice_date: e.target.value }))} />
                    </div>
                    <div className="space-y-2">
                      <Label>Due Date</Label>
                      <Input type="date" value={invoiceForm.due_date} onChange={(e) => setInvoiceForm((f) => ({ ...f, due_date: e.target.value }))} />
                    </div>
                  </div>
                  <div className="flex justify-end gap-2">
                    <Button variant="outline" onClick={() => setInvoiceEditOpen(false)}>Cancel</Button>
                    <Button
                      disabled={!invoiceForm.invoice_ref.trim() || !invoiceForm.amount || !invoiceToEdit || isSavingForm}
                      onClick={async () => {
                        if (!invoiceToEdit) return
                        setIsSavingForm(true)
                        try {
                          await updateInvoice(id, invoiceToEdit.id, {
                            invoice_ref: invoiceForm.invoice_ref,
                            description: invoiceForm.description || undefined,
                            amount: Number(invoiceForm.amount),
                            currency: invoiceForm.currency || undefined,
                            invoice_date: invoiceForm.invoice_date || undefined,
                            due_date: invoiceForm.due_date || undefined,
                          })
                          toast.success('Invoice updated')
                          setInvoiceEditOpen(false)
                          setInvoiceToEdit(null)
                        } catch {
                          toast.error('Error updating invoice')
                        } finally {
                          setIsSavingForm(false)
                        }
                      }}
                    >
                      {isSavingForm && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                      Save
                    </Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
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
