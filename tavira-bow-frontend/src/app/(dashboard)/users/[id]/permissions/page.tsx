import { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { api, get } from '@/lib/api'
import { ArrowLeft, ShieldCheck, Loader2, Save } from 'lucide-react'
import { toast } from 'sonner'
import type { SettingList } from '@/types'

interface DeptPermission {
  id?: number
  department: string
  can_view: boolean
  can_edit_status: boolean
  can_create_tasks: boolean
  can_edit_all: boolean
}

export default function UserPermissionsPage() {
  const params = useParams()
  const navigate = useNavigate()
  const userId = Number(params.id)

  const [userName, setUserName] = useState('')
  const [departments, setDepartments] = useState<string[]>([])
  const [permissions, setPermissions] = useState<DeptPermission[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)

  useEffect(() => {
    if (!userId) return

    const load = async () => {
      setIsLoading(true)
      try {
        const [userRes, permsRes, deptsRes] = await Promise.all([
          api.get<{ user?: { full_name?: string }; full_name?: string }>(`/users/${userId}`),
          api.get<DeptPermission[]>(`/users/${userId}/permissions`),
          get<{ data: SettingList[] }>('/settings/lists?type=department'),
        ])

        const userData = (userRes.data as { user?: { full_name?: string } }).user ?? userRes.data
        setUserName((userData as { full_name?: string }).full_name || `User #${userId}`)

        const existingPerms = Array.isArray(permsRes.data) ? permsRes.data : []
        const deptList = (deptsRes?.data || []).map((d) => d.value)
        setDepartments(deptList)

        const merged: DeptPermission[] = deptList.map((dept) => {
          const existing = existingPerms.find((p) => p.department === dept)
          return existing || {
            department: dept,
            can_view: false,
            can_edit_status: false,
            can_create_tasks: false,
            can_edit_all: false,
          }
        })
        setPermissions(merged)
      } catch {
        toast.error('Error loading permissions')
      } finally {
        setIsLoading(false)
      }
    }
    load()
  }, [userId])

  const toggleField = (dept: string, field: keyof Omit<DeptPermission, 'id' | 'department'>) => {
    setPermissions((prev) =>
      prev.map((p) => {
        if (p.department !== dept) return p
        return { ...p, [field]: !p[field] }
      })
    )
  }

  const handleSave = async () => {
    setIsSaving(true)
    try {
      for (const perm of permissions) {
        const hasAny = perm.can_view || perm.can_edit_status || perm.can_create_tasks || perm.can_edit_all
        if (perm.id && !hasAny) {
          await api.delete(`/users/${userId}/permissions/${perm.id}`)
        } else if (perm.id && hasAny) {
          await api.put(`/users/${userId}/permissions/${perm.id}`, {
            can_view: perm.can_view,
            can_edit_status: perm.can_edit_status,
            can_create_tasks: perm.can_create_tasks,
            can_edit_all: perm.can_edit_all,
          })
        } else if (!perm.id && hasAny) {
          await api.post(`/users/${userId}/permissions`, {
            department: perm.department,
            can_view: perm.can_view,
            can_edit_status: perm.can_edit_status,
            can_create_tasks: perm.can_create_tasks,
            can_edit_all: perm.can_edit_all,
          })
        }
      }

      const refreshed = await api.get<DeptPermission[]>(`/users/${userId}/permissions`)
      const existingPerms = Array.isArray(refreshed.data) ? refreshed.data : []
      setPermissions(
        departments.map((dept) => {
          const existing = existingPerms.find((p) => p.department === dept)
          return existing || {
            department: dept,
            can_view: false,
            can_edit_status: false,
            can_create_tasks: false,
            can_edit_all: false,
          }
        })
      )
      toast.success('Permissions saved')
    } catch {
      toast.error('Error saving permissions')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <>
      <Header
        title={`Permissions - ${userName}`}
        description="Manage department-level access permissions"
      />

      <div className="p-6 space-y-6">
        <div className="flex items-center justify-between">
          <Button variant="ghost" onClick={() => navigate(`/users/${userId}`)}>
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to user
          </Button>
          <Button onClick={handleSave} disabled={isSaving || isLoading}>
            {isSaving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
            Save All
          </Button>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <ShieldCheck className="h-5 w-5" />
              Department Permissions
            </CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="flex items-center gap-2 py-8 justify-center text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" />
                Loading...
              </div>
            ) : permissions.length === 0 ? (
              <p className="py-4 text-center text-muted-foreground">
                No departments configured. Add departments in Settings first.
              </p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-3 px-2 font-medium">Department</th>
                      <th className="text-center py-3 px-2 font-medium">View</th>
                      <th className="text-center py-3 px-2 font-medium">Edit Status</th>
                      <th className="text-center py-3 px-2 font-medium">Create Tasks</th>
                      <th className="text-center py-3 px-2 font-medium">Full Edit</th>
                    </tr>
                  </thead>
                  <tbody>
                    {permissions.map((perm) => (
                      <tr key={perm.department} className="border-b hover:bg-muted/50">
                        <td className="py-3 px-2">
                          <div className="flex items-center gap-2">
                            <span className="font-medium">{perm.department}</span>
                            {(perm.can_view || perm.can_edit_status || perm.can_create_tasks || perm.can_edit_all) && (
                              <Badge variant="secondary" className="text-xs">Active</Badge>
                            )}
                          </div>
                        </td>
                        <td className="text-center py-3 px-2">
                          <input
                            type="checkbox"
                            checked={perm.can_view}
                            onChange={() => toggleField(perm.department, 'can_view')}
                            className="h-4 w-4 rounded border-gray-300"
                          />
                        </td>
                        <td className="text-center py-3 px-2">
                          <input
                            type="checkbox"
                            checked={perm.can_edit_status}
                            onChange={() => toggleField(perm.department, 'can_edit_status')}
                            className="h-4 w-4 rounded border-gray-300"
                          />
                        </td>
                        <td className="text-center py-3 px-2">
                          <input
                            type="checkbox"
                            checked={perm.can_create_tasks}
                            onChange={() => toggleField(perm.department, 'can_create_tasks')}
                            className="h-4 w-4 rounded border-gray-300"
                          />
                        </td>
                        <td className="text-center py-3 px-2">
                          <input
                            type="checkbox"
                            checked={perm.can_edit_all}
                            onChange={() => toggleField(perm.department, 'can_edit_all')}
                            className="h-4 w-4 rounded border-gray-300"
                          />
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
    </>
  )
}
