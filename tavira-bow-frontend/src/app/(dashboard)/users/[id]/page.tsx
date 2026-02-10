'use client'

import { useEffect, useState } from 'react'
import { useParams, useRouter } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { PageLoading, ErrorState } from '@/components/shared'
import { useUsersStore, type DepartmentPermission } from '@/stores/users'
import { useUIStore } from '@/stores/ui'
import { formatDate } from '@/lib/utils'
import {
  ArrowLeft,
  Edit,
  Trash2,
  User,
  Mail,
  Shield,
  Building2,
  Calendar,
  UserCheck,
  UserX,
  Plus,
  X,
} from 'lucide-react'
import { toast } from 'sonner'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const departments = ['IT', 'Finance', 'Operations', 'Compliance', 'HR', 'Legal', 'Marketing']

export default function UserDetailPage() {
  const params = useParams()
  const router = useRouter()
  const id = Number(params.id)
  const [isAddingPermission, setIsAddingPermission] = useState(false)
  const [selectedDept, setSelectedDept] = useState('')
  const [selectedLevel, setSelectedLevel] = useState('read')

  const {
    selectedUser,
    isLoadingUser,
    error,
    permissions,
    fetchById,
    remove,
    toggleActive,
    fetchPermissions,
    addPermission,
    removePermission,
  } = useUsersStore()
  const { showConfirm } = useUIStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
      fetchPermissions(id)
    }
  }, [id, fetchById, fetchPermissions])

  const handleDelete = () => {
    showConfirm({
      title: 'Delete this user',
      description:
        'This action is irreversible. Do you really want to delete this user?',
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await remove(id)
          toast.success('User deleted')
          router.push('/users')
        } catch {
          toast.error('Error during deletion')
        }
      },
    })
  }

  const handleToggleActive = async () => {
    try {
      await toggleActive(id)
      toast.success('Status updated')
    } catch {
      toast.error('Error during update')
    }
  }

  const handleAddPermission = async () => {
    if (!selectedDept) return
    try {
      await addPermission(id, selectedDept, selectedLevel)
      toast.success('Permission added')
      setIsAddingPermission(false)
      setSelectedDept('')
      setSelectedLevel('read')
    } catch {
      toast.error('Error during addition')
    }
  }

  const handleRemovePermission = async (permissionId: number) => {
    try {
      await removePermission(id, permissionId)
      toast.success('Permission deleted')
    } catch {
      toast.error('Error during deletion')
    }
  }

  if (isLoadingUser) {
    return <PageLoading text="Loading user..." />
  }

  if (error || !selectedUser) {
    return (
      <ErrorState
        title="User not found"
        description={error || "This user does not exist or has been deleted."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  const user = selectedUser
  const usedDepts = permissions.map((p) => p.department)
  const availableDepts = departments.filter((d) => !usedDepts.includes(d))

  return (
    <>
      <Header
        title={user.full_name || user.email}
        description={`@${user.email}`}
      />

      <div className="p-6">
        <div className="mb-6 flex items-center justify-between">
          <Button variant="ghost" asChild>
            <Link href="/users">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to list
            </Link>
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" onClick={handleToggleActive}>
              {user.is_active ? (
                <>
                  <UserX className="mr-2 h-4 w-4" />
                  Desactiver
                </>
              ) : (
                <>
                  <UserCheck className="mr-2 h-4 w-4" />
                  Activer
                </>
              )}
            </Button>
            <Button variant="outline" asChild>
              <Link href={`/users/${id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </Button>
            <Button variant="destructive" onClick={handleDelete}>
              <Trash2 className="mr-2 h-4 w-4" />
              Delete
            </Button>
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          <div className="space-y-6 lg:col-span-2">
            {/* User info */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <User className="h-5 w-5" />
                  Information
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center gap-6">
                  <div className="flex h-20 w-20 items-center justify-center rounded-full bg-muted text-2xl font-bold">
                    {user.full_name?.charAt(0).toUpperCase() || 'U'}
                  </div>
                  <div>
                    <h2 className="text-xl font-semibold">{user.full_name}</h2>
                    <p className="text-muted-foreground">@{user.email}</p>
                    {user.full_name && (
                      <p className="text-sm text-muted-foreground">
                        Affiche comme: {user.full_name}
                      </p>
                    )}
                  </div>
                </div>

                <Separator />

                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Email</h4>
                    <p className="mt-1 flex items-center gap-2">
                      <Mail className="h-4 w-4 text-muted-foreground" />
                      <a href={`mailto:${user.email}`} className="text-primary hover:underline">
                        {user.email}
                      </a>
                    </p>
                  </div>

                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Department</h4>
                    <p className="mt-1 flex items-center gap-2">
                      <Building2 className="h-4 w-4 text-muted-foreground" />
                      {user.department || '-'}
                    </p>
                  </div>

                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Cree le</h4>
                    <p className="mt-1 flex items-center gap-2">
                      <Calendar className="h-4 w-4 text-muted-foreground" />
                      {formatDate(user.created_at)}
                    </p>
                  </div>

                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">Modifie le</h4>
                    <p className="mt-1 flex items-center gap-2">
                      <Calendar className="h-4 w-4 text-muted-foreground" />
                      {formatDate(user.updated_at)}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Permissions */}
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2">
                  <Shield className="h-5 w-5" />
                  Permissions by Department
                </CardTitle>
                <Dialog open={isAddingPermission} onOpenChange={setIsAddingPermission}>
                  <DialogTrigger asChild>
                    <Button variant="outline" size="sm" disabled={availableDepts.length === 0}>
                      <Plus className="h-4 w-4 mr-1" />
                      Add
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Add Permission</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Department</label>
                        <Select value={selectedDept} onValueChange={setSelectedDept}>
                          <SelectTrigger>
                            <SelectValue placeholder="Select..." />
                          </SelectTrigger>
                          <SelectContent>
                            {availableDepts.map((dept) => (
                              <SelectItem key={dept} value={dept}>
                                {dept}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Niveau d&apos;acces</label>
                        <Select value={selectedLevel} onValueChange={setSelectedLevel}>
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="read">Lecture</SelectItem>
                            <SelectItem value="write">Lecture/Ecriture</SelectItem>
                            <SelectItem value="admin">Administration</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setIsAddingPermission(false)}>
                          Cancel
                        </Button>
                        <Button onClick={handleAddPermission} disabled={!selectedDept}>
                          Add
                        </Button>
                      </div>
                    </div>
                  </DialogContent>
                </Dialog>
              </CardHeader>
              <CardContent>
                {permissions.length === 0 ? (
                  <p className="py-4 text-center text-muted-foreground">
                    No specific permissions configured
                  </p>
                ) : (
                  <div className="space-y-2">
                    {permissions.map((perm) => (
                      <div
                        key={perm.id}
                        className="flex items-center justify-between p-3 rounded-lg border group"
                      >
                        <div className="flex items-center gap-3">
                          <Building2 className="h-4 w-4 text-muted-foreground" />
                          <span className="font-medium">{perm.department}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Badge
                            variant="outline"
                            className={
                              perm.access_level === 'admin'
                                ? 'bg-purple-100 text-purple-800'
                                : perm.access_level === 'write'
                                ? 'bg-green-100 text-green-800'
                                : 'bg-blue-100 text-blue-800'
                            }
                          >
                            {perm.access_level === 'admin'
                              ? 'Admin'
                              : perm.access_level === 'write'
                              ? 'Ecriture'
                              : 'Lecture'}
                          </Badge>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="opacity-0 group-hover:opacity-100"
                            onClick={() => handleRemovePermission(perm.id)}
                          >
                            <X className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>

          <div className="space-y-6">
            {/* Status */}
            <Card>
              <CardHeader>
                <CardTitle>Statut</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Role</span>
                  <Badge
                    className={
                      user.role === 'admin'
                        ? 'bg-purple-100 text-purple-800'
                        : 'bg-blue-100 text-blue-800'
                    }
                  >
                    {user.role === 'admin' && <Shield className="mr-1 h-3 w-3" />}
                    {user.role === 'admin' ? 'Administrateur' : 'Membre'}
                  </Badge>
                </div>

                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Compte</span>
                  <Badge
                    className={
                      user.is_active
                        ? 'bg-green-100 text-green-800'
                        : 'bg-gray-100 text-gray-800'
                    }
                  >
                    {user.is_active ? (
                      <>
                        <UserCheck className="mr-1 h-3 w-3" />
                        Actif
                      </>
                    ) : (
                      <>
                        <UserX className="mr-1 h-3 w-3" />
                        Inactif
                      </>
                    )}
                  </Badge>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </>
  )
}
