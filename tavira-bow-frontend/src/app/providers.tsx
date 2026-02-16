import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ThemeProvider } from 'next-themes'
import { useState, Component, type ReactNode, type ErrorInfo } from 'react'
import { StoreHydrationProvider } from '@/components/providers/store-hydration-provider'

// Error Boundary to catch and display the exact error
class ErrorBoundary extends Component<
  { children: ReactNode },
  { hasError: boolean; error: Error | null; errorInfo: ErrorInfo | null }
> {
  constructor(props: { children: ReactNode }) {
    super(props)
    this.state = { hasError: false, error: null, errorInfo: null }
  }

  static getDerivedStateFromError(error: Error) {
    return { hasError: true, error }
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    this.setState({ errorInfo })
    console.error('ErrorBoundary caught:', error, errorInfo)
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{ padding: '20px', fontFamily: 'monospace' }}>
          <h1 style={{ color: 'red' }}>Error Caught by ErrorBoundary</h1>
          <pre style={{ background: '#f0f0f0', padding: '10px', overflow: 'auto' }}>
            {this.state.error?.message}
          </pre>
          <h2>Stack:</h2>
          <pre style={{ background: '#f0f0f0', padding: '10px', overflow: 'auto', fontSize: '12px' }}>
            {this.state.error?.stack}
          </pre>
          <h2>Component Stack:</h2>
          <pre style={{ background: '#f0f0f0', padding: '10px', overflow: 'auto', fontSize: '12px' }}>
            {this.state.errorInfo?.componentStack}
          </pre>
          <button
            onClick={() => {
              // Clear all localStorage and reload
              localStorage.clear()
              window.location.reload()
            }}
            style={{ marginTop: '20px', padding: '10px 20px', cursor: 'pointer' }}
          >
            Clear Storage & Reload
          </button>
        </div>
      )
    }
    return this.props.children
  }
}

export function Providers({ children }: { children: React.ReactNode }) {
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 60 * 1000,
            refetchOnWindowFocus: false,
          },
        },
      })
  )

  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <ThemeProvider
          attribute="class"
          defaultTheme="system"
          enableSystem
          disableTransitionOnChange
        >
          <StoreHydrationProvider>
            {children}
          </StoreHydrationProvider>
        </ThemeProvider>
      </QueryClientProvider>
    </ErrorBoundary>
  )
}
