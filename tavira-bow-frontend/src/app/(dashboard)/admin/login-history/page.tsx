import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Header } from '@/components/layout/header'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { get } from '@/lib/api'
import { format } from 'date-fns'
import { Monitor, Smartphone, Globe } from 'lucide-react'

interface LoginEntry {
  id: number
  user_id: number
  ip_address: string | null
  user_agent: string | null
  logged_in_at: string
  user?: {
    id: number
    full_name: string
    email: string
  }
}

interface LoginHistoryResponse {
  data: LoginEntry[]
  current_page: number
  last_page: number
  total: number
}

interface UserOption {
  id: number
  full_name: string
  email: string
}

function parseUserAgent(ua: string | null): string {
  if (!ua) return 'Unknown'
  if (ua.includes('Chrome') && !ua.includes('Edg')) return 'Chrome'
  if (ua.includes('Firefox')) return 'Firefox'
  if (ua.includes('Safari') && !ua.includes('Chrome')) return 'Safari'
  if (ua.includes('Edg')) return 'Edge'
  return ua.slice(0, 40)
}

function DeviceIcon({ ua }: { ua: string | null }) {
  if (!ua) return <Globe className="h-4 w-4 text-muted-foreground" />
  if (ua.includes('Mobile') || ua.includes('Android') || ua.includes('iPhone'))
    return <Smartphone className="h-4 w-4 text-muted-foreground" />
  return <Monitor className="h-4 w-4 text-muted-foreground" />
}

export default function LoginHistoryPage() {
  const [page, setPage] = useState(1)
  const [userId, setUserId] = useState<string>('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')

  const { data: users } = useQuery({
    queryKey: ['users-list'],
    queryFn: () => get<{ data: UserOption[] }>('/users?per_page=200'),
  })

  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', '30')
  if (userId) params.set('user_id', userId)
  if (dateFrom) params.set('from', dateFrom)
  if (dateTo) params.set('to', dateTo)

  const { data, isLoading } = useQuery({
    queryKey: ['login-history', page, userId, dateFrom, dateTo],
    queryFn: () =>
      get<LoginHistoryResponse>(`/admin/login-history?${params.toString()}`),
  })

  const resetFilters = () => {
    setUserId('')
    setDateFrom('')
    setDateTo('')
    setPage(1)
  }

  return (
    <>
      <Header title="Login History" description="Connection history for all users (admin only)" />

      <div className="p-6 space-y-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex flex-wrap gap-3 items-end">
              <div>
                <label className="text-xs text-muted-foreground mb-1 block">User</label>
                <Select value={userId} onValueChange={(v) => { setUserId(v === 'all' ? '' : v); setPage(1) }}>
                  <SelectTrigger className="w-[200px]">
                    <SelectValue placeholder="All users" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All users</SelectItem>
                    {users?.data?.map((u) => (
                      <SelectItem key={u.id} value={String(u.id)}>
                        {u.full_name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <label className="text-xs text-muted-foreground mb-1 block">From</label>
                <Input
                  type="date"
                  value={dateFrom}
                  onChange={(e) => { setDateFrom(e.target.value); setPage(1) }}
                  className="w-[160px]"
                />
              </div>
              <div>
                <label className="text-xs text-muted-foreground mb-1 block">To</label>
                <Input
                  type="date"
                  value={dateTo}
                  onChange={(e) => { setDateTo(e.target.value); setPage(1) }}
                  className="w-[160px]"
                />
              </div>
              <Button variant="outline" size="sm" onClick={resetFilters}>
                Reset
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            {isLoading ? (
              <p className="text-muted-foreground text-sm py-8 text-center">Loading...</p>
            ) : (
              <>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>User</TableHead>
                      <TableHead>Email</TableHead>
                      <TableHead>IP Address</TableHead>
                      <TableHead>Browser</TableHead>
                      <TableHead>Date</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {data?.data?.map((entry) => (
                      <TableRow key={entry.id}>
                        <TableCell className="font-medium">
                          {entry.user?.full_name ?? `User #${entry.user_id}`}
                        </TableCell>
                        <TableCell className="text-muted-foreground">
                          {entry.user?.email ?? '-'}
                        </TableCell>
                        <TableCell className="font-mono text-sm">
                          {entry.ip_address ?? '-'}
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <DeviceIcon ua={entry.user_agent} />
                            <span className="text-sm">{parseUserAgent(entry.user_agent)}</span>
                          </div>
                        </TableCell>
                        <TableCell className="text-sm">
                          {format(new Date(entry.logged_in_at), 'dd/MM/yyyy HH:mm')}
                        </TableCell>
                      </TableRow>
                    ))}
                    {(!data?.data || data.data.length === 0) && (
                      <TableRow>
                        <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                          No login history found
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>

                {data && data.last_page > 1 && (
                  <div className="flex items-center justify-between pt-4">
                    <p className="text-sm text-muted-foreground">
                      Page {data.current_page} / {data.last_page} ({data.total} entries)
                    </p>
                    <div className="flex gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        disabled={page <= 1}
                        onClick={() => setPage(page - 1)}
                      >
                        Previous
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        disabled={page >= data.last_page}
                        onClick={() => setPage(page + 1)}
                      >
                        Next
                      </Button>
                    </div>
                  </div>
                )}
              </>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  )
}
