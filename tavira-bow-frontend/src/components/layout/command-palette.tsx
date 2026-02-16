import { useEffect, useState, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  CheckSquare,
  FileText,
  Truck,
  AlertTriangle,
  Users,
  Settings,
  LayoutDashboard,
  Plus,
  Search,
  Bell,
  ClipboardList,
} from 'lucide-react'
import {
  Dialog,
  DialogContent,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { cn } from '@/lib/utils'

interface CommandItem {
  id: string
  label: string
  icon: React.ComponentType<{ className?: string }>
  href: string
  section: string
}

const commands: CommandItem[] = [
  // Navigation
  { id: 'home', label: 'Go to Dashboard', icon: LayoutDashboard, href: '/dashboard', section: 'Navigation' },
  { id: 'tasks', label: 'Go to Work Items', icon: CheckSquare, href: '/tasks', section: 'Navigation' },
  { id: 'kanban', label: 'Go to Kanban', icon: CheckSquare, href: '/tasks/kanban', section: 'Navigation' },
  { id: 'gantt', label: 'Go to Gantt', icon: CheckSquare, href: '/tasks/gantt', section: 'Navigation' },
  { id: 'governance', label: 'Go to Governance', icon: FileText, href: '/governance', section: 'Navigation' },
  { id: 'suppliers', label: 'Go to Suppliers', icon: Truck, href: '/suppliers', section: 'Navigation' },
  { id: 'risks', label: 'Go to Risk Register', icon: AlertTriangle, href: '/risks', section: 'Navigation' },
  { id: 'risks-dash', label: 'Go to Risk Dashboard', icon: LayoutDashboard, href: '/risks/dashboard', section: 'Navigation' },
  { id: 'heatmap', label: 'Go to Heat Map', icon: AlertTriangle, href: '/risks/heatmap', section: 'Navigation' },
  { id: 'users', label: 'Go to Users', icon: Users, href: '/users', section: 'Navigation' },
  { id: 'teams', label: 'Go to Teams', icon: Users, href: '/teams', section: 'Navigation' },
  { id: 'settings', label: 'Go to Settings', icon: Settings, href: '/settings', section: 'Navigation' },
  { id: 'notifications', label: 'Go to Notifications', icon: Bell, href: '/notifications', section: 'Navigation' },
  { id: 'audit', label: 'Go to Audit Trail', icon: ClipboardList, href: '/audit', section: 'Navigation' },
  // Create
  { id: 'new-task', label: 'Create Work Item', icon: Plus, href: '/tasks/new', section: 'Create' },
  { id: 'new-gov', label: 'Create Governance Item', icon: Plus, href: '/governance/new', section: 'Create' },
  { id: 'new-supplier', label: 'Create Supplier', icon: Plus, href: '/suppliers/new', section: 'Create' },
  { id: 'new-risk', label: 'Create Risk', icon: Plus, href: '/risks/new', section: 'Create' },
  { id: 'new-team', label: 'Create Team', icon: Plus, href: '/teams/new', section: 'Create' },
]

export function CommandPalette() {
  const [open, setOpen] = useState(false)
  const [search, setSearch] = useState('')
  const [selectedIndex, setSelectedIndex] = useState(0)
  const navigate = useNavigate()

  const filtered = commands.filter((cmd) =>
    cmd.label.toLowerCase().includes(search.toLowerCase())
  )

  const sections = Array.from(new Set(filtered.map((c) => c.section)))

  useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
        e.preventDefault()
        setOpen((prev) => !prev)
        setSearch('')
        setSelectedIndex(0)
      }
    }
    document.addEventListener('keydown', down)
    return () => document.removeEventListener('keydown', down)
  }, [])

  const handleSelect = useCallback((href: string) => {
    setOpen(false)
    navigate(href)
  }, [navigate])

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'ArrowDown') {
      e.preventDefault()
      setSelectedIndex((prev) => Math.min(prev + 1, filtered.length - 1))
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      setSelectedIndex((prev) => Math.max(prev - 1, 0))
    } else if (e.key === 'Enter' && filtered[selectedIndex]) {
      handleSelect(filtered[selectedIndex].href)
    }
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent className="p-0 gap-0 max-w-lg">
        <div className="flex items-center border-b px-3">
          <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
          <Input
            placeholder="Type a command or search..."
            value={search}
            onChange={(e) => {
              setSearch(e.target.value)
              setSelectedIndex(0)
            }}
            onKeyDown={handleKeyDown}
            className="border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
            autoFocus
          />
          <kbd className="pointer-events-none inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100">
            ESC
          </kbd>
        </div>

        <div className="max-h-[300px] overflow-y-auto p-2">
          {filtered.length === 0 ? (
            <div className="py-6 text-center text-sm text-muted-foreground">
              No results found
            </div>
          ) : (
            sections.map((section) => (
              <div key={section}>
                <div className="px-2 py-1.5 text-xs font-semibold text-muted-foreground">
                  {section}
                </div>
                {filtered
                  .filter((c) => c.section === section)
                  .map((cmd) => {
                    const globalIndex = filtered.indexOf(cmd)
                    return (
                      <button
                        key={cmd.id}
                        className={cn(
                          'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors',
                          globalIndex === selectedIndex
                            ? 'bg-accent text-accent-foreground'
                            : 'hover:bg-accent/50'
                        )}
                        onClick={() => handleSelect(cmd.href)}
                        onMouseEnter={() => setSelectedIndex(globalIndex)}
                      >
                        <cmd.icon className="h-4 w-4 shrink-0" />
                        {cmd.label}
                      </button>
                    )
                  })}
              </div>
            ))
          )}
        </div>

        <div className="border-t p-2 text-xs text-muted-foreground flex items-center gap-4">
          <span>Navigate with arrow keys</span>
          <span>Enter to select</span>
          <span>Esc to close</span>
        </div>
      </DialogContent>
    </Dialog>
  )
}
