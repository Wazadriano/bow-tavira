import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'

interface ModalState {
  isOpen: boolean
  type: string | null
  data?: unknown
}

interface UIState {
  // Sidebar
  sidebarCollapsed: boolean
  toggleSidebar: () => void
  setSidebarCollapsed: (collapsed: boolean) => void

  // Global loading
  isGlobalLoading: boolean
  setGlobalLoading: (loading: boolean) => void

  // Modals
  modal: ModalState
  openModal: (type: string, data?: unknown) => void
  closeModal: () => void

  // Confirmation dialog
  confirmDialog: {
    isOpen: boolean
    title: string
    description: string
    onConfirm: (() => void) | null
    variant: 'default' | 'destructive'
  }
  showConfirm: (options: {
    title: string
    description: string
    onConfirm: () => void
    variant?: 'default' | 'destructive'
  }) => void
  hideConfirm: () => void

  // Theme
  theme: 'light' | 'dark' | 'system'
  setTheme: (theme: 'light' | 'dark' | 'system') => void

  // Notifications panel
  notificationsPanelOpen: boolean
  toggleNotificationsPanel: () => void

  // Hydration tracking
  _hasHydrated: boolean
  setHasHydrated: (state: boolean) => void
}

export const useUIStore = create<UIState>()(
  persist(
    (set) => ({
      // Hydration tracking
      _hasHydrated: false,
      setHasHydrated: (state: boolean) => set({ _hasHydrated: state }),

      // Sidebar
      sidebarCollapsed: false,
      toggleSidebar: () =>
        set((state) => ({ sidebarCollapsed: !state.sidebarCollapsed })),
      setSidebarCollapsed: (collapsed) => set({ sidebarCollapsed: collapsed }),

      // Global loading
      isGlobalLoading: false,
      setGlobalLoading: (loading) => set({ isGlobalLoading: loading }),

      // Modals
      modal: {
        isOpen: false,
        type: null,
        data: undefined,
      },
      openModal: (type, data) =>
        set({
          modal: {
            isOpen: true,
            type,
            data,
          },
        }),
      closeModal: () =>
        set({
          modal: {
            isOpen: false,
            type: null,
            data: undefined,
          },
        }),

      // Confirmation dialog
      confirmDialog: {
        isOpen: false,
        title: '',
        description: '',
        onConfirm: null,
        variant: 'default',
      },
      showConfirm: ({ title, description, onConfirm, variant = 'default' }) =>
        set({
          confirmDialog: {
            isOpen: true,
            title,
            description,
            onConfirm,
            variant,
          },
        }),
      hideConfirm: () =>
        set({
          confirmDialog: {
            isOpen: false,
            title: '',
            description: '',
            onConfirm: null,
            variant: 'default',
          },
        }),

      // Theme
      theme: 'system',
      setTheme: (theme) => set({ theme }),

      // Notifications panel
      notificationsPanelOpen: false,
      toggleNotificationsPanel: () =>
        set((state) => ({
          notificationsPanelOpen: !state.notificationsPanelOpen,
        })),
    }),
    {
      name: 'ui-storage',
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        sidebarCollapsed: state.sidebarCollapsed,
        theme: state.theme,
      }),
      // Safe merge that handles corrupted localStorage data
      merge: (persistedState, currentState) => {
        if (!persistedState || typeof persistedState !== 'object') {
          return currentState
        }
        return {
          ...currentState,
          sidebarCollapsed: (persistedState as Partial<UIState>).sidebarCollapsed ?? false,
          theme: (persistedState as Partial<UIState>).theme ?? 'system',
        }
      },
      onRehydrateStorage: () => (state, error) => {
        if (error) {
          console.error('UI store rehydration error:', error)
          try {
            localStorage.removeItem('ui-storage')
          } catch {
            // Ignore localStorage errors
          }
        }
        if (state) {
          state.setHasHydrated(true)
        }
      },
    }
  )
)

// Safe selector hook for hydration status
export const useUIHydrated = () => useUIStore((state) => state?._hasHydrated ?? false)
