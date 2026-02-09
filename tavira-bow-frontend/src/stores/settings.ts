import { create } from 'zustand'
import { api } from '@/lib/api'
import type { SettingList, SystemSetting } from '@/types'

interface SettingsState {
  // Data
  lists: SettingList[]
  systemSettings: SystemSetting[]

  // Loading states
  isLoading: boolean
  isSaving: boolean
  error: string | null

  // Actions - Lists
  fetchLists: (type?: string) => Promise<void>
  createList: (data: Partial<SettingList>) => Promise<SettingList>
  updateList: (id: number, data: Partial<SettingList>) => Promise<void>
  deleteList: (id: number) => Promise<void>

  // Actions - System Settings
  fetchSystemSettings: () => Promise<void>
  updateSystemSetting: (key: string, value: string) => Promise<void>

  // Utility
  clearError: () => void
}

export const useSettingsStore = create<SettingsState>((set, get) => ({
  // Initial state
  lists: [],
  systemSettings: [],
  isLoading: false,
  isSaving: false,
  error: null,

  // Lists
  fetchLists: async (type) => {
    set({ isLoading: true, error: null })
    try {
      const url = type ? `/settings/lists?type=${type}` : '/settings/lists'
      const response = await api.get<{ data: SettingList[] }>(url)
      set({ lists: response.data.data, isLoading: false })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch settings lists'
      set({ error: message, isLoading: false })
    }
  },

  createList: async (data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.post<{ data: SettingList }>(
        '/settings/lists',
        data
      )
      const newItem = response.data.data
      set((state) => ({
        lists: [...state.lists, newItem],
        isSaving: false,
      }))
      return newItem
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to create setting'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  updateList: async (id, data) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.put<{ data: SettingList }>(
        `/settings/lists/${id}`,
        data
      )
      set((state) => ({
        lists: state.lists.map((item) =>
          item.id === id ? response.data.data : item
        ),
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update setting'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  deleteList: async (id) => {
    set({ isSaving: true, error: null })
    try {
      await api.delete(`/settings/lists/${id}`)
      set((state) => ({
        lists: state.lists.filter((item) => item.id !== id),
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to delete setting'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  // System Settings
  fetchSystemSettings: async () => {
    set({ isLoading: true, error: null })
    try {
      const response = await api.get<{ data: SystemSetting[] }>(
        '/settings/system'
      )
      set({ systemSettings: response.data.data, isLoading: false })
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to fetch system settings'
      set({ error: message, isLoading: false })
    }
  },

  updateSystemSetting: async (key, value) => {
    set({ isSaving: true, error: null })
    try {
      const response = await api.put<{ data: SystemSetting }>(
        `/settings/system/${key}`,
        { value }
      )
      set((state) => ({
        systemSettings: state.systemSettings.map((item) =>
          item.key === key ? response.data.data : item
        ),
        isSaving: false,
      }))
    } catch (error: unknown) {
      const message =
        error instanceof Error ? error.message : 'Failed to update system setting'
      set({ error: message, isSaving: false })
      throw error
    }
  },

  clearError: () => set({ error: null }),
}))
