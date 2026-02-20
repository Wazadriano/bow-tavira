import { Link, useLocation } from 'react-router-dom'
import React, { useState, useEffect, useRef } from 'react'
import {
  Home,
  LayoutDashboard,
  CheckSquare,
  Calendar,
  FileText,
  Truck,
  Receipt,
  Shield,
  AlertTriangle,
  Grid3X3,
  Zap,
  Columns3,
  GanttChart,
  BarChart3,
  ShieldCheck,
  Users,
  UserCog,
  Settings,
  FileSpreadsheet,
  ClipboardList,
  ChevronDown,
  ChevronLeft,
  LogOut,
  Lock,
  Bell,
  Menu,
  X,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { useAuthUser, useAuthActions } from '@/stores/auth'
import { usePermissions } from '@/hooks/use-permissions'

interface NavSection {
  title: string
  items: {
    name: string
    href: string
    icon: React.ComponentType<{ className?: string }>
  }[]
}

const navSections: NavSection[] = [
  {
    title: 'BOOK OF WORK',
    items: [
      { name: 'Work Items', href: '/tasks', icon: CheckSquare },
      { name: 'Kanban', href: '/tasks/kanban', icon: Columns3 },
      { name: 'Gantt', href: '/tasks/gantt', icon: GanttChart },
      { name: 'Workload', href: '/tasks/workload', icon: BarChart3 },
      { name: 'Dashboard', href: '/tasks/dashboard', icon: LayoutDashboard },
      { name: 'Calendar', href: '/tasks/calendar', icon: Calendar },
    ],
  },
  {
    title: 'GOVERNANCE',
    items: [
      { name: 'Regular Items', href: '/governance', icon: FileText },
      { name: 'Dashboard', href: '/governance/dashboard', icon: LayoutDashboard },
      { name: 'Governance Calendar', href: '/governance/calendar', icon: Calendar },
    ],
  },
  {
    title: 'SUPPLIERS',
    items: [
      { name: 'Suppliers', href: '/suppliers', icon: Truck },
      { name: 'Contracts', href: '/suppliers/contracts', icon: FileText },
      { name: 'Invoices', href: '/suppliers/invoices', icon: Receipt },
      { name: 'Dashboard', href: '/suppliers/dashboard', icon: LayoutDashboard },
      { name: 'Contract Calendar', href: '/suppliers/calendar', icon: Calendar },
    ],
  },
  {
    title: 'RISK MANAGEMENT',
    items: [
      { name: 'Dashboard', href: '/risks/dashboard', icon: LayoutDashboard },
      { name: 'Risk Register', href: '/risks', icon: AlertTriangle },
      { name: 'Heat Map', href: '/risks/heatmap', icon: Grid3X3 },
      { name: 'Action Log', href: '/risks/actions', icon: Zap },
      { name: 'Control Library', href: '/risks/controls', icon: ShieldCheck },
      { name: 'Theme Permissions', href: '/risks/themes/permissions', icon: Lock },
    ],
  },
  {
    title: 'MANAGEMENT',
    items: [
      { name: 'Import / Export', href: '/import-export', icon: FileSpreadsheet },
      { name: 'Audit Trail', href: '/audit', icon: ClipboardList },
      { name: 'Login History', href: '/admin/login-history', icon: ClipboardList },
      { name: 'Public Tokens', href: '/admin/public-tokens', icon: Lock },
      { name: 'Notifications', href: '/notifications', icon: Bell },
      { name: 'Teams', href: '/teams', icon: Users },
      { name: 'Users', href: '/users', icon: UserCog },
      { name: 'Settings', href: '/settings', icon: Settings },
      { name: 'Security', href: '/settings/security', icon: Shield },
    ],
  },
]

export function MobileMenuButton({ onClick }: { onClick: () => void }) {
  return (
    <Button
      variant="ghost"
      size="icon"
      className="md:hidden h-8 w-8"
      onClick={onClick}
    >
      <Menu className="h-5 w-5" />
    </Button>
  )
}

const ADMIN_ONLY_HREFS = new Set([
  '/users',
  '/settings',
  '/settings/security',
  '/audit',
  '/admin/login-history',
  '/admin/public-tokens',
  '/risks/themes/permissions',
])

export function Sidebar() {
  const { pathname } = useLocation()
  const user = useAuthUser()
  const { logout } = useAuthActions()
  const { isAdmin } = usePermissions()
  const [collapsed, setCollapsed] = useState(false)
  const [mobileOpen, setMobileOpen] = useState(false)
  const [expandedSections, setExpandedSections] = useState<string[]>(
    navSections.map((s) => s.title)
  )

  // Close mobile menu on route change
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setMobileOpen(false)
  }, [pathname])

  // Close mobile menu on resize to desktop
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth >= 768) {
        setMobileOpen(false)
      }
    }
    window.addEventListener('resize', handleResize)
    return () => window.removeEventListener('resize', handleResize)
  }, [])

  const toggleSection = (title: string) => {
    setExpandedSections((prev) =>
      prev.includes(title)
        ? prev.filter((t) => t !== title)
        : [...prev, title]
    )
  }

  const isItemActive = (href: string) => {
    if (href === '/tasks' || href === '/governance' || href === '/suppliers' || href === '/risks' || href === '/settings') {
      return pathname === href
    }
    return pathname.startsWith(href)
  }

  const isSectionActive = (section: NavSection) => {
    return section.items.some((item) => pathname.startsWith(item.href.split('/').slice(0, 2).join('/')))
  }

  const sidebarContent = (isMobile: boolean) => {
    const isCollapsed = isMobile ? false : collapsed

    return (
      <>
        {/* Logo */}
        <div className="flex h-14 items-center justify-between border-b px-3">
          {(!isCollapsed) && (
            <Link to="/dashboard" className="flex items-center gap-2">
              <div className="flex h-8 w-10 items-center justify-center rounded bg-primary text-xs font-bold text-primary-foreground">
                BOW
              </div>
              <span className="font-semibold">Tavira</span>
            </Link>
          )}
          {isMobile ? (
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setMobileOpen(false)}
              className="h-8 w-8"
            >
              <X className="h-4 w-4" />
            </Button>
          ) : (
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setCollapsed(!collapsed)}
              className={cn('h-8 w-8', isCollapsed && 'mx-auto')}
            >
              <ChevronLeft
                className={cn('h-4 w-4 transition-transform', isCollapsed && 'rotate-180')}
              />
            </Button>
          )}
        </div>

        {/* Navigation */}
        <nav className="flex-1 overflow-y-auto py-2">
          {/* Home link */}
          <div className="mb-1 px-2">
            <Link
              to="/dashboard"
              className={cn(
                'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
                pathname === '/dashboard'
                  ? 'bg-accent text-accent-foreground font-medium'
                  : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                isCollapsed && 'justify-center px-2'
              )}
              title={isCollapsed ? 'Home' : undefined}
            >
              <Home className="h-4 w-4 shrink-0" />
              {!isCollapsed && <span>Home</span>}
            </Link>
          </div>

          {navSections.map((section) => {
            const isExpanded = expandedSections.includes(section.title)
            const sectionActive = isSectionActive(section)

            return (
              <div key={section.title} className="mb-1">
                {/* Section Header */}
                <button
                  onClick={() => !isCollapsed && toggleSection(section.title)}
                  className={cn(
                    'flex w-full items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider transition-colors',
                    sectionActive
                      ? 'text-foreground'
                      : 'text-muted-foreground hover:text-foreground',
                    isCollapsed && 'justify-center'
                  )}
                >
                  {!isCollapsed && <span>{section.title}</span>}
                  {!isCollapsed && (
                    <ChevronDown
                      className={cn(
                        'h-4 w-4 transition-transform',
                        !isExpanded && '-rotate-90'
                      )}
                    />
                  )}
                </button>

                {/* Section Items */}
                {(isExpanded || isCollapsed) && (
                  <div className={cn('space-y-0.5', !isCollapsed && 'pl-2')}>
                    {section.items.filter((item) => !ADMIN_ONLY_HREFS.has(item.href) || isAdmin).map((item) => {
                      const isActive = isItemActive(item.href)
                      return (
                        <Link
                          key={item.href}
                          to={item.href}
                          className={cn(
                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
                            isActive
                              ? 'bg-accent text-accent-foreground font-medium'
                              : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                            isCollapsed && 'justify-center px-2'
                          )}
                          title={isCollapsed ? item.name : undefined}
                        >
                          <item.icon className="h-4 w-4 shrink-0" />
                          {!isCollapsed && <span>{item.name}</span>}
                        </Link>
                      )
                    })}
                  </div>
                )}
              </div>
            )
          })}
        </nav>

        {/* User section */}
        <div className="border-t p-3">
          <div
            className={cn(
              'flex items-center gap-3',
              isCollapsed && 'flex-col gap-2'
            )}
          >
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-xs font-medium">
              {user?.full_name?.charAt(0).toUpperCase() || 'A'}
            </div>
            {!isCollapsed && (
              <div className="flex-1 min-w-0">
                <p className="truncate text-sm font-medium">
                  {user?.full_name || 'Administrator'}
                </p>
              </div>
            )}
            <Button
              variant="ghost"
              size="icon"
              onClick={() => logout()}
              title="Logout"
              className="h-8 w-8 shrink-0"
            >
              <LogOut className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </>
    )
  }

  return (
    <>
      {/* Desktop sidebar */}
      <aside
        className={cn(
          'hidden md:flex h-screen flex-col border-r bg-card transition-all duration-300',
          collapsed ? 'w-16' : 'w-64'
        )}
      >
        {sidebarContent(false)}
      </aside>

      {/* Mobile hamburger button (positioned in header area) */}
      <div className="fixed top-3 left-3 z-50 md:hidden">
        {!mobileOpen && (
          <Button
            variant="outline"
            size="icon"
            onClick={() => setMobileOpen(true)}
            className="h-9 w-9 bg-card shadow-md"
          >
            <Menu className="h-5 w-5" />
          </Button>
        )}
      </div>

      {/* Mobile overlay */}
      {mobileOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50 md:hidden"
          onClick={() => setMobileOpen(false)}
        />
      )}

      {/* Mobile sidebar */}
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-card shadow-xl transition-transform duration-300 md:hidden',
          mobileOpen ? 'translate-x-0' : '-translate-x-full'
        )}
      >
        {sidebarContent(true)}
      </aside>
    </>
  )
}
