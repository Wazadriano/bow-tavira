import { create } from 'zustand'
import { api } from '@/lib/api'
import type { Team, TeamMember, TeamFormData, PaginatedResponse } from '@/types'

interface TeamsState {
  // Data
  items: Team[]
  selectedItem: Team | null
  members: TeamMember[]

  // Pagination
  currentPage: number
  lastPage: number
  total: number
  perPage: number

  // Loading states
  isLoading: boolean
  isLoadingItem: boolean
  isSaving: boolean
  error: string | null

  // Actions - List
  fetchItems: (page?: number) => Promise<void>

  // Actions - CRUD
  fetchById: (id: number) => Promise<Team>
  create: (data: TeamFormData) => Promise<Team>
  update: (id: number, data: Partial<TeamFormData>) => Promise<Team>
  remove: (id: number) => Promise<void>

  // Actions - Selection
  selectItem: (item: Team | null) => void

  // Actions - Members
  fetchMembers: (teamId: number) => Promise<void>
  addMember: (teamId: number, userId: number, isLead?: boolean) => Promise<void>
  updateMember: (teamId: number, memberId: number, isLead: boolean) => Promise<void>
  removeMember: (teamId: number, memberId: number) => Promise<void>

  // Utility
  clearError: () => void
}

export const useTeamsStore = create<TeamsState>((set, get) => ({
  // Initial state
  items: [],
  selectedItem: null,
  members: [],
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 20,
  isLoading: false,
  isLoadingItem: false,
  isSaving: false,
  error: null,

  // Fetch list
  fetchItems: async (page = 1) => {
    set({ isLoading: true, error: null })
    try {
      const { perPage } = get()
      const params = new URLSearchParams()
      params.append('page', String(page))
      params.append('per_page', String(perPage))

      const response = await api.get<Team[] | PaginatedResponse<Team>>(`/teams?${params}`)
      const data = response.data
      // Backend may return raw array or paginated response
      if (Array.isArray(data)) {
        set({
          items: data,
          currentPage: 1,
          lastPage: 1,
          total: data.length,
          perPage: data.length,
          isLoading: false,
        })
      } else {
        set({
          items: data.data,
          currentPage: data.current_page,
          lastPage: data.last_page,
          total: data.total,
          perPage: data.per_page,
          isLoading: false,
        })
      }
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch teams'
      set({ error: message, isLoading: false })
    }
  },

  // CRUD
  fetchById: async (id) => {
    set({ isLoadingItem: true, error: null })
    try {
      const response = await api.get<Team>(`/teams/${id}`)
      const item = response.data
      set({ selectedItem: item, isLoadingItem: false })
      return item
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch team'
      set({ error: message, isLoadingItem: false })
      throw error
    }
  },

  create: async (data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.post<Team>('/teams', data)
      const newItem = response.data
      set((state) => ({
        items: [newItem, ...state.items],
        isSaving: false,
      }))
      return newItem
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create team'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  update: async (id, data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.put<Team>(`/teams/${id}`, data)
      const updatedItem = response.data
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
        error instanceof Error ? error.message : 'Failed to update team'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  remove: async (id) => {
    set({ isSaving: true, error: null })
    try {
      await api.delete(`/teams/${id}`)
      set((state) => ({
        items: state.items.filter((item) => item.id !== id),
        selectedItem: state.selectedItem?.id === id ? null : state.selectedItem,
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete team'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  selectItem: (item) => set({ selectedItem: item }),

  // Members
  fetchMembers: async (teamId) => {
    try {
      const response = await api.get(
        `/teams/${teamId}/members`
      )
      const raw = response.data
      set({ members: Array.isArray(raw) ? raw : (raw?.data ?? []) })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch members'
      set({ error: message })
    }
  },

  addMember: async (teamId, userId, isLead = false) => {
    set({ isSaving: true })
    try {
      const response = await api.post<TeamMember>(
        `/teams/${teamId}/members`,
        { user_id: userId, is_lead: isLead }
      )
      set((state) => ({
        members: [...state.members, response.data],
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to add member'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  updateMember: async (teamId, memberId, isLead) => {
    set({ isSaving: true })
    try {
      const response = await api.put<TeamMember>(
        `/teams/${teamId}/members/${memberId}`,
        { is_lead: isLead }
      )
      set((state) => ({
        members: state.members.map((m) =>
          m.id === memberId ? response.data : m
        ),
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update member'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  removeMember: async (teamId, memberId) => {
    try {
      await api.delete(`/teams/${teamId}/members/${memberId}`)
      set((state) => ({
        members: state.members.filter((m) => m.id !== memberId),
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to remove member'
      set({ error: message })
      throw error
    }
  },

  clearError: () => set({ error: null }),
}))
