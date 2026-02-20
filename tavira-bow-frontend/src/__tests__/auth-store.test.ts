import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useAuthStore } from '@/stores/auth'
import { act } from '@testing-library/react'

// Mock the API module
vi.mock('@/lib/api', () => ({
  api: {
    post: vi.fn(),
    get: vi.fn(),
    defaults: {
      baseURL: '/api',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      withCredentials: true,
    },
    interceptors: {
      request: { handlers: [{}] },
      response: { handlers: [{}] },
    },
  },
}))

describe('auth store', () => {
  beforeEach(() => {
    // Reset store to initial state
    act(() => {
      useAuthStore.setState({
        user: null,
        token: null,
        isAuthenticated: false,
        isLoading: false,
        error: null,
        _hasHydrated: false,
      })
    })
    localStorage.clear()
  })

  it('has correct initial state', () => {
    const state = useAuthStore.getState()
    expect(state.user).toBeNull()
    expect(state.token).toBeNull()
    expect(state.isAuthenticated).toBe(false)
    expect(state.isLoading).toBe(false)
    expect(state.error).toBeNull()
  })

  it('clearError resets the error', () => {
    act(() => {
      useAuthStore.setState({ error: 'Some error' })
    })
    expect(useAuthStore.getState().error).toBe('Some error')

    act(() => {
      useAuthStore.getState().clearError()
    })
    expect(useAuthStore.getState().error).toBeNull()
  })

  it('setHasHydrated updates hydration flag', () => {
    expect(useAuthStore.getState()._hasHydrated).toBe(false)

    act(() => {
      useAuthStore.getState().setHasHydrated(true)
    })
    expect(useAuthStore.getState()._hasHydrated).toBe(true)
  })

  it('login sets loading state', async () => {
    const { api } = await import('@/lib/api')
    const mockPost = vi.mocked(api.post)

    mockPost.mockResolvedValueOnce({
      data: {
        user: { id: 1, email: 'test@example.com', full_name: 'Test User', role: 'admin' },
        token: 'mock-token',
        refresh_token: 'mock-refresh',
      },
    })

    await act(async () => {
      await useAuthStore.getState().login('test@example.com', 'password')
    })

    const state = useAuthStore.getState()
    expect(state.isAuthenticated).toBe(true)
    expect(state.user?.email).toBe('test@example.com')
    expect(state.token).toBe('mock-token')
    expect(state.isLoading).toBe(false)
    expect(localStorage.getItem('token')).toBe('mock-token')
    expect(localStorage.getItem('refresh_token')).toBe('mock-refresh')
  })

  it('login handles error', async () => {
    const { api } = await import('@/lib/api')
    const mockPost = vi.mocked(api.post)

    mockPost.mockRejectedValueOnce(new Error('Invalid credentials'))

    await act(async () => {
      try {
        await useAuthStore.getState().login('bad@example.com', 'wrong')
      } catch {
        // Expected
      }
    })

    const state = useAuthStore.getState()
    expect(state.isAuthenticated).toBe(false)
    expect(state.error).toBe('Invalid credentials')
    expect(state.isLoading).toBe(false)
  })

  it('logout clears state and localStorage', async () => {
    const { api } = await import('@/lib/api')
    const mockPost = vi.mocked(api.post)

    // Set authenticated state
    act(() => {
      useAuthStore.setState({
        user: { id: 1, email: 'test@example.com', full_name: 'Test', role: 'admin' } as any,
        token: 'mock-token',
        isAuthenticated: true,
      })
    })
    localStorage.setItem('token', 'mock-token')
    localStorage.setItem('refresh_token', 'mock-refresh')

    mockPost.mockResolvedValueOnce({})

    await act(async () => {
      await useAuthStore.getState().logout()
    })

    const state = useAuthStore.getState()
    expect(state.user).toBeNull()
    expect(state.token).toBeNull()
    expect(state.isAuthenticated).toBe(false)
    expect(localStorage.getItem('token')).toBeNull()
    expect(localStorage.getItem('refresh_token')).toBeNull()
  })

  it('fetchUser sets user when token exists', async () => {
    const { api } = await import('@/lib/api')
    const mockGet = vi.mocked(api.get)

    localStorage.setItem('token', 'valid-token')

    mockGet.mockResolvedValueOnce({
      data: { user: { id: 1, email: 'test@example.com', full_name: 'Test User', role: 'admin' } },
    })

    await act(async () => {
      await useAuthStore.getState().fetchUser()
    })

    const state = useAuthStore.getState()
    expect(state.isAuthenticated).toBe(true)
    expect(state.user?.full_name).toBe('Test User')
  })

  it('fetchUser clears state when no token', async () => {
    act(() => {
      useAuthStore.setState({ isAuthenticated: true })
    })

    await act(async () => {
      await useAuthStore.getState().fetchUser()
    })

    expect(useAuthStore.getState().isAuthenticated).toBe(false)
  })

  it('fetchUser clears state on API error', async () => {
    const { api } = await import('@/lib/api')
    const mockGet = vi.mocked(api.get)

    localStorage.setItem('token', 'expired-token')
    mockGet.mockRejectedValueOnce(new Error('Unauthorized'))

    await act(async () => {
      await useAuthStore.getState().fetchUser()
    })

    const state = useAuthStore.getState()
    expect(state.isAuthenticated).toBe(false)
    expect(state.user).toBeNull()
    expect(localStorage.getItem('token')).toBeNull()
  })
})
