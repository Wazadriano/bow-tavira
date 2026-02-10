'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { Sidebar } from '@/components/layout/sidebar'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { useAuthHydrated, useAuthIsAuthenticated, useAuthIsLoading, useAuthActions } from '@/stores/auth'

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const router = useRouter()
  const hasHydrated = useAuthHydrated()
  const isAuthenticated = useAuthIsAuthenticated()
  const isLoading = useAuthIsLoading()
  const { fetchUser } = useAuthActions()

  useEffect(() => {
    // Only fetch user after hydration completes to avoid race conditions
    if (hasHydrated) {
      fetchUser()
    }
  }, [fetchUser, hasHydrated])

  useEffect(() => {
    // Only redirect after hydration completes to ensure we have the real auth state
    if (hasHydrated && !isLoading && !isAuthenticated) {
      router.push('/login')
    }
  }, [isAuthenticated, isLoading, router, hasHydrated])

  // Show loading while hydrating or fetching user
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
      <main className="flex-1 overflow-auto">{children}</main>
      <ConfirmDialog />
    </div>
  )
}
