import { create } from 'zustand'
import { api } from '@/lib/api'
import type {
  Supplier,
  SupplierFormData,
  SupplierFilters,
  SupplierContract,
  SupplierInvoice,
  ContractFormData,
  InvoiceFormData,
  PaginatedResponse,
  SupplierDashboardStats,
} from '@/types'

interface SuppliersState {
  // Data
  items: Supplier[]
  selectedItem: Supplier | null
  contracts: SupplierContract[]
  invoices: SupplierInvoice[]
  stats: SupplierDashboardStats | null

  // Pagination
  currentPage: number
  lastPage: number
  total: number
  perPage: number

  // Filters
  filters: SupplierFilters

  // Loading states
  isLoading: boolean
  isLoadingItem: boolean
  isSaving: boolean
  error: string | null

  // Actions - List
  fetchItems: (page?: number) => Promise<void>
  fetchStats: () => Promise<void>
  setFilters: (filters: Partial<SupplierFilters>) => void
  resetFilters: () => void

  // Actions - CRUD
  fetchById: (id: number) => Promise<Supplier>
  create: (data: SupplierFormData) => Promise<Supplier>
  update: (id: number, data: Partial<SupplierFormData>) => Promise<Supplier>
  remove: (id: number) => Promise<void>

  // Actions - Selection
  selectItem: (item: Supplier | null) => void

  // Actions - Contracts
  fetchContracts: (supplierId: number) => Promise<void>
  createContract: (data: ContractFormData) => Promise<SupplierContract>
  updateContract: (
    supplierId: number,
    contractId: number,
    data: Partial<ContractFormData>
  ) => Promise<void>
  deleteContract: (supplierId: number, contractId: number) => Promise<void>

  // Actions - Invoices
  fetchInvoices: (supplierId: number) => Promise<void>
  createInvoice: (data: InvoiceFormData) => Promise<SupplierInvoice>
  updateInvoice: (
    supplierId: number,
    invoiceId: number,
    data: Partial<InvoiceFormData>
  ) => Promise<void>
  deleteInvoice: (supplierId: number, invoiceId: number) => Promise<void>
  importInvoices: (supplierId: number, file: File) => Promise<void>

  // Utility
  clearError: () => void
}

const defaultFilters: SupplierFilters = {
  location: undefined,
  status: undefined,
  sage_category_id: undefined,
  search: undefined,
}

