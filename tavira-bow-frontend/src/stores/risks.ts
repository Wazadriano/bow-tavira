import { create } from 'zustand'
import { api } from '@/lib/api'
import type {
  Risk,
  RiskFormData,
  RiskFilters,
  RiskTheme,
  RiskCategory,
  RiskControl,
  RiskAction,
  ControlLibrary,
  RiskActionFormData,
  RiskControlFormData,
  PaginatedResponse,
  HeatmapData,
  RiskDashboardStats,
} from '@/types'

interface RisksState {
  // Data
  items: Risk[]
  selectedItem: Risk | null
  themes: RiskTheme[]
  categories: RiskCategory[]
  controls: RiskControl[]
  actions: RiskAction[]
  controlLibrary: ControlLibrary[]
  heatmapData: HeatmapData | null
  stats: RiskDashboardStats | null

  // Pagination
  currentPage: number
  lastPage: number
  total: number
  perPage: number

  // Filters
  filters: RiskFilters

  // Loading states
  isLoading: boolean
  isLoadingItem: boolean
  isSaving: boolean
  error: string | null

  // Actions - List
  fetchItems: (page?: number) => Promise<void>
  fetchThemes: () => Promise<void>
  fetchCategories: (themeId?: number) => Promise<void>
  fetchControlLibrary: () => Promise<void>
  fetchHeatmap: (type?: 'inherent' | 'residual') => Promise<void>
  fetchStats: () => Promise<void>
  setFilters: (filters: Partial<RiskFilters>) => void
  resetFilters: () => void

  // Actions - CRUD
  fetchById: (id: number) => Promise<Risk>
  create: (data: RiskFormData) => Promise<Risk>
  update: (id: number, data: Partial<RiskFormData>) => Promise<Risk>
  remove: (id: number) => Promise<void>
  recalculateScores: (id: number) => Promise<void>

  // Actions - Selection
  selectItem: (item: Risk | null) => void

  // Actions - Controls
  fetchControls: (riskId: number) => Promise<void>
  addControl: (riskId: number, data: RiskControlFormData) => Promise<void>
  updateControl: (
    riskId: number,
    controlId: number,
    data: Partial<RiskControlFormData>
  ) => Promise<void>
  removeControl: (riskId: number, controlId: number) => Promise<void>

  // Actions - Actions
  fetchActions: (riskId: number) => Promise<void>
  createAction: (riskId: number, data: RiskActionFormData) => Promise<RiskAction>
  updateAction: (
    riskId: number,
    actionId: number,
    data: Partial<RiskActionFormData>
  ) => Promise<void>
  deleteAction: (riskId: number, actionId: number) => Promise<void>

  // Utility
  clearError: () => void
}

const defaultFilters: RiskFilters = {
  theme_id: undefined,
  category_id: undefined,
  tier: undefined,
  rag_status: undefined,
  appetite_status: undefined,
  owner_id: undefined,
  search: undefined,
}

