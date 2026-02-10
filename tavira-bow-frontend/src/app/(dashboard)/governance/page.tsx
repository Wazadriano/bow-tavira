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
import { UserAvatarWithName } from '@/components/ui/user-avatar'
import { ActionButtons } from '@/components/shared/action-buttons'
import { get } from '@/lib/api'
import { formatDate } from '@/lib/utils'
import type { GovernanceItem, PaginatedResponse, SettingList } from '@/types'
import { useGovernanceStore } from '@/stores/governance'
import { useUIStore } from '@/stores/ui'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
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
  { value: 'completed', label: 'Completed' },
]

const RAG_OPTIONS = [
  { value: 'all', label: 'All RAG' },
  { value: 'blue', label: 'Blue' },
  { value: 'green', label: 'Green' },
  { value: 'amber', label: 'Amber' },
  { value: 'red', label: 'Red' },
]

const FREQUENCY_OPTIONS = [
  { value: 'all', label: 'All Frequency' },
  { value: 'daily', label: 'Daily' },
  { value: 'weekly', label: 'Weekly' },
  { value: 'monthly', label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
  { value: 'annually', label: 'Annually' },
]

export default function GovernancePage() {
  const router = useRouter()
  const queryClient = useQueryClient()
  const { remove } = useGovernanceStore()
  const { showConfirm } = useUIStore()
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [ragFilter, setRagFilter] = useState('all')
  const [frequencyFilter, setFrequencyFilter] = useState('all')
  const [showAdvancedFilters, setShowAdvancedFilters] = useState(false)
  const [departmentFilter, setDepartmentFilter] = useState('all')

  const { data, isLoading } = useQuery({
    queryKey: ['governance-items'],
    queryFn: () => get<PaginatedResponse<GovernanceItem>>('/governance/items'),
  })

  const { data: departments } = useQuery({
    queryKey: ['settings', 'departments'],
    queryFn: () => get<{ data: SettingList[] }>('/settings/lists?type=department'),
  })

  // Filter data
  const filteredData = (data?.data || []).filter((item) => {
    // Search filter
    if (search) {
      const searchLower = search.toLowerCase()
      const matchesSearch =
        item.ref_no?.toLowerCase().includes(searchLower) ||
        item.activity?.toLowerCase().includes(searchLower) ||
        item.department?.toLowerCase().includes(searchLower) ||
        item.description?.toLowerCase().includes(searchLower)
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

    // Frequency filter
    if (frequencyFilter !== 'all' && item.frequency !== frequencyFilter) {
      return false
    }

    // Department filter (advanced)
    if (departmentFilter !== 'all' && item.department !== departmentFilter) {
      return false
    }

    return true
  })

  const resetFilters = () => {
    setSearch('')
    setStatusFilter('all')
    setRagFilter('all')
    setFrequencyFilter('all')
    setDepartmentFilter('all')
  }

  const columns: ColumnDef<GovernanceItem>[] = [
    {
      accessorKey: 'activity',
      header: 'Item Name',
      cell: ({ row }) => (
        <div className="min-w-[200px]">
          <Link
            href={`/governance/${row.original.id}`}
            className="font-medium text-primary hover:underline"
          >
            {row.getValue('activity') || row.original.ref_no}
          </Link>
          <p className="text-sm text-muted-foreground">
            {row.original.frequency ? (
              <span className="capitalize">{row.original.frequency}</span>
            ) : null}
            {row.original.frequency && row.original.location && ' • '}
            {row.original.location || ''}
          </p>
        </div>
      ),
    },
    {
      accessorKey: 'department',
      header: 'Department',
      cell: ({ row }) => (
        <span className="text-sm">{row.getValue('department') || '-'}</span>
      ),
    },
    {
      accessorKey: 'responsible_party',
      header: 'Entity/Section',
      cell: ({ row }) => {
        const party = row.original.responsible_party
        return <UserAvatarWithName name={party?.full_name} size="sm" />
      },
    },
    {
      accessorKey: 'frequency',
      header: 'Frequency',
      cell: ({ row }) => {
        const freq = row.getValue('frequency') as string
        return (
          <span className="text-sm capitalize">{freq || '-'}</span>
        )
      },
    },
    {
      accessorKey: 'location',
      header: 'Location',
      cell: ({ row }) => (
        <span className="text-sm">{row.original.location || '-'}</span>
      ),
    },
    {
      accessorKey: 'deadline',
      header: 'Next Due',
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
      id: 'actions',
      header: '',
      cell: ({ row }) => (
        <ActionButtons
          onView={() => router.push(`/governance/${row.original.id}`)}
          onEdit={() => router.push(`/governance/${row.original.id}/edit`)}
          onDelete={() => {
            showConfirm({
              title: 'Delete this governance item',
              description: `Are you sure you want to delete "${row.original.activity || row.original.ref_no}"? This action is irreversible.`,
              variant: 'destructive',
              onConfirm: async () => {
                try {
                  await remove(row.original.id)
                  toast.success('Governance item deleted')
                  queryClient.invalidateQueries({ queryKey: ['governance-items'] })
                } catch {
                  toast.error('Error during deletion')
                }
              },
            })
          }}
        />
      ),
    },
  ]

  return (
    <>
      <Header
        title="Governance Items"
        description="Manage governance items and compliance"
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
                placeholder="Search governance items..."
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

            {/* Frequency Filter */}
            <Select value={frequencyFilter} onValueChange={setFrequencyFilter}>
              <SelectTrigger className="w-[150px]">
                <SelectValue placeholder="Frequency" />
              </SelectTrigger>
              <SelectContent>
                {FREQUENCY_OPTIONS.map((option) => (
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

            {/* New Item Button */}
            <Button asChild>
              <Link href="/governance/new">
                <Plus className="mr-2 h-4 w-4" />
                New Item
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
            searchKey="activity"
            searchPlaceholder="Search governance items..."
          />
        )}
      </div>
    </>
  )
}
