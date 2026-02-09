'use client'

import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { ColumnDef } from '@tanstack/react-table'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { DataTable } from '@/components/shared/data-table'
import { RAGBadge } from '@/components/shared/rag-badge'
import { StatusBadge } from '@/components/shared/status-badge'
import { PriorityBadge } from '@/components/shared/priority-badge'
import { UserAvatarWithName } from '@/components/ui/user-avatar'
import { ActionButtons } from '@/components/shared/action-buttons'
import { get } from '@/lib/api'
import { formatDate } from '@/lib/utils'
import type { WorkItem, PaginatedResponse, SettingList } from '@/types'
import { Plus, Search, RotateCcw, ChevronDown, ChevronUp } from 'lucide-react'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'

const STATUS_OPTIONS = [
  { value: 'all', label: 'All Status' },
  { value: 'not_started', label: 'Not Started' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'on_hold', label: 'On Hold' },
  { value: 'completed', label: 'Completed' },
]

const RAG_OPTIONS = [
  { value: 'all', label: 'All RAG' },
  { value: 'blue', label: 'Blue' },
  { value: 'green', label: 'Green' },
  { value: 'amber', label: 'Amber' },
  { value: 'red', label: 'Red' },
]

const PRIORITY_OPTIONS = [
  { value: 'all', label: 'All Priority' },
  { value: 'true', label: 'High Priority' },
  { value: 'false', label: 'Normal' },
]

