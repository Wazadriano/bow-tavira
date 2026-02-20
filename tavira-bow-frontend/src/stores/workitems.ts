import { create } from 'zustand'
import { api } from '@/lib/api'
import type {
  WorkItem,
  WorkItemFormData,
  WorkItemFilters,
  TaskMilestone,
  TaskAssignment,
  PaginatedResponse,
} from '@/types'

interface WorkItemsState {
  // Data
  items: WorkItem[]
  selectedItem: WorkItem | null
  milestones: TaskMilestone[]
  assignments: TaskAssignment[]

  // Pagination
  currentPage: number
  lastPage: number
  total: number
  perPage: number

  // Filters
  filters: WorkItemFilters

  // Loading states
  isLoading: boolean
  isLoadingItem: boolean
  isSaving: boolean
  error: string | null

  // Actions - List
  fetchItems: (page?: number) => Promise<void>
  setFilters: (filters: Partial<WorkItemFilters>) => void
  resetFilters: () => void

  // Actions - CRUD
  fetchById: (id: number) => Promise<WorkItem>
  create: (data: WorkItemFormData) => Promise<WorkItem>
  update: (id: number, data: Partial<WorkItemFormData>) => Promise<WorkItem>
  updateStatus: (id: number, status: string) => Promise<void>
  remove: (id: number) => Promise<void>

  // Actions - Selection
  selectItem: (item: WorkItem | null) => void

  // Actions - Milestones
  fetchMilestones: (workItemId: number) => Promise<void>
  createMilestone: (
    workItemId: number,
    data: { title: string; description?: string; due_date?: string }
  ) => Promise<TaskMilestone>
  updateMilestone: (
    milestoneId: number,
    data: Partial<TaskMilestone>
  ) => Promise<void>
  deleteMilestone: (milestoneId: number) => Promise<void>

  // Actions - Assignments
  fetchAssignments: (workItemId: number) => Promise<void>
  assignUser: (workItemId: number, userId: number, type: 'owner' | 'member') => Promise<void>
  unassignUser: (workItemId: number, userId: number) => Promise<void>
  acknowledgeAssignment: (assignmentId: number) => Promise<void>

  // Actions - Files
  uploadFile: (workItemId: number, file: File) => Promise<void>
  deleteFile: (workItemId: number, fileId: number) => Promise<void>

  // Actions - Dependencies
  addDependency: (workItemId: number, dependencyId: number) => Promise<void>
  removeDependency: (workItemId: number, dependencyId: number) => Promise<void>

  // Utility
  clearError: () => void
}

const defaultFilters: WorkItemFilters = {
  department: undefined,
  status: undefined,
  rag_status: undefined,
  priority_item: undefined,
  search: undefined,
  responsible_party_id: undefined,
}

