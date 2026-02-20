import { create } from 'zustand'
import { api } from '@/lib/api'
import type { User, PaginatedResponse } from '@/types'

export interface UserFormData {
  username: string
  email: string
  full_name: string
  role: 'admin' | 'member'
  department?: string
  is_active: boolean
  password?: string
  password_confirmation?: string
}

export interface UserFilters {
  role?: string
  department?: string
  is_active?: boolean
  search?: string
}

export interface DepartmentPermission {
  id: number
  user_id: number
  department: string
  access_level: 'read' | 'write' | 'admin'
  created_at: string
}

interface UsersState {
  // Data
  users: User[]
  selectedUser: User | null
  permissions: DepartmentPermission[]

  // Pagination
  currentPage: number
  lastPage: number
  total: number
  perPage: number

  // Filters
  filters: UserFilters

  // Loading states
  isLoading: boolean
  isLoadingUser: boolean
  isSaving: boolean
  error: string | null

  // Actions - List
  fetchUsers: (page?: number) => Promise<void>
  setFilters: (filters: Partial<UserFilters>) => void
  resetFilters: () => void

  // Actions - CRUD
  fetchById: (id: number) => Promise<User>
  create: (data: UserFormData) => Promise<User>
  update: (id: number, data: Partial<UserFormData>) => Promise<User>
  remove: (id: number) => Promise<void>
  toggleActive: (id: number) => Promise<void>

  // Actions - Selection
  selectUser: (user: User | null) => void

  // Actions - Permissions
  fetchPermissions: (userId: number) => Promise<void>
  addPermission: (userId: number, department: string, accessLevel: string) => Promise<void>
  removePermission: (userId: number, permissionId: number) => Promise<void>

  // Utility
  clearError: () => void
}

const defaultFilters: UserFilters = {
  role: undefined,
  department: undefined,
  is_active: undefined,
  search: undefined,
}

export const useUsersStore = create<UsersState>((set, get) => ({
  // Initial state
  users: [],
  selectedUser: null,
  permissions: [],
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 20,
  filters: { ...defaultFilters },
  isLoading: false,
  isLoadingUser: false,
  isSaving: false,
  error: null,

  fetchUsers: async (page = 1) => {
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

      const response = await api.get<PaginatedResponse<User>>(`/users?${params}`)

      set({
        users: response.data.data,
        currentPage: response.data.current_page,
        lastPage: response.data.last_page,
        total: response.data.total,
        perPage: response.data.per_page,
        isLoading: false,
      })
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to fetch users'
      set({ error: message, isLoading: false })
    }
  },

  setFilters: (newFilters) => {
    set((state) => ({
      filters: { ...state.filters, ...newFilters },
    }))
    get().fetchUsers(1)
  },

  resetFilters: () => {
    set({ filters: { ...defaultFilters } })
    get().fetchUsers(1)
  },

  fetchById: async (id) => {
    set({ isLoadingUser: true, error: null })
    try {
      const response = await api.get<{ user: User }>(`/users/${id}`)
      const user = response.data.user
      set({ selectedUser: user, isLoadingUser: false })
      return user
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to fetch user'
      set({ error: message, isLoadingUser: false })
      throw error
    }
  },

  create: async (data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.post<{ user: User }>('/users', data)
      const newUser = response.data.user
      set((state) => ({
        users: [newUser, ...state.users],
        isSaving: false,
      }))
      return newUser
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to create user'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  update: async (id, data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.put<{ user: User }>(`/users/${id}`, data)
      const updatedUser = response.data.user
      set((state) => ({
        users: state.users.map((user) => (user.id === id ? updatedUser : user)),
        selectedUser: state.selectedUser?.id === id ? updatedUser : state.selectedUser,
        isSaving: false,
      }))
      return updatedUser
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to update user'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  remove: async (id) => {
    set({ isSaving: true, error: null })
    try {
      await api.delete(`/users/${id}`)
      set((state) => ({
        users: state.users.filter((user) => user.id !== id),
        selectedUser: state.selectedUser?.id === id ? null : state.selectedUser,
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to delete user'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  toggleActive: async (id) => {
    set({ isSaving: true, error: null })
    try {
      const currentUser = get().users.find((u) => u.id === id)
      const response = await api.put<{ user: User }>(`/users/${id}`, {
        is_active: !currentUser?.is_active,
      })
      const updatedUser = response.data.user
      set((state) => ({
        users: state.users.map((user) => (user.id === id ? updatedUser : user)),
        selectedUser: state.selectedUser?.id === id ? updatedUser : state.selectedUser,
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to toggle user status'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  selectUser: (user) => set({ selectedUser: user }),

  fetchPermissions: async (userId) => {
    try {
      const response = await api.get(
        `/users/${userId}/permissions`
      )
      const raw = response.data
      set({ permissions: Array.isArray(raw) ? raw : (raw?.data ?? []) })
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to fetch permissions'
      set({ error: message })
    }
  },

  addPermission: async (userId, department, accessLevel) => {
    try {
      await api.post(`/users/${userId}/permissions`, { department, access_level: accessLevel })
      get().fetchPermissions(userId)
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to add permission'
      set({ error: message })
      throw error
    }
  },

  removePermission: async (userId, permissionId) => {
    try {
      await api.delete(`/users/${userId}/permissions/${permissionId}`)
      set((state) => ({
        permissions: state.permissions.filter((p) => p.id !== permissionId),
      }))
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to remove permission'
      set({ error: message })
      throw error
    }
  },

  clearError: () => set({ error: null }),
}))
