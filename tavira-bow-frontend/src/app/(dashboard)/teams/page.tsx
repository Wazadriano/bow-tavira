import { useState, useEffect } from 'react'
import { ColumnDef } from '@tanstack/react-table'
import { Link } from 'react-router-dom'
import { useNavigate } from 'react-router-dom'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { DataTable } from '@/components/shared/data-table'
import { StatusBadge } from '@/components/shared/status-badge'
import { ActionButtons } from '@/components/shared/action-buttons'
import { PageLoading } from '@/components/shared'
import { useTeamsStore } from '@/stores/teams'
import type { Team } from '@/types'
import { Plus, Search, RotateCcw } from 'lucide-react'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const STATUS_OPTIONS = [
  { value: 'all', label: 'All Status' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
]

export default function TeamsPage() {
  const navigate = useNavigate()
  const { items, isLoading, fetchItems, remove } = useTeamsStore()
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')

  useEffect(() => {
    fetchItems()
  }, [fetchItems])

  // Filter data
  const filteredData = items.filter((item) => {
    // Search filter
    if (search) {
      const searchLower = search.toLowerCase()
      const matchesSearch =
        item.name?.toLowerCase().includes(searchLower) ||
        item.description?.toLowerCase().includes(searchLower)
      if (!matchesSearch) return false
    }

    // Status filter
    if (statusFilter !== 'all') {
      const isActive = statusFilter === 'active'
      if (item.is_active !== isActive) return false
    }

    return true
  })

  const resetFilters = () => {
    setSearch('')
    setStatusFilter('all')
  }

  const columns: ColumnDef<Team>[] = [
    {
      id: 'index',
      header: '#',
      cell: ({ row }) => (
        <span className="text-muted-foreground font-mono text-sm">
          {row.original.id}
        </span>
      ),
    },
    {
      accessorKey: 'name',
      header: 'Name',
      cell: ({ row }) => (
        <Link
          to={`/teams/${row.original.id}`}
          className="font-medium text-primary hover:underline"
        >
          {row.getValue('name')}
        </Link>
      ),
    },
    {
      id: 'department',
      header: 'Department',
      cell: () => <span className="text-sm">-</span>,
    },
    {
      accessorKey: 'members',
      header: 'Members',
      cell: ({ row }) => {
        const members = row.original.members || []
        if (members.length === 0) {
          return <span className="text-muted-foreground">No members</span>
        }
        return (
          <div className="flex gap-1 flex-wrap">
            {members.slice(0, 3).map((member) => (
              <Badge key={member.id} variant="secondary" className="text-xs">
                {member.user?.full_name || `User #${member.user_id}`}
                {member.is_lead && (
                  <span className="ml-1 text-primary">★</span>
                )}
              </Badge>
            ))}
            {members.length > 3 && (
              <Badge variant="outline" className="text-xs">
                +{members.length - 3}
              </Badge>
            )}
          </div>
        )
      },
    },
    {
      accessorKey: 'description',
      header: 'Description',
      cell: ({ row }) => (
        <p className="text-sm text-muted-foreground truncate max-w-[200px]">
          {row.getValue('description') || '-'}
        </p>
      ),
    },
    {
      accessorKey: 'is_active',
      header: 'Status',
      cell: ({ row }) => {
        const isActive = row.getValue('is_active') as boolean
        return <StatusBadge status={isActive ? 'active' : 'inactive'} />
      },
    },
    {
      id: 'actions',
      header: '',
      cell: ({ row }) => (
        <ActionButtons
          onView={() => navigate(`/teams/${row.original.id}`)}
          onEdit={() => navigate(`/teams/${row.original.id}/edit`)}
          onDelete={() => {
            if (confirm('Are you sure you want to delete this team?')) {
              remove(row.original.id)
            }
          }}
        />
      ),
    },
  ]

  if (isLoading) {
    return <PageLoading text="Loading teams..." />
  }

  return (
    <>
      <Header
        title="Teams"
        description="Manage your teams and their members"
      />

      <div className="p-6">
        {/* Filters Section */}
        <div className="mb-6 space-y-4">
          {/* Main Filters Row */}
          <div className="flex flex-wrap items-center gap-3">
            {/* Search */}
            <div className="relative flex-1 min-w-[200px] max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search teams..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-9"
              />
            </div>

            {/* Status Filter */}
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[130px]">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                {STATUS_OPTIONS.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            {/* Reset Button */}
            <Button variant="outline" size="sm" onClick={resetFilters}>
              <RotateCcw className="mr-2 h-4 w-4" />
              Reset
            </Button>

            {/* Spacer */}
            <div className="flex-1" />

            {/* Count Badge */}
            <Badge variant="secondary">
              {filteredData.length} / {items.length} teams
            </Badge>

            {/* New Team Button */}
            <Button asChild>
              <Link to="/teams/new">
                <Plus className="mr-2 h-4 w-4" />
                New Team
              </Link>
            </Button>
          </div>
        </div>

        {/* Data Table */}
        <DataTable
          columns={columns}
          data={filteredData}
          searchKey="name"
          searchPlaceholder="Search teams..."
        />
      </div>
    </>
  )
}