export const useWorkItemsStore = create<WorkItemsState>((set, get) => ({
  // Initial state
  items: [],
  selectedItem: null,
  milestones: [],
  assignments: [],
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 20,
  filters: { ...defaultFilters },
  isLoading: false,
  isLoadingItem: false,
  isSaving: false,
  error: null,

  // Fetch list with pagination and filters
  fetchItems: async (page = 1) => {
    set({ isLoading: true, error: null })
    try {
      const { filters, perPage } = get()
      const params = new URLSearchParams()
      params.append('page', String(page))
      params.append('per_page', String(perPage))

      // Add filters
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== '') {
          params.append(key, String(value))
        }
      })

      const response = await api.get<PaginatedResponse<WorkItem>>(
        `/workitems?${params}`
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
        error instanceof Error ? error.message : 'Failed to fetch work items'
      set({ error: message, isLoading: false })
    }
  },

  setFilters: (newFilters) => {
    set((state) => ({
      filters: { ...state.filters, ...newFilters },
    }))
    // Refetch with new filters
    get().fetchItems(1)
  },

  resetFilters: () => {
    set({ filters: { ...defaultFilters } })
    get().fetchItems(1)
  },

  // Fetch single item
  fetchById: async (id) => {
    set({ isLoadingItem: true, error: null, selectedItem: null })
    try {
      const response = await api.get<{ work_item: WorkItem }>(`/workitems/${id}`)
      const data = response.data as { work_item?: WorkItem } & Record<string, unknown>
      const item = data.work_item ?? (data as unknown as WorkItem)
      if (!item || typeof item !== 'object' || !('id' in item)) {
        set({ error: 'Invalid server response', isLoadingItem: false })
        throw new Error('Invalid work item response')
      }
      set({ selectedItem: item, isLoadingItem: false })
      return item
    } catch (error: unknown) {
      const msg = error instanceof Error ? error.message : 'Failed to load work item'
      const is404 = typeof error === 'object' && error !== null && (error as { response?: { status?: number } }).response?.status === 404
      set({
        error: is404 ? 'This work item was not found or has been deleted.' : msg,
        isLoadingItem: false,
        selectedItem: null,
      })
      throw error
    }
  },

  // Create
  create: async (data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.post<{ work_item: WorkItem }>('/workitems', data)
      const newItem = response.data.work_item
      set((state) => ({
        items: [newItem, ...state.items],
        isSaving: false,
      }))
      return newItem
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create work item'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  // Update
  update: async (id, data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.put<{ work_item: WorkItem }>(
        `/workitems/${id}`,
        data
      )
      const updatedItem = response.data.work_item
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
        error instanceof Error ? error.message : 'Failed to update work item'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  // Update status only
  updateStatus: async (id, status) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.put<{ work_item: WorkItem }>(
        `/workitems/${id}`,
        { current_status: status }
      )
      const updatedItem = response.data.work_item
      set((state) => ({
        items: state.items.map((item) =>
          item.id === id ? updatedItem : item
        ),
        selectedItem:
          state.selectedItem?.id === id ? updatedItem : state.selectedItem,
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update status'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  // Delete
  remove: async (id) => {
    set({ isSaving: true, error: null })
    try {
      await api.delete(`/workitems/${id}`)
      set((state) => ({
        items: state.items.filter((item) => item.id !== id),
        selectedItem: state.selectedItem?.id === id ? null : state.selectedItem,
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete work item'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  // Selection
  selectItem: (item) => set({ selectedItem: item }),

  // Milestones
  fetchMilestones: async (workItemId) => {
    set({ milestones: [] })
    try {
      const response = await api.get<TaskMilestone[]>(
        `/workitems/${workItemId}/milestones`
      )
      const list = Array.isArray(response.data) ? response.data : []
      set({ milestones: list })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch milestones'
      set({ error: message })
    }
  },

  createMilestone: async (workItemId, data) => {
    try {
      const response = await api.post<TaskMilestone>(
        `/milestones`,
        { ...data, work_item_id: workItemId }
      )
      const newMilestone = response.data
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
      const response = await api.put<TaskMilestone>(
        `/milestones/${milestoneId}`,
        data
      )
      set((state) => ({
        milestones: state.milestones.map((m) =>
          m.id === milestoneId ? response.data : m
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
      await api.delete(`/milestones/${milestoneId}`)
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

  // Assignments
  fetchAssignments: async (workItemId) => {
    try {
      const response = await api.get(
        `/workitems/${workItemId}/assignments`
      )
      const raw = response.data
      set({ assignments: Array.isArray(raw) ? raw : (raw?.data ?? []) })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch assignments'
      set({ error: message })
    }
  },

  assignUser: async (workItemId, userId, type) => {
    try {
      await api.post(`/workitems/${workItemId}/assign/${userId}`, { type })
      // Refresh work item so selectedItem.assignments is updated
      await get().fetchById(workItemId)
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to assign user'
      set({ error: message })
      throw error
    }
  },

  unassignUser: async (workItemId, userId) => {
    try {
      await api.delete(`/workitems/${workItemId}/assign/${userId}`)
      // Refresh work item so selectedItem.assignments is updated
      await get().fetchById(workItemId)
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to unassign user'
      set({ error: message })
      throw error
    }
  },

  acknowledgeAssignment: async (assignmentId) => {
    try {
      const response = await api.put(`/task-assignments/${assignmentId}/acknowledge`)
      const updated = response.data.data
      set((state) => ({
        assignments: state.assignments.map((a) =>
          a.id === assignmentId ? { ...a, acknowledged_at: updated.acknowledged_at } : a
        ),
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to acknowledge assignment'
      set({ error: message })
      throw error
    }
  },

  // Files
  uploadFile: async (workItemId, file) => {
    try {
      const formData = new FormData()
      formData.append('file', file)
      await api.post(`/workitems/${workItemId}/files`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      // Refresh the item to get updated files list
      get().fetchById(workItemId)
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to upload file'
      set({ error: message })
      throw error
    }
  },

  deleteFile: async (workItemId, fileId) => {
    try {
      await api.delete(`/workitems/${workItemId}/files/${fileId}`)
      // Refresh the item
      get().fetchById(workItemId)
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete file'
      set({ error: message })
      throw error
    }
  },

  // Dependencies
  addDependency: async (workItemId, dependencyId) => {
    try {
      await api.post(`/workitems/${workItemId}/dependencies/${dependencyId}`)
      // Refresh the item to get updated dependencies
      get().fetchById(workItemId)
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to add dependency'
      set({ error: message })
      throw error
    }
  },

  removeDependency: async (workItemId, dependencyId) => {
    try {
      await api.delete(`/workitems/${workItemId}/dependencies/${dependencyId}`)
      // Refresh the item to get updated dependencies
      get().fetchById(workItemId)
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to remove dependency'
      set({ error: message })
      throw error
    }
  },

  // Utility
  clearError: () => set({ error: null }),
}))
