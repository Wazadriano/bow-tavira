'use client'

import { useQuery } from '@tanstack/react-query'
import { ColumnDef } from '@tanstack/react-table'
import Link from 'next/link'
import { useState } from 'react'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { DataTable } from '@/components/shared/data-table'
import { RAGBadge } from '@/components/shared/rag-badge'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { get } from '@/lib/api'
import type { Risk, PaginatedResponse, HeatmapData } from '@/types'
import { Plus, Eye, Edit, MoreHorizontal, Grid3X3, Trash2 } from 'lucide-react'
import { useRisksStore } from '@/stores/risks'
import { useUIStore } from '@/stores/ui'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'

function useRiskColumns(): ColumnDef<Risk>[] {
  const { remove } = useRisksStore()
  const { showConfirm } = useUIStore()
  const queryClient = useQueryClient()

  return [
    {
      accessorKey: 'ref_no',
      header: 'Reference',
      cell: ({ row }) => (
        <Link
          href={`/risks/${row.original.id}`}
          className="font-medium text-primary hover:underline"
        >
          {row.getValue('ref_no')}
        </Link>
      ),
    },
    {
      accessorKey: 'name',
      header: 'Name',
      cell: ({ row }) => (
        <div className="max-w-[200px] truncate font-medium">
          {row.getValue('name')}
        </div>
      ),
    },
    {
      accessorKey: 'category',
      header: 'Category',
      cell: ({ row }) => {
        const category = row.original.category
        return (
          <div>
            <p className="text-sm">{category?.name || '-'}</p>
            <p className="text-xs text-muted-foreground">
              {category?.theme?.name}
            </p>
          </div>
        )
      },
    },
    {
      accessorKey: 'tier',
      header: 'Tier',
      cell: ({ row }) => {
        const tier = row.getValue('tier') as string
        return tier ? (
          <Badge variant="outline" className="uppercase">
            {tier.replace('_', ' ')}
          </Badge>
        ) : (
          '-'
        )
      },
    },
    {
      accessorKey: 'inherent_risk_score',
      header: 'Inherent',
      cell: ({ row }) => (
        <div className="flex items-center gap-2">
          <span className="font-medium">
            {row.original.inherent_risk_score || '-'}
          </span>
          <RAGBadge status={row.original.inherent_rag} />
        </div>
      ),
    },
    {
      accessorKey: 'residual_risk_score',
      header: 'Residual',
      cell: ({ row }) => (
        <div className="flex items-center gap-2">
          <span className="font-medium">
            {row.original.residual_risk_score || '-'}
          </span>
          <RAGBadge status={row.original.residual_rag} />
        </div>
      ),
    },
    {
      accessorKey: 'appetite_status',
      header: 'Appetite',
      cell: ({ row }) => {
        const status = row.getValue('appetite_status') as string
        const variants: Record<string, 'green' | 'amber' | 'red'> = {
          within: 'green',
          approaching: 'amber',
          exceeded: 'red',
        }
        return status ? (
          <Badge variant={variants[status] || 'outline'} className="capitalize">
            {status}
          </Badge>
        ) : (
          '-'
        )
      },
    },
    {
      id: 'actions',
      cell: ({ row }) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem asChild>
              <Link href={`/risks/${row.original.id}`}>
                <Eye className="mr-2 h-4 w-4" />
                View
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link href={`/risks/${row.original.id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem
              className="text-destructive"
              onClick={() => {
                showConfirm({
                  title: 'Delete this risk',
                  description: `Are you sure you want to delete "${row.original.ref_no} - ${row.original.name}"? This action is irreversible.`,
                  variant: 'destructive',
                  onConfirm: async () => {
                    try {
                      await remove(row.original.id)
                      toast.success('Risk deleted')
                      queryClient.invalidateQueries({ queryKey: ['risks'] })
                    } catch {
                      toast.error('Error during deletion')
                    }
                  },
                })
              }}
            >
              <Trash2 className="mr-2 h-4 w-4" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ]
}

function HeatmapView() {
  const { data: heatmap } = useQuery({
    queryKey: ['risks-heatmap'],
    queryFn: () => get<HeatmapData>('/risks/heatmap'),
  })

  const getRagBg = (rag: string) => {
    switch (rag) {
      case 'green':
        return 'bg-green-100 hover:bg-green-200'
      case 'amber':
        return 'bg-amber-100 hover:bg-amber-200'
      case 'red':
        return 'bg-red-100 hover:bg-red-200'
      default:
        return 'bg-gray-50'
    }
  }

  const matrix = heatmap?.matrix || []
  const grid: Record<string, typeof matrix[0]> = {}
  matrix.forEach((cell) => {
    grid[`${cell.impact}_${cell.probability}`] = cell
  })

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Grid3X3 className="h-5 w-5" />
          Risk Heatmap
        </CardTitle>
        <CardDescription>5x5 Risk Matrix (Impact x Probability)</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="overflow-x-auto">
          <div className="grid grid-cols-6 gap-1 min-w-[500px]">
            {/* Header row */}
            <div className="h-12" />
            {[1, 2, 3, 4, 5].map((p) => (
              <div
                key={p}
                className="flex h-12 items-center justify-center text-xs font-medium text-muted-foreground"
              >
                P{p}
              </div>
            ))}

            {/* Matrix rows */}
            {[5, 4, 3, 2, 1].map((impact) => (
              <>
                <div
                  key={`label-${impact}`}
                  className="flex h-20 items-center justify-center text-xs font-medium text-muted-foreground"
                >
                  I{impact}
                </div>
                {[1, 2, 3, 4, 5].map((prob) => {
                  const cell = grid[`${impact}_${prob}`]
                  return (
                    <div
                      key={`${impact}-${prob}`}
                      className={cn(
                        'flex h-20 flex-col items-center justify-center rounded-md border transition-colors',
                        getRagBg(cell?.rag || '')
                      )}
                    >
                      <span className="text-lg font-bold">{cell?.count || 0}</span>
                      <span className="text-xs text-muted-foreground">
                        Score: {cell?.score || impact * prob}
                      </span>
                    </div>
                  )
                })}
              </>
            ))}
          </div>
        </div>

        {/* Legend */}
        <div className="mt-6 flex items-center justify-center gap-6">
          <div className="flex items-center gap-2">
            <div className="h-4 w-4 rounded bg-green-100" />
            <span className="text-sm">Low (1-4)</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="h-4 w-4 rounded bg-amber-100" />
            <span className="text-sm">Medium (5-12)</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="h-4 w-4 rounded bg-red-100" />
            <span className="text-sm">High (13-25)</span>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

export default function RisksPage() {
  const [view, setView] = useState<'list' | 'heatmap'>('list')
  const columns = useRiskColumns()

  const { data, isLoading } = useQuery({
    queryKey: ['risks'],
    queryFn: () => get<PaginatedResponse<Risk>>('/risks'),
  })

  return (
    <>
      <Header
        title="Risk Register"
        description="Manage and monitor organizational risks"
      />

      <div className="p-6">
        <div className="mb-6 flex items-center justify-between">
          <Tabs value={view} onValueChange={(v) => setView(v as 'list' | 'heatmap')}>
            <TabsList>
              <TabsTrigger value="list">List View</TabsTrigger>
              <TabsTrigger value="heatmap">Heatmap</TabsTrigger>
            </TabsList>
          </Tabs>
          <Button asChild>
            <Link href="/risks/new">
              <Plus className="mr-2 h-4 w-4" />
              Add Risk
            </Link>
          </Button>
        </div>

        {view === 'heatmap' ? (
          <HeatmapView />
        ) : isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
          </div>
        ) : (
          <DataTable
            columns={columns}
            data={data?.data || []}
            searchKey="name"
            searchPlaceholder="Search risks..."
          />
        )}
      </div>
    </>
  )
}
