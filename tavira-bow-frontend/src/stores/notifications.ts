import { create } from 'zustand'
import { api } from '@/lib/api'
import type { AppNotification } from '@/types'

interface NotificationsState {
  items: AppNotification[]
  unreadCount: number
  currentPage: number
  lastPage: number
  total: number
  isLoading: boolean
  error: string | null

  fetchNotifications: (page?: number) => Promise<void>
  fetchUnreadCount: () => Promise<void>
  markAsRead: (id: string) => Promise<void>
  markAllAsRead: () => Promise<void>
  deleteNotification: (id: string) => Promise<void>
  clearError: () => void
}

export const useNotificationsStore = create<NotificationsState>((set) => ({
  items: [],
  unreadCount: 0,
  currentPage: 1,
  lastPage: 1,
  total: 0,
  isLoading: false,
  error: null,

  fetchNotifications: async (page = 1) => {
    set({ isLoading: true, error: null })
    try {
      const response = await api.get(`/notifications?page=${page}&per_page=25`)
      const data = response.data
      set({
        items: data.notifications,
        unreadCount: data.unread_count,
        currentPage: data.meta.current_page,
        lastPage: data.meta.last_page,
        total: data.meta.total,
        isLoading: false,
      })
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to fetch notifications'
      set({ error: message, isLoading: false })
    }
  },

  fetchUnreadCount: async () => {
    try {
      const response = await api.get('/notifications/unread-count')
      set({ unreadCount: response.data.count })
    } catch {
      // Silent fail for badge count
    }
  },

  markAsRead: async (id) => {
    try {
      await api.put(`/notifications/${id}/read`)
      set((state) => ({
        items: state.items.map((n) =>
          n.id === id ? { ...n, read_at: new Date().toISOString() } : n
        ),
        unreadCount: Math.max(0, state.unreadCount - 1),
      }))
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to mark notification as read'
      set({ error: message })
    }
  },

  markAllAsRead: async () => {
    try {
      await api.put('/notifications/read-all')
      set((state) => ({
        items: state.items.map((n) => ({ ...n, read_at: n.read_at || new Date().toISOString() })),
        unreadCount: 0,
      }))
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to mark all as read'
      set({ error: message })
    }
  },

  deleteNotification: async (id) => {
    try {
      await api.delete(`/notifications/${id}`)
      set((state) => ({
        items: state.items.filter((n) => n.id !== id),
        total: state.total - 1,
        unreadCount: state.items.find((n) => n.id === id && !n.read_at)
          ? state.unreadCount - 1
          : state.unreadCount,
      }))
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to delete notification'
      set({ error: message })
    }
  },

  clearError: () => set({ error: null }),
}))
