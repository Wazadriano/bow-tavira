'use client'

import { useEffect, useState, type ReactNode } from 'react'

interface StoreHydrationProviderProps {
  children: ReactNode
  fallback?: ReactNode
}

/**
 * StoreHydrationProvider ensures client-side hydration is complete
 * before rendering children that depend on Zustand persisted stores.
 *
 * Uses a simple mounted state check to avoid SSR/hydration mismatches.
 */
export function StoreHydrationProvider({
  children,
  fallback = <DefaultLoadingFallback />,
}: StoreHydrationProviderProps) {
  const [isMounted, setIsMounted] = useState(false)

  useEffect(() => {
    // Simple client-side mounting detection
    // This ensures we're on the client and hydration is complete
    setIsMounted(true)
  }, [])

  if (!isMounted) {
    return <>{fallback}</>
  }

  return <>{children}</>
}

function DefaultLoadingFallback() {
  return (
    <div className="flex h-screen items-center justify-center">
      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
    </div>
  )
}