export const useSuppliersStore = create<SuppliersState>((set, get) => ({
  // Initial state
  items: [],
  selectedItem: null,
  contracts: [],
  invoices: [],
  stats: null,
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 20,
  filters: { ...defaultFilters },
  isLoading: false,
  isLoadingItem: false,
  isSaving: false,
  error: null,

  // Fetch list
  fetchItems: async (page = 1) => {
    set({ isLoading: true, error: null })
    try {
      const { filters, perPage } = get()
      const params = new URLSearchParams()
      params.append('page', String(page))
      params.append('per_page', String(perPage))

      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== '') {
          params.append(key, String(value))
        }
      })

      const response = await api.get<PaginatedResponse<Supplier>>(
        `/suppliers?${params}`
      )

      set({
        items: response.data.data,
        currentPage: response.data.current_page,
        lastPage: response.data.last_page,
        total: response.data.total,
        perPage: response.data.per_page,
        isLoading: false,
      })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch suppliers'
      set({ error: message, isLoading: false })
    }
  },

  fetchStats: async () => {
    try {
      const response = await api.get<SupplierDashboardStats>(
        '/suppliers-dashboard'
      )
      set({ stats: response.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch stats'
      set({ error: message })
    }
  },

  setFilters: (newFilters) => {
    set((state) => ({
      filters: { ...state.filters, ...newFilters },
    }))
    get().fetchItems(1)
  },

  resetFilters: () => {
    set({ filters: { ...defaultFilters } })
    get().fetchItems(1)
  },

  // CRUD
  fetchById: async (id) => {
    set({ isLoadingItem: true, error: null })
    try {
      const response = await api.get<{ supplier: Supplier }>(`/suppliers/${id}`)
      const item = response.data.supplier
      set({ selectedItem: item, isLoadingItem: false })
      return item
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch supplier'
      set({ error: message, isLoadingItem: false })
      throw error
    }
  },

  create: async (data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.post<{ supplier: Supplier }>('/suppliers', data)
      const newItem = response.data.supplier
      set((state) => ({
        items: [newItem, ...state.items],
        isSaving: false,
      }))
      return newItem
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create supplier'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  update: async (id, data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.put<{ supplier: Supplier }>(
        `/suppliers/${id}`,
        data
      )
      const updatedItem = response.data.supplier
      set((state) => ({
        items: state.items.map((item) =>
          item.id === id ? updatedItem : item
        ),
        selectedItem:
          state.selectedItem?.id === id ? updatedItem : state.selectedItem,
        isSaving: false,
      }))
      return updatedItem
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update supplier'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  remove: async (id) => {
    set({ isSaving: true, error: null })
    try {
      await api.delete(`/suppliers/${id}`)
      set((state) => ({
        items: state.items.filter((item) => item.id !== id),
        selectedItem: state.selectedItem?.id === id ? null : state.selectedItem,
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete supplier'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  selectItem: (item) => set({ selectedItem: item }),

  // Contracts
  fetchContracts: async (supplierId) => {
    try {
      const response = await api.get<SupplierContract[]>(
        `/suppliers/${supplierId}/contracts`
      )
      set({ contracts: response.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch contracts'
      set({ error: message })
    }
  },

  createContract: async (data) => {
    set({ isSaving: true })
    try {
      const response = await api.post<{ contract: SupplierContract }>(
        `/suppliers/${data.supplier_id}/contracts`,
        data
      )
      const newContract = response.data.contract
      set((state) => ({
        contracts: [...state.contracts, newContract],
        isSaving: false,
      }))
      return newContract
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create contract'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  updateContract: async (supplierId, contractId, data) => {
    set({ isSaving: true })
    try {
      const response = await api.put<{ contract: SupplierContract }>(
        `/suppliers/${supplierId}/contracts/${contractId}`,
        data
      )
      set((state) => ({
        contracts: state.contracts.map((c) =>
          c.id === contractId ? response.data.contract : c
        ),
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update contract'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  deleteContract: async (supplierId, contractId) => {
    try {
      await api.delete(`/suppliers/${supplierId}/contracts/${contractId}`)
      set((state) => ({
        contracts: state.contracts.filter((c) => c.id !== contractId),
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete contract'
      set({ error: message })
      throw error
    }
  },

  // Invoices
  fetchInvoices: async (supplierId) => {
    try {
      const response = await api.get<SupplierInvoice[]>(
        `/suppliers/${supplierId}/invoices`
      )
      set({ invoices: response.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch invoices'
      set({ error: message })
    }
  },

  createInvoice: async (data) => {
    set({ isSaving: true })
    try {
      const response = await api.post<{ invoice: SupplierInvoice }>(
        `/suppliers/${data.supplier_id}/invoices`,
        data
      )
      const newInvoice = response.data.invoice
      set((state) => ({
        invoices: [...state.invoices, newInvoice],
        isSaving: false,
      }))
      return newInvoice
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create invoice'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  updateInvoice: async (supplierId, invoiceId, data) => {
    set({ isSaving: true })
    try {
      const response = await api.put<{ invoice: SupplierInvoice }>(
        `/suppliers/${supplierId}/invoices/${invoiceId}`,
        data
      )
      set((state) => ({
        invoices: state.invoices.map((i) =>
          i.id === invoiceId ? response.data.invoice : i
        ),
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update invoice'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  deleteInvoice: async (supplierId, invoiceId) => {
    try {
      await api.delete(`/suppliers/${supplierId}/invoices/${invoiceId}`)
      set((state) => ({
        invoices: state.invoices.filter((i) => i.id !== invoiceId),
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete invoice'
      set({ error: message })
      throw error
    }
  },

  importInvoices: async (supplierId, file) => {
    set({ isSaving: true })
    try {
      const formData = new FormData()
      formData.append('file', file)
      await api.post(`/suppliers/${supplierId}/invoices/import`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      get().fetchInvoices(supplierId)
      set({ isSaving: false })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to import invoices'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  clearError: () => set({ error: null }),
}))