export default function TasksPage() {
  const router = useRouter()
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [ragFilter, setRagFilter] = useState('all')
  const [priorityFilter, setPriorityFilter] = useState('all')
  const [showAdvancedFilters, setShowAdvancedFilters] = useState(false)
  const [departmentFilter, setDepartmentFilter] = useState('all')
  const [activityFilter, setActivityFilter] = useState('all')

  const { data, isLoading } = useQuery({
    queryKey: ['workitems'],
    queryFn: () => get<PaginatedResponse<WorkItem>>('/workitems'),
  })

  const { data: departments } = useQuery({
    queryKey: ['settings', 'departments'],
    queryFn: () => get<{ data: SettingList[] }>('/settings/lists?type=department'),
  })

  const { data: activities } = useQuery({
    queryKey: ['settings', 'activities'],
    queryFn: () => get<{ data: SettingList[] }>('/settings/lists?type=activity'),
  })

  // Filter data
  const filteredData = (data?.data || []).filter((item) => {
    // Search filter
    if (search) {
      const searchLower = search.toLowerCase()
      const matchesSearch =
        item.ref_no?.toLowerCase().includes(searchLower) ||
        item.description?.toLowerCase().includes(searchLower) ||
        item.department?.toLowerCase().includes(searchLower) ||
        item.activity?.toLowerCase().includes(searchLower)
      if (!matchesSearch) return false
    }

    // Status filter
    if (statusFilter !== 'all' && item.current_status !== statusFilter) {
      return false
    }

    // RAG filter
    if (ragFilter !== 'all' && item.rag_status !== ragFilter) {
      return false
    }

    // Priority filter
    if (priorityFilter !== 'all') {
      const isPriority = priorityFilter === 'true'
      if (item.priority_item !== isPriority) return false
    }

    // Department filter (advanced)
    if (departmentFilter !== 'all' && item.department !== departmentFilter) {
      return false
    }

    // Activity filter (advanced)
    if (activityFilter !== 'all' && item.activity !== activityFilter) {
      return false
    }

    return true
  })

  const resetFilters = () => {
    setSearch('')
    setStatusFilter('all')
    setRagFilter('all')
    setPriorityFilter('all')
    setDepartmentFilter('all')
    setActivityFilter('all')
  }

  const columns: ColumnDef<WorkItem>[] = [
    {
      id: 'index',
      header: '#',
      cell: ({ row }) => (
        <span className="text-muted-foreground font-mono text-sm">
          {row.index + 1}
        </span>
      ),
    },
    {
      accessorKey: 'ref_no',
      header: 'Task',
      cell: ({ row }) => (
        <div className="min-w-[200px]">
          <Link
            href={`/tasks/${row.original.id}`}
            className="font-medium text-primary hover:underline"
          >
            {row.getValue('ref_no')}
          </Link>
          <p className="text-sm text-muted-foreground truncate max-w-[250px]">
            {row.original.description}
          </p>
        </div>
      ),
    },
    {
      accessorKey: 'department',
      header: 'Dept',
      cell: ({ row }) => (
        <span className="text-sm">{row.getValue('department') || '-'}</span>
      ),
    },
    {
      accessorKey: 'activity',
      header: 'Activity',
      cell: ({ row }) => (
        <span className="text-sm">{row.getValue('activity') || '-'}</span>
      ),
    },
    {
      accessorKey: 'responsible_party',
      header: 'Owner',
      cell: ({ row }) => {
        const party = row.original.responsible_party
        return <UserAvatarWithName name={party?.full_name} size="sm" />
      },
    },
    {
      accessorKey: 'priority_item',
      header: 'Pri',
      cell: ({ row }) => <PriorityBadge priority={row.getValue('priority_item')} />,
    },
    {
      accessorKey: 'deadline',
      header: 'Due',
      cell: ({ row }) => (
        <span className="text-sm whitespace-nowrap">
          {formatDate(row.getValue('deadline'))}
        </span>
      ),
    },
    {
      accessorKey: 'current_status',
      header: 'Status',
      cell: ({ row }) => {
        const status = row.getValue('current_status') as string
        return status ? <StatusBadge status={status} /> : <span className="text-muted-foreground">-</span>
      },
    },
    {
      accessorKey: 'rag_status',
      header: 'RAG',
      cell: ({ row }) => <RAGBadge status={row.getValue('rag_status')} />,
    },
    {
      id: 'actions',
      header: '',
      cell: ({ row }) => (
        <ActionButtons
          onView={() => router.push(`/tasks/${row.original.id}`)}
          onEdit={() => router.push(`/tasks/${row.original.id}/edit`)}
          onDelete={() => {
            // TODO: Implement delete confirmation
            console.log('Delete', row.original.id)
          }}
        />
      ),
    },
  ]

  return (
    <>
      <Header
        title="Work Items"
        description="Manage your work items and track progress"
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
                placeholder="Search tasks..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-9"
              />
            </div>

            {/* Status Filter */}
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[140px]">
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

            {/* RAG Filter */}
            <Select value={ragFilter} onValueChange={setRagFilter}>
              <SelectTrigger className="w-[120px]">
                <SelectValue placeholder="RAG" />
              </SelectTrigger>
              <SelectContent>
                {RAG_OPTIONS.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            {/* Priority Filter */}
            <Select value={priorityFilter} onValueChange={setPriorityFilter}>
              <SelectTrigger className="w-[140px]">
                <SelectValue placeholder="Priority" />
              </SelectTrigger>
              <SelectContent>
                {PRIORITY_OPTIONS.map((option) => (
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

            {/* Advanced Filters Toggle */}
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setShowAdvancedFilters(!showAdvancedFilters)}
              className="text-muted-foreground"
            >
              {showAdvancedFilters ? (
                <>
                  <ChevronUp className="mr-2 h-4 w-4" />
                  Hide Advanced Filters
                </>
              ) : (
                <>
                  <ChevronDown className="mr-2 h-4 w-4" />
                  Show Advanced Filters
                </>
              )}
            </Button>

            {/* Spacer */}
            <div className="flex-1" />

            {/* Count Badge */}
            <Badge variant="secondary">
              {filteredData.length} / {data?.total || 0} items
            </Badge>

            {/* New Task Button */}
            <Button asChild>
              <Link href="/tasks/new">
                <Plus className="mr-2 h-4 w-4" />
                New Task
              </Link>
            </Button>
          </div>

          {/* Advanced Filters Panel */}
          {showAdvancedFilters && (
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-muted/50 rounded-lg border">
              {/* Department Filter */}
              <div>
                <label className="text-sm font-medium mb-2 block">Department</label>
                <Select value={departmentFilter} onValueChange={setDepartmentFilter}>
                  <SelectTrigger>
                    <SelectValue placeholder="All Departments" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Departments</SelectItem>
                    {departments?.data?.map((dept) => (
                      <SelectItem key={dept.id} value={dept.value}>
                        {dept.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* Activity Filter */}
              <div>
                <label className="text-sm font-medium mb-2 block">Activity</label>
                <Select value={activityFilter} onValueChange={setActivityFilter}>
                  <SelectTrigger>
                    <SelectValue placeholder="All Activities" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Activities</SelectItem>
                    {activities?.data?.map((act) => (
                      <SelectItem key={act.id} value={act.value}>
                        {act.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
          )}
        </div>

        {/* Data Table */}
        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
          </div>
        ) : (
          <DataTable
            columns={columns}
            data={filteredData}
            searchKey="description"
            searchPlaceholder="Search tasks..."
          />
        )}
      </div>
    </>
  )
}
