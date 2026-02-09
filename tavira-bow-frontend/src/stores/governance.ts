import { create } from 'zustand'
import { api } from '@/lib/api'
import type {
  GovernanceItem,
  GovernanceFormData,
  GovernanceFilters,
  GovernanceMilestone,
  MilestoneFormData,
  PaginatedResponse,
} from '@/types'

interface GovernanceState {
  // Data
  items: GovernanceItem[]
  selectedItem: GovernanceItem | null
  milestones: GovernanceMilestone[]

  // Pagination
  currentPage: number
  lastPage: number
  total: number
  perPage: number

  // Filters
  filters: GovernanceFilters

  // Loading states
  isLoading: boolean
  isLoadingItem: boolean
  isSaving: boolean
  error: string | null

  // Actions - List
  fetchItems: (page?: number) => Promise<void>
  setFilters: (filters: Partial<GovernanceFilters>) => void
  resetFilters: () => void

  // Actions - CRUD
  fetchById: (id: number) => Promise<GovernanceItem>
  create: (data: GovernanceFormData) => Promise<GovernanceItem>
  update: (id: number, data: Partial<GovernanceFormData>) => Promise<GovernanceItem>
  remove: (id: number) => Promise<void>

  // Actions - Selection
  selectItem: (item: GovernanceItem | null) => void

  // Actions - Milestones
  fetchMilestones: (itemId: number) => Promise<void>
  createMilestone: (itemId: number, data: MilestoneFormData) => Promise<GovernanceMilestone>
  updateMilestone: (milestoneId: number, data: Partial<MilestoneFormData>) => Promise<void>
  deleteMilestone: (milestoneId: number) => Promise<void>

  // Actions - Files
  uploadFile: (itemId: number, file: File) => Promise<void>
  deleteFile: (itemId: number, fileId: number) => Promise<void>

  // Utility
  clearError: () => void
}

const defaultFilters: GovernanceFilters = {
  department: undefined,
  status: undefined,
  rag_status: undefined,
  frequency: undefined,
  search: undefined,
}

export const useGovernanceStore = create<GovernanceState>((set, get) => ({
  // Initial state
  items: [],
  selectedItem: null,
  milestones: [],
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

      const response = await api.get<PaginatedResponse<GovernanceItem>>(
        `/governance/items?${params}`
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
        error instanceof Error ? error.message : 'Failed to fetch governance items'
      set({ error: message, isLoading: false })
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
      const response = await api.get<{ data: GovernanceItem }>(
        `/governance/items/${id}`
      )
      const item = response.data.data
      set({ selectedItem: item, isLoadingItem: false })
      return item
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch governance item'
      set({ error: message, isLoadingItem: false })
      throw error
    }
  },

  create: async (data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.post<{ data: GovernanceItem }>(
        '/governance/items',
        data
      )
      const newItem = response.data.data
      set((state) => ({
        items: [newItem, ...state.items],
        isSaving: false,
      }))
      return newItem
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create governance item'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  update: async (id, data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.put<{ data: GovernanceItem }>(
        `/governance/items/${id}`,
        data
      )
      const updatedItem = response.data.data
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
        error instanceof Error ? error.message : 'Failed to update governance item'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  remove: async (id) => {
    set({ isSaving: true, error: null })
    try {
      await api.delete(`/governance/items/${id}`)
      set((state) => ({
        items: state.items.filter((item) => item.id !== id),
        selectedItem: state.selectedItem?.id === id ? null : state.selectedItem,
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete governance item'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  selectItem: (item) => set({ selectedItem: item }),

  // Milestones
  fetchMilestones: async (itemId) => {
    try {
      const response = await api.get<{ data: GovernanceMilestone[] }>(
        `/governance/items/${itemId}/milestones`
      )
      set({ milestones: response.data.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch milestones'
      set({ error: message })
    }
  },

  createMilestone: async (itemId, data) => {
    try {
      const response = await api.post<{ data: GovernanceMilestone }>(
        `/governance/items/${itemId}/milestones`,
        data
      )
      const newMilestone = response.data.data
      set((state) => ({
        milestones: [...state.milestones, newMilestone],
      }))
      return newMilestone
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create milestone'
      set({ error: message })
      throw error
    }
  },

  updateMilestone: async (milestoneId, data) => {
    try {
      const response = await api.put<{ data: GovernanceMilestone }>(
        `/governance/milestones/${milestoneId}`,
        data
      )
      set((state) => ({
        milestones: state.milestones.map((m) =>
          m.id === milestoneId ? response.data.data : m
        ),
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update milestone'
      set({ error: message })
      throw error
    }
  },

  deleteMilestone: async (milestoneId) => {
    try {
      await api.delete(`/governance/milestones/${milestoneId}`)
      set((state) => ({
        milestones: state.milestones.filter((m) => m.id !== milestoneId),
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete milestone'
      set({ error: message })
      throw error
    }
  },

  // Files
  uploadFile: async (itemId, file) => {
    try {
      const formData = new FormData()
      formData.append('file', file)
      await api.post(`/governance/items/${itemId}/files`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      get().fetchById(itemId)
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to upload file'
      set({ error: message })
      throw error
    }
  },

  deleteFile: async (itemId, fileId) => {
    try {
      await api.delete(`/governance/items/${itemId}/files/${fileId}`)
      get().fetchById(itemId)
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete file'
      set({ error: message })
      throw error
    }
  },

  clearError: () => set({ error: null }),
}))
