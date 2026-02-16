import { useEffect, useState } from 'react'
import { Header } from '@/components/layout/header'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { api } from '@/lib/api'
import { ShieldCheck, UserPlus, Trash2, Loader2 } from 'lucide-react'
import { toast } from 'sonner'

interface Theme {
  id: number
  code: string
  name: string
}

interface Permission {
  id: number
  theme_id: number
  user_id: number
  can_view: boolean
  can_edit: boolean
  can_create: boolean
  can_delete: boolean
  user?: { id: number; full_name?: string; email?: string }
}

export default function RiskThemePermissionsPage() {
  const [themes, setThemes] = useState<Theme[]>([])
  const [selectedThemeId, setSelectedThemeId] = useState<string>('')
  const [permissions, setPermissions] = useState<Permission[]>([])
  const [users, setUsers] = useState<{ id: number; full_name?: string; email?: string }[]>([])
  const [isLoadingThemes, setIsLoadingThemes] = useState(true)
  const [isLoadingPerms, setIsLoadingPerms] = useState(false)
  const [addDialogOpen, setAddDialogOpen] = useState(false)
  const [selectedUserId, setSelectedUserId] = useState<string>('')
  const [newPermCanView, setNewPermCanView] = useState(true)
  const [newPermCanEdit, setNewPermCanEdit] = useState(false)
  const [newPermCanCreate, setNewPermCanCreate] = useState(false)
  const [newPermCanDelete, setNewPermCanDelete] = useState(false)
  const [isSaving, setIsSaving] = useState(false)

  useEffect(() => {
    api.get<Theme[] | { data: Theme[] }>('/risks/themes')
      .then((r) => {
        const list = Array.isArray(r.data) ? r.data : (r.data as { data?: Theme[] }).data ?? []
        setThemes(list)
        if (list.length > 0 && !selectedThemeId) setSelectedThemeId(String(list[0].id))
      })
      .catch(() => setThemes([]))
      .finally(() => setIsLoadingThemes(false))
  }, [])

  useEffect(() => {
    if (!selectedThemeId) {
      setPermissions([])
      return
    }
    setIsLoadingPerms(true)
    api.get<{ data: Permission[] }>(`/risks/themes/${selectedThemeId}/permissions`)
      .then((r) => setPermissions(r.data.data ?? []))
      .catch(() => setPermissions([]))
      .finally(() => setIsLoadingPerms(false))
  }, [selectedThemeId])

  useEffect(() => {
    api.get<{ data: { id: number; full_name?: string; email?: string }[] }>('/users?per_page=100')
      .then((r) => {
        const data = r.data as { data?: { id: number; full_name?: string; email?: string }[] }
        const list = data.data ?? []
        setUsers(Array.isArray(list) ? list : [])
      })
      .catch(() => setUsers([]))
  }, [])

  const selectedTheme = themes.find((t) => String(t.id) === selectedThemeId)
  const assignedUserIds = permissions.map((p) => p.user_id)
  const availableUsers = users.filter((u) => !assignedUserIds.includes(u.id))

  const handleAddPermission = async () => {
    if (!selectedUserId || !selectedThemeId) return
    setIsSaving(true)
    try {
      await api.post(`/risks/themes/${selectedThemeId}/permissions`, {
        user_id: Number(selectedUserId),
        can_view: newPermCanView,
        can_edit: newPermCanEdit,
        can_create: newPermCanCreate,
        can_delete: newPermCanDelete,
      })
      const res = await api.get<{ data: Permission[] }>(`/risks/themes/${selectedThemeId}/permissions`)
      setPermissions(res.data.data ?? [])
      setAddDialogOpen(false)
      setSelectedUserId('')
      toast.success('Permission added')
    } catch {
      toast.error('Error adding permission')
    } finally {
      setIsSaving(false)
    }
  }

  const handleRemovePermission = async (permId: number) => {
    if (!selectedThemeId) return
    try {
      await api.delete(`/risks/themes/${selectedThemeId}/permissions/${permId}`)
      setPermissions((p) => p.filter((x) => x.id !== permId))
      toast.success('Permission removed')
    } catch {
      toast.error('Error')
    }
  }

  return (
    <>
      <Header
        title="Risk Theme Permissions"
        description="Manage access (view / edit) by risk theme (RG-BOW-011)"
      />

      <div className="p-6 space-y-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <ShieldCheck className="h-5 w-5" />
              Theme
            </CardTitle>
          </CardHeader>
          <CardContent>
            {isLoadingThemes ? (
              <p className="text-sm text-muted-foreground">Loading...</p>
            ) : (
              <Select value={selectedThemeId} onValueChange={setSelectedThemeId}>
                <SelectTrigger className="w-[320px]">
                  <SelectValue placeholder="Select a theme" />
                </SelectTrigger>
                <SelectContent>
                  {themes.map((t) => (
                    <SelectItem key={t.id} value={String(t.id)}>
                      {t.code} – {t.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
          </CardContent>
        </Card>

        {selectedTheme && (
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>Access to theme &ldquo;{selectedTheme.name}&rdquo;</CardTitle>
              <Button onClick={() => setAddDialogOpen(true)} size="sm">
                <UserPlus className="h-4 w-4 mr-2" />
                Add user
              </Button>
            </CardHeader>
            <CardContent>
              {isLoadingPerms ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Loading...
                </div>
              ) : permissions.length === 0 ? (
                <p className="text-sm text-muted-foreground py-4">
                  No specific permissions. Department-level access applies by default.
                </p>
              ) : (
                <ul className="space-y-3">
                  {permissions.map((p) => (
                    <li
                      key={p.id}
                      className="flex items-center justify-between rounded-lg border p-3"
                    >
                      <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-medium">
                          {p.user?.full_name ?? p.user?.email ?? `User #${p.user_id}`}
                        </span>
                        <Badge variant={p.can_view ? 'default' : 'secondary'}>View</Badge>
                        {p.can_edit && <Badge variant="outline">Edit</Badge>}
                        {p.can_create && <Badge variant="outline">Create</Badge>}
                        {p.can_delete && <Badge variant="outline">Delete</Badge>}
                      </div>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="text-destructive"
                        onClick={() => handleRemovePermission(p.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </li>
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>
        )}
      </div>

      <Dialog open={addDialogOpen} onOpenChange={setAddDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Access</DialogTitle>
            <DialogDescription>
              Grant a user access to theme &ldquo;{selectedTheme?.name}&rdquo;.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>User</Label>
              <Select value={selectedUserId} onValueChange={setSelectedUserId}>
                <SelectTrigger>
                  <SelectValue placeholder="Choose a user" />
                </SelectTrigger>
                <SelectContent>
                  {availableUsers.map((u) => (
                    <SelectItem key={u.id} value={String(u.id)}>
                      {u.full_name ?? u.email ?? `#${u.id}`}
                    </SelectItem>
                  ))}
                  {availableUsers.length === 0 && (
                    <SelectItem value="_none" disabled>
                      All users already have access
                    </SelectItem>
                  )}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Rights</Label>
              <div className="flex flex-wrap gap-4">
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={newPermCanView}
                    onChange={(e) => setNewPermCanView(e.target.checked)}
                  />
                  <span className="text-sm">View</span>
                </label>
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={newPermCanEdit}
                    onChange={(e) => setNewPermCanEdit(e.target.checked)}
                  />
                  <span className="text-sm">Edit</span>
                </label>
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={newPermCanCreate}
                    onChange={(e) => setNewPermCanCreate(e.target.checked)}
                  />
                  <span className="text-sm">Create</span>
                </label>
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={newPermCanDelete}
                    onChange={(e) => setNewPermCanDelete(e.target.checked)}
                  />
                  <span className="text-sm">Delete</span>
                </label>
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => setAddDialogOpen(false)}>
                Cancel
              </Button>
              <Button
                disabled={!selectedUserId || selectedUserId === '_none' || isSaving}
                onClick={handleAddPermission}
              >
                {isSaving && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                Add
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </>
  )
}