export const useRisksStore = create<RisksState>((set, get) => ({
  // Initial state
  items: [],
  selectedItem: null,
  themes: [],
  categories: [],
  controls: [],
  actions: [],
  controlLibrary: [],
  heatmapData: null,
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

      const response = await api.get<PaginatedResponse<Risk>>(
        `/risks?${params}`
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
        error instanceof Error ? error.message : 'Failed to fetch risks'
      set({ error: message, isLoading: false })
    }
  },

  fetchThemes: async () => {
    try {
      const response = await api.get<{ data: RiskTheme[] }>('/risks/themes')
      set({ themes: response.data.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch themes'
      set({ error: message })
    }
  },

  fetchCategories: async (themeId) => {
    try {
      const url = themeId
        ? `/risks/categories?theme_id=${themeId}`
        : '/risks/categories'
      const response = await api.get<{ data: RiskCategory[] }>(url)
      set({ categories: response.data.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch categories'
      set({ error: message })
    }
  },

  fetchControlLibrary: async () => {
    try {
      const response = await api.get<{ data: ControlLibrary[] }>(
        '/risks/controls/library'
      )
      set({ controlLibrary: response.data.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch control library'
      set({ error: message })
    }
  },

  fetchHeatmap: async (type = 'inherent') => {
    try {
      const response = await api.get<{ data: HeatmapData }>(
        `/risks/heatmap?type=${type}`
      )
      set({ heatmapData: response.data.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch heatmap'
      set({ error: message })
    }
  },

  fetchStats: async () => {
    try {
      const response = await api.get<{ data: RiskDashboardStats }>(
        '/risks/dashboard/stats'
      )
      set({ stats: response.data.data })
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
      const response = await api.get<{ data: Risk }>(`/risks/${id}`)
      const item = response.data.data
      set({ selectedItem: item, isLoadingItem: false })
      return item
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch risk'
      set({ error: message, isLoadingItem: false })
      throw error
    }
  },

  create: async (data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.post<{ data: Risk }>('/risks', data)
      const newItem = response.data.data
      set((state) => ({
        items: [newItem, ...state.items],
        isSaving: false,
      }))
      return newItem
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create risk'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  update: async (id, data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.put<{ data: Risk }>(`/risks/${id}`, data)
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
        error instanceof Error ? error.message : 'Failed to update risk'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  remove: async (id) => {
    set({ isSaving: true, error: null })
    try {
      await api.delete(`/risks/${id}`)
      set((state) => ({
        items: state.items.filter((item) => item.id !== id),
        selectedItem: state.selectedItem?.id === id ? null : state.selectedItem,
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete risk'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  recalculateScores: async (id) => {
    try {
      const response = await api.post<{ data: Risk }>(`/risks/${id}/recalculate`)
      const updatedItem = response.data.data
      set((state) => ({
        items: state.items.map((item) =>
          item.id === id ? updatedItem : item
        ),
        selectedItem:
          state.selectedItem?.id === id ? updatedItem : state.selectedItem,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to recalculate scores'
      set({ error: message })
      throw error
    }
  },

  selectItem: (item) => set({ selectedItem: item }),

  // Controls
  fetchControls: async (riskId) => {
    try {
      const response = await api.get<{ data: RiskControl[] }>(
        `/risks/${riskId}/controls`
      )
      set({ controls: response.data.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch controls'
      set({ error: message })
    }
  },

  addControl: async (riskId, data) => {
    set({ isSaving: true })
    try {
      await api.post(`/risks/${riskId}/controls`, data)
      get().fetchControls(riskId)
      // Recalculate scores after adding control
      get().recalculateScores(riskId)
      set({ isSaving: false })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to add control'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  updateControl: async (riskId, controlId, data) => {
    set({ isSaving: true })
    try {
      await api.put(`/risks/${riskId}/controls/${controlId}`, data)
      get().fetchControls(riskId)
      set({ isSaving: false })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update control'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  removeControl: async (riskId, controlId) => {
    try {
      await api.delete(`/risks/${riskId}/controls/${controlId}`)
      set((state) => ({
        controls: state.controls.filter((c) => c.id !== controlId),
      }))
      // Recalculate scores after removing control
      get().recalculateScores(riskId)
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to remove control'
      set({ error: message })
      throw error
    }
  },

  // Actions
  fetchActions: async (riskId) => {
    try {
      const response = await api.get<{ data: RiskAction[] }>(
        `/risks/${riskId}/actions`
      )
      set({ actions: response.data.data })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch actions'
      set({ error: message })
    }
  },

  createAction: async (riskId, data) => {
    set({ isSaving: true })
    try {
      const response = await api.post<{ data: RiskAction }>(
        `/risks/${riskId}/actions`,
        data
      )
      const newAction = response.data.data
      set((state) => ({
        actions: [...state.actions, newAction],
        isSaving: false,
      }))
      return newAction
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create action'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  updateAction: async (riskId, actionId, data) => {
    set({ isSaving: true })
    try {
      const response = await api.put<{ data: RiskAction }>(
        `/risks/${riskId}/actions/${actionId}`,
        data
      )
      set((state) => ({
        actions: state.actions.map((a) =>
          a.id === actionId ? response.data.data : a
        ),
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update action'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  deleteAction: async (riskId, actionId) => {
    try {
      await api.delete(`/risks/${riskId}/actions/${actionId}`)
      set((state) => ({
        actions: state.actions.filter((a) => a.id !== actionId),
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete action'
      set({ error: message })
      throw error
    }
  },

  clearError: () => set({ error: null }),
}))
