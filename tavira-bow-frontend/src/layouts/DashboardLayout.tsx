import { useEffect } from 'react'
import { useNavigate, Outlet } from 'react-router-dom'
import { Sidebar } from '@/components/layout/sidebar'
import { CommandPalette } from '@/components/layout/command-palette'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { useAuthHydrated, useAuthIsAuthenticated, useAuthIsLoading, useAuthActions } from '@/stores/auth'

export default function DashboardLayout() {
  const navigate = useNavigate()
  const hasHydrated = useAuthHydrated()
  const isAuthenticated = useAuthIsAuthenticated()
  const isLoading = useAuthIsLoading()
  const { fetchUser } = useAuthActions()

  useEffect(() => {
    if (hasHydrated) {
      fetchUser()
    }
  }, [fetchUser, hasHydrated])

  useEffect(() => {
    if (hasHydrated && !isLoading && !isAuthenticated) {
      navigate('/login')
    }
  }, [isAuthenticated, isLoading, navigate, hasHydrated])

  if (!hasHydrated || isLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
      </div>
    )
  }

  if (!isAuthenticated) {
    return null
  }

  return (
    <div className="flex h-screen">
      <Sidebar />
      <main className="flex-1 overflow-auto"><Outlet /></main>
      <CommandPalette />
      <ConfirmDialog />
    </div>
  )
}
