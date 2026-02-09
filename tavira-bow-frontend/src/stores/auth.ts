import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'
import { api } from '@/lib/api'
import type { User } from '@/types'

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
  _hasHydrated: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  fetchUser: () => Promise<void>
  clearError: () => void
  setHasHydrated: (state: boolean) => void
}

// Default state for safe access
const defaultState = {
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,
  _hasHydrated: false,
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,
      _hasHydrated: false,

      setHasHydrated: (state: boolean) => {
        set({ _hasHydrated: state })
      },

      login: async (email: string, password: string) => {
        set({ isLoading: true, error: null })
        try {
          const response = await api.post<{
            user: User
            token: string
            refresh_token: string
          }>('/auth/login', { email, password })

          const { user, token, refresh_token } = response.data

          localStorage.setItem('token', token)
          localStorage.setItem('refresh_token', refresh_token)

          set({
            user,
            token,
            isAuthenticated: true,
            isLoading: false,
          })
        } catch (error: unknown) {
          const message =
            error instanceof Error ? error.message : 'Login failed'
          set({
            error: message,
            isLoading: false,
            isAuthenticated: false,
          })
          throw error
        }
      },

      logout: async () => {
        set({ isLoading: true })
        try {
          await api.post('/auth/logout')
        } catch {
          // Continue with logout even if API fails
        } finally {
          localStorage.removeItem('token')
          localStorage.removeItem('refresh_token')
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
            error: null,
          })
        }
      },

      fetchUser: async () => {
        const token = localStorage.getItem('token')
        if (!token) {
          set({ isAuthenticated: false, user: null })
          return
        }

        set({ isLoading: true })
        try {
          const response = await api.get<{ user: User }>('/auth/me')
          set({
            user: response.data.user,
            token,
            isAuthenticated: true,
            isLoading: false,
          })
        } catch {
          localStorage.removeItem('token')
          localStorage.removeItem('refresh_token')
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
          })
        }
      },

      clearError: () => set({ error: null }),
    }),
    {
      name: 'auth-storage',
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
      // Safe merge that handles corrupted or old localStorage data
      merge: (persistedState, currentState) => {
        // If persisted state is invalid, return current state
        if (!persistedState || typeof persistedState !== 'object') {
          return currentState
        }
        // Safely merge, ensuring all required properties exist
        return {
          ...currentState,
          user: (persistedState as Partial<AuthState>).user ?? null,
          token: (persistedState as Partial<AuthState>).token ?? null,
          isAuthenticated: (persistedState as Partial<AuthState>).isAuthenticated ?? false,
        }
      },
      onRehydrateStorage: () => (state, error) => {
        // Handle rehydration errors gracefully
        if (error) {
          console.error('Auth store rehydration error:', error)
          // Clear corrupted storage
          try {
            localStorage.removeItem('auth-storage')
          } catch {
            // Ignore localStorage errors
          }
        }
        // Always set hydrated, even on error
        if (state) {
          state.setHasHydrated(true)
        }
      },
    }
  )
)

// Safe selector hooks with fallback values
export const useAuthHydrated = () => useAuthStore((state) => state?._hasHydrated ?? false)

// Export safe selectors for defensive access
export const useAuthUser = () => useAuthStore((state) => state?.user ?? null)
export const useAuthIsAuthenticated = () => useAuthStore((state) => state?.isAuthenticated ?? false)
export const useAuthIsLoading = () => useAuthStore((state) => state?.isLoading ?? false)
export const useAuthError = () => useAuthStore((state) => state?.error ?? null)

// Safe action selectors
export const useAuthActions = () => useAuthStore((state) => ({
  login: state?.login ?? (async () => {}),
  logout: state?.logout ?? (async () => {}),
  fetchUser: state?.fetchUser ?? (async () => {}),
  clearError: state?.clearError ?? (() => {}),
}))
