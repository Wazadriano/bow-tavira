'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { useState } from 'react'
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
  Moon,
  Sun,
  Lock,
  Bell,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import { useAuthUser, useAuthActions } from '@/stores/auth'
import { useTheme } from 'next-themes'

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
      { name: 'Notifications', href: '/notifications', icon: Bell },
      { name: 'Teams', href: '/teams', icon: Users },
      { name: 'Users', href: '/users', icon: UserCog },
      { name: 'Settings', href: '/settings', icon: Settings },
    ],
  },
]

export function Sidebar() {
  const pathname = usePathname()
  const user = useAuthUser()
  const { logout } = useAuthActions()
  const { theme, setTheme } = useTheme()
  const [collapsed, setCollapsed] = useState(false)
  const [expandedSections, setExpandedSections] = useState<string[]>(
    navSections.map((s) => s.title)
  )

  const toggleSection = (title: string) => {
    setExpandedSections((prev) =>
      prev.includes(title)
        ? prev.filter((t) => t !== title)
        : [...prev, title]
    )
  }

  const isItemActive = (href: string) => {
    if (href === '/tasks' || href === '/governance' || href === '/suppliers' || href === '/risks') {
      return pathname === href
    }
    return pathname.startsWith(href)
  }

  const isSectionActive = (section: NavSection) => {
    return section.items.some((item) => pathname.startsWith(item.href.split('/').slice(0, 2).join('/')))
  }

  return (
    <aside
      className={cn(
        'flex h-screen flex-col border-r bg-card transition-all duration-300',
        collapsed ? 'w-16' : 'w-64'
      )}
    >
      {/* Logo */}
      <div className="flex h-14 items-center justify-between border-b px-3">
        {!collapsed && (
          <Link href="/dashboard" className="flex items-center gap-2">
            <div className="flex h-8 w-10 items-center justify-center rounded bg-primary text-xs font-bold text-primary-foreground">
              BOW
            </div>
            <span className="font-semibold">Tavira</span>
          </Link>
        )}
        <Button
          variant="ghost"
          size="icon"
          onClick={() => setCollapsed(!collapsed)}
          className={cn('h-8 w-8', collapsed && 'mx-auto')}
        >
          <ChevronLeft
            className={cn('h-4 w-4 transition-transform', collapsed && 'rotate-180')}
          />
        </Button>
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto py-2">
        {/* Home link */}
        <div className="mb-1 px-2">
          <Link
            href="/dashboard"
            className={cn(
              'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
              pathname === '/dashboard'
                ? 'bg-accent text-accent-foreground font-medium'
                : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
              collapsed && 'justify-center px-2'
            )}
            title={collapsed ? 'Home' : undefined}
          >
            <Home className="h-4 w-4 shrink-0" />
            {!collapsed && <span>Home</span>}
          </Link>
        </div>

        {navSections.map((section) => {
          const isExpanded = expandedSections.includes(section.title)
          const sectionActive = isSectionActive(section)

          return (
            <div key={section.title} className="mb-1">
              {/* Section Header */}
              <button
                onClick={() => !collapsed && toggleSection(section.title)}
                className={cn(
                  'flex w-full items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider transition-colors',
                  sectionActive
                    ? 'text-foreground'
                    : 'text-muted-foreground hover:text-foreground',
                  collapsed && 'justify-center'
                )}
              >
                {!collapsed && <span>{section.title}</span>}
                {!collapsed && (
                  <ChevronDown
                    className={cn(
                      'h-4 w-4 transition-transform',
                      !isExpanded && '-rotate-90'
                    )}
                  />
                )}
              </button>

              {/* Section Items */}
              {(isExpanded || collapsed) && (
                <div className={cn('space-y-0.5', !collapsed && 'pl-2')}>
                  {section.items.map((item) => {
                    const isActive = isItemActive(item.href)
                    return (
                      <Link
                        key={item.href}
                        href={item.href}
                        className={cn(
                          'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
                          isActive
                            ? 'bg-accent text-accent-foreground font-medium'
                            : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                          collapsed && 'justify-center px-2'
                        )}
                        title={collapsed ? item.name : undefined}
                      >
                        <item.icon className="h-4 w-4 shrink-0" />
                        {!collapsed && <span>{item.name}</span>}
                      </Link>
                    )
                  })}
                </div>
              )}
            </div>
          )
        })}
      </nav>

      {/* Dark Mode Toggle */}
      {!collapsed && (
        <div className="border-t px-4 py-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 text-sm">
              {theme === 'dark' ? (
                <Moon className="h-4 w-4" />
              ) : (
                <Sun className="h-4 w-4" />
              )}
              <span>Dark Mode</span>
            </div>
            <Switch
              checked={theme === 'dark'}
              onCheckedChange={(checked) => setTheme(checked ? 'dark' : 'light')}
            />
          </div>
        </div>
      )}

      {/* User section */}
      <div className="border-t p-3">
        <div
          className={cn(
            'flex items-center gap-3',
            collapsed && 'flex-col gap-2'
          )}
        >
          <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-xs font-medium">
            {user?.full_name?.charAt(0).toUpperCase() || 'A'}
          </div>
          {!collapsed && (
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
    </aside>
  )
}
