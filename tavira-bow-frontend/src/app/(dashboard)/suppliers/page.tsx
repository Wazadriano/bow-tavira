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
import { StatusBadge } from '@/components/shared/status-badge'
import { UserAvatarWithName } from '@/components/ui/user-avatar'
import { ActionButtons } from '@/components/shared/action-buttons'
import { get } from '@/lib/api'
import type { Supplier, PaginatedResponse, SageCategory } from '@/types'
import { Plus, Search, RotateCcw, ChevronDown, ChevronUp, Upload, FileText } from 'lucide-react'
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
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'pending', label: 'Pending' },
]

const LOCATION_OPTIONS = [
  { value: 'all', label: 'All Locations' },
  { value: 'local', label: 'Local' },
  { value: 'overseas', label: 'Overseas' },
]

export default function SuppliersPage() {
  const router = useRouter()
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [locationFilter, setLocationFilter] = useState('all')
  const [categoryFilter, setCategoryFilter] = useState('all')
  const [showAdvancedFilters, setShowAdvancedFilters] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['suppliers'],
    queryFn: () => get<PaginatedResponse<Supplier>>('/suppliers'),
  })

  const { data: categories } = useQuery({
    queryKey: ['sage-categories'],
    queryFn: () => get<{ data: SageCategory[] }>('/settings/sage-categories'),
  })

  // Filter data
  const filteredData = (data?.data || []).filter((item) => {
    // Search filter
    if (search) {
      const searchLower = search.toLowerCase()
      const matchesSearch =
        item.ref_no?.toLowerCase().includes(searchLower) ||
        item.name?.toLowerCase().includes(searchLower) ||
        item.sage_category?.name?.toLowerCase().includes(searchLower) ||
        item.location?.toLowerCase().includes(searchLower)
      if (!matchesSearch) return false
    }

    // Status filter
    if (statusFilter !== 'all' && item.status !== statusFilter) {
      return false
    }

    // Location filter
    if (locationFilter !== 'all' && item.location !== locationFilter) {
      return false
    }

    // Category filter
    if (categoryFilter !== 'all' && item.sage_category_id?.toString() !== categoryFilter) {
      return false
    }

    return true
  })

  const resetFilters = () => {
    setSearch('')
    setStatusFilter('all')
    setLocationFilter('all')
    setCategoryFilter('all')
  }

  const columns: ColumnDef<Supplier>[] = [
    {
      accessorKey: 'name',
      header: 'Supplier',
      cell: ({ row }) => (
        <div className="min-w-[180px]">
          <Link
            href={`/suppliers/${row.original.id}`}
            className="font-medium text-primary hover:underline"
          >
            {row.getValue('name')}
          </Link>
          {row.original.notes && (
            <p className="text-sm text-muted-foreground truncate max-w-[200px]">
              {row.original.notes}
            </p>
          )}
        </div>
      ),
    },
    {
      accessorKey: 'location',
      header: 'Location',
      cell: ({ row }) => (
        <span className="text-sm capitalize">{row.getValue('location') || '-'}</span>
      ),
    },
    {
      accessorKey: 'sage_category',
      header: 'Category',
      cell: ({ row }) => {
        const category = row.original.sage_category
        return (
          <span className="text-sm">{category?.name || '-'}</span>
        )
      },
    },
    {
      accessorKey: 'entities',
      header: 'Department',
      cell: ({ row }) => {
        const entities = row.original.entities || []
        if (entities.length === 0) return <span className="text-muted-foreground">-</span>
        return (
          <div className="flex gap-1 flex-wrap">
            {entities.slice(0, 2).map((e) => (
              <Badge key={e.id} variant="outline" className="text-xs">
                {e.entity}
              </Badge>
            ))}
            {entities.length > 2 && (
              <Badge variant="outline" className="text-xs">
                +{entities.length - 2}
              </Badge>
            )}
          </div>
        )
      },
    },
    {
      accessorKey: 'responsible_party',
      header: 'Owner',
      cell: ({ row }) => {
        const owner = row.original.responsible_party
        return <UserAvatarWithName name={owner?.full_name} size="sm" />
      },
    },
    {
      id: 'contracts',
      header: 'Contracts',
      cell: () => (
        <Badge variant="secondary" className="font-mono">
          <FileText className="mr-1 h-3 w-3" />
          0
        </Badge>
      ),
    },
    {
      accessorKey: 'status',
      header: 'Status',
      cell: ({ row }) => {
        const status = row.getValue('status') as string
        return status ? <StatusBadge status={status} /> : <span className="text-muted-foreground">-</span>
      },
    },
    {
      id: 'actions',
      header: '',
      cell: ({ row }) => (
        <ActionButtons
          onView={() => router.push(`/suppliers/${row.original.id}`)}
          onEdit={() => router.push(`/suppliers/${row.original.id}/edit`)}
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
        title="Suppliers"
        description="Manage your supplier relationships"
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
                placeholder="Search suppliers..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-9"
              />
            </div>

            {/* Location Filter */}
            <Select value={locationFilter} onValueChange={setLocationFilter}>
              <SelectTrigger className="w-[140px]">
                <SelectValue placeholder="Location" />
              </SelectTrigger>
              <SelectContent>
                {LOCATION_OPTIONS.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

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

            {/* Category Filter */}
            <Select value={categoryFilter} onValueChange={setCategoryFilter}>
              <SelectTrigger className="w-[150px]">
                <SelectValue placeholder="Category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Categories</SelectItem>
                {categories?.data?.map((cat) => (
                  <SelectItem key={cat.id} value={cat.id.toString()}>
                    {cat.name}
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
              {filteredData.length} / {data?.total || 0} suppliers
            </Badge>

            {/* Import CSV Button */}
            <Button variant="outline">
              <Upload className="mr-2 h-4 w-4" />
              Import CSV
            </Button>

            {/* New Supplier Button */}
            <Button asChild>
              <Link href="/suppliers/new">
                <Plus className="mr-2 h-4 w-4" />
                New Supplier
              </Link>
            </Button>
          </div>

          {/* Advanced Filters Panel */}
          {showAdvancedFilters && (
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-muted/50 rounded-lg border">
              {/* Common Provider Filter */}
              <div>
                <label className="text-sm font-medium mb-2 block">Common Provider</label>
                <Select>
                  <SelectTrigger>
                    <SelectValue placeholder="All" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All</SelectItem>
                    <SelectItem value="true">Yes</SelectItem>
                    <SelectItem value="false">No</SelectItem>
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
            searchKey="name"
            searchPlaceholder="Search suppliers..."
          />
        )}
      </div>
    </>
  )
}
