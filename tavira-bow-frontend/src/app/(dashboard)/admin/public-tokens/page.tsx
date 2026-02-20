import { useEffect, useState } from 'react'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { api } from '@/lib/api'
import { Plus, Trash2, Loader2, Copy, ExternalLink, Key } from 'lucide-react'
import { toast } from 'sonner'
import { useUIStore } from '@/stores/ui'
import { formatDate } from '@/lib/utils'

interface Token {
  id: number
  name: string
  token: string
  is_active: boolean
  expires_at: string | null
  last_used_at: string | null
  created_by: string | null
  created_at: string
}

export default function PublicTokensPage() {
  const [tokens, setTokens] = useState<Token[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [createOpen, setCreateOpen] = useState(false)
  const [newName, setNewName] = useState('')
  const [newExpires, setNewExpires] = useState('')
  const [isSaving, setIsSaving] = useState(false)
  const { showConfirm } = useUIStore()

  const fetchTokens = async () => {
    try {
      const res = await api.get<{ data: Token[] }>('/admin/public-tokens')
      setTokens(res.data.data ?? [])
    } catch {
      toast.error('Error loading tokens')
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    fetchTokens()
  }, [])

  const handleCreate = async () => {
    if (!newName.trim()) return
    setIsSaving(true)
    try {
      const res = await api.post<{ token: string }>('/admin/public-tokens', {
        name: newName.trim(),
        expires_at: newExpires || null,
      })
      toast.success(`Token created: ${res.data.token}`)
      setCreateOpen(false)
      setNewName('')
      setNewExpires('')
      fetchTokens()
    } catch {
      toast.error('Error creating token')
    } finally {
      setIsSaving(false)
    }
  }

  const handleToggleActive = async (token: Token) => {
    try {
      await api.put(`/admin/public-tokens/${token.id}`, {
        is_active: !token.is_active,
      })
      setTokens((prev) =>
        prev.map((t) => (t.id === token.id ? { ...t, is_active: !t.is_active } : t))
      )
      toast.success(token.is_active ? 'Token deactivated' : 'Token activated')
    } catch {
      toast.error('Error updating token')
    }
  }

  const handleDelete = (token: Token) => {
    showConfirm({
      title: 'Delete this token',
      description: `Are you sure you want to delete "${token.name}"? Anyone using this token will lose access.`,
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await api.delete(`/admin/public-tokens/${token.id}`)
          setTokens((prev) => prev.filter((t) => t.id !== token.id))
          toast.success('Token deleted')
        } catch {
          toast.error('Error deleting token')
        }
      },
    })
  }

  const copyLink = (token: string) => {
    const baseUrl = window.location.origin
    const link = `${baseUrl}/public/dashboard?token=${token}`
    navigator.clipboard.writeText(link)
    toast.success('Link copied to clipboard')
  }

  const maskToken = (token: string) => {
    if (token.length <= 8) return token
    return token.slice(0, 4) + '...' + token.slice(-4)
  }

  return (
    <>
      <Header
        title="Public Dashboard Tokens"
        description="Manage access tokens for the public dashboard"
      />

      <div className="p-6 space-y-6">
        <div className="flex justify-end">
          <Button onClick={() => setCreateOpen(true)}>
            <Plus className="mr-2 h-4 w-4" />
            Create Token
          </Button>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Key className="h-5 w-5" />
              Tokens
            </CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
              </div>
            ) : tokens.length === 0 ? (
              <p className="py-8 text-center text-muted-foreground">
                No tokens created yet. Create one to share the public dashboard.
              </p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-3 px-2 font-medium">Name</th>
                      <th className="text-left py-3 px-2 font-medium">Token</th>
                      <th className="text-center py-3 px-2 font-medium">Active</th>
                      <th className="text-left py-3 px-2 font-medium">Expires</th>
                      <th className="text-left py-3 px-2 font-medium">Last Used</th>
                      <th className="text-left py-3 px-2 font-medium">Created By</th>
                      <th className="text-right py-3 px-2 font-medium">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {tokens.map((token) => (
                      <tr key={token.id} className="border-b hover:bg-muted/50">
                        <td className="py-3 px-2 font-medium">{token.name}</td>
                        <td className="py-3 px-2">
                          <code className="text-xs bg-muted px-2 py-1 rounded">
                            {maskToken(token.token)}
                          </code>
                        </td>
                        <td className="py-3 px-2 text-center">
                          <Switch
                            checked={token.is_active}
                            onCheckedChange={() => handleToggleActive(token)}
                          />
                        </td>
                        <td className="py-3 px-2 text-muted-foreground">
                          {token.expires_at ? formatDate(token.expires_at) : 'Never'}
                        </td>
                        <td className="py-3 px-2 text-muted-foreground">
                          {token.last_used_at ? formatDate(token.last_used_at) : 'Never'}
                        </td>
                        <td className="py-3 px-2 text-muted-foreground">
                          {token.created_by || '-'}
                        </td>
                        <td className="py-3 px-2">
                          <div className="flex justify-end gap-1">
                            <Button
                              variant="ghost"
                              size="icon"
                              title="Copy link"
                              onClick={() => copyLink(token.token)}
                            >
                              <Copy className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              title="Open dashboard"
                              onClick={() => {
                                window.open(`/public/dashboard?token=${token.token}`, '_blank')
                              }}
                            >
                              <ExternalLink className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-destructive"
                              title="Delete"
                              onClick={() => handleDelete(token)}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Create Access Token</DialogTitle>
            <DialogDescription>
              Generate a new token for sharing the public dashboard with external stakeholders.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>Name *</Label>
              <Input
                value={newName}
                onChange={(e) => setNewName(e.target.value)}
                placeholder="e.g. Board Meeting Q1"
              />
            </div>
            <div className="space-y-2">
              <Label>Expiration Date (optional)</Label>
              <Input
                type="date"
                value={newExpires}
                onChange={(e) => setNewExpires(e.target.value)}
              />
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => setCreateOpen(false)}>
                Cancel
              </Button>
              <Button onClick={handleCreate} disabled={!newName.trim() || isSaving}>
                {isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Create
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </>
  )
}
