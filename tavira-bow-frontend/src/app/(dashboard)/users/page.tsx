'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { TableLoading, EmptyState } from '@/components/shared'
import { useUsersStore } from '@/stores/users'
import { useUIStore } from '@/stores/ui'
import { formatDate } from '@/lib/utils'
import {
  Plus,
  Search,
  MoreHorizontal,
  Eye,
  Edit,
  Trash2,
  UserCheck,
  UserX,
  Shield,
  User,
} from 'lucide-react'
import { toast } from 'sonner'

const ROLE_LABELS: Record<string, string> = {
  admin: 'Administrateur',
  member: 'Membre',
}

const ROLE_COLORS: Record<string, string> = {
  admin: 'bg-purple-100 text-purple-800',
  member: 'bg-blue-100 text-blue-800',
}

export default function UsersPage() {
  const router = useRouter()
  const {
    users,
    isLoading,
    filters,
    currentPage,
    lastPage,
    total,
    fetchUsers,
    setFilters,
    resetFilters,
    remove,
    toggleActive,
  } = useUsersStore()
  const { showConfirm } = useUIStore()

  useEffect(() => {
    fetchUsers()
  }, [fetchUsers])

  const handleDelete = (id: number, name: string) => {
    showConfirm({
      title: 'Supprimer cet utilisateur',
      description: `Etes-vous sur de vouloir supprimer ${name}? Cette action est irreversible.`,
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await remove(id)
          toast.success('Utilisateur supprime')
        } catch {
          toast.error('Erreur lors de la suppression')
        }
      },
    })
  }

  const handleToggleActive = async (id: number) => {
    try {
      await toggleActive(id)
      toast.success('Statut mis a jour')
    } catch {
      toast.error('Erreur lors de la mise a jour')
    }
  }

  return (
    <>
      <Header
        title="Utilisateurs"
        description="Gestion des utilisateurs et de leurs permissions"
        actions={
          <Button asChild>
            <Link href="/users/new">
              <Plus className="mr-2 h-4 w-4" />
              Nouvel utilisateur
            </Link>
          </Button>
        }
      />

      <div className="p-6">
        {/* Filters */}
        <div className="mb-6 flex flex-wrap items-center gap-4">
          <div className="relative flex-1 min-w-[200px]">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              placeholder="Rechercher..."
              className="pl-10"
              value={filters.search || ''}
              onChange={(e) => setFilters({ search: e.target.value })}
            />
          </div>

          <Select
            value={filters.role || 'all'}
            onValueChange={(value) =>
              setFilters({ role: value === 'all' ? undefined : value })
            }
          >
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="Role" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Tous les roles</SelectItem>
              <SelectItem value="admin">Administrateur</SelectItem>
              <SelectItem value="member">Membre</SelectItem>
            </SelectContent>
          </Select>

          <Select
            value={
              filters.is_active === undefined
                ? 'all'
                : filters.is_active
                ? 'active'
                : 'inactive'
            }
            onValueChange={(value) =>
              setFilters({
                is_active:
                  value === 'all' ? undefined : value === 'active',
              })
            }
          >
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="Statut" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Tous les statuts</SelectItem>
              <SelectItem value="active">Actifs</SelectItem>
              <SelectItem value="inactive">Inactifs</SelectItem>
            </SelectContent>
          </Select>

          <Button variant="outline" onClick={resetFilters}>
            Reinitialiser
          </Button>
        </div>

        {/* Table */}
        {isLoading ? (
          <TableLoading rows={10} />
        ) : users.length === 0 ? (
          <EmptyState
            icon={User}
            title="Aucun utilisateur"
            description="Commencez par creer un utilisateur"
            actionLabel="Nouvel utilisateur"
            onAction={() => router.push('/users/new')}
          />
        ) : (
          <>
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Utilisateur</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Role</TableHead>
                    <TableHead>Departement</TableHead>
                    <TableHead>Statut</TableHead>
                    <TableHead>Cree le</TableHead>
                    <TableHead className="w-[50px]" />
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {users.map((user) => (
                    <TableRow key={user.id}>
                      <TableCell>
                        <div className="flex items-center gap-3">
                          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted font-semibold">
                            {user.full_name?.charAt(0).toUpperCase() || 'U'}
                          </div>
                          <div>
                            <p className="font-medium">{user.full_name}</p>
                            <p className="text-sm text-muted-foreground">
                              @{user.email}
                            </p>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>{user.email}</TableCell>
                      <TableCell>
                        <Badge className={ROLE_COLORS[user.role] || ''}>
                          {user.role === 'admin' && (
                            <Shield className="mr-1 h-3 w-3" />
                          )}
                          {ROLE_LABELS[user.role] || user.role}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        {user.department ? (
                          <Badge variant="outline">{user.department}</Badge>
                        ) : (
                          <span className="text-muted-foreground">-</span>
                        )}
                      </TableCell>
                      <TableCell>
                        <Badge
                          variant={user.is_active ? 'default' : 'secondary'}
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
                      </TableCell>
                      <TableCell>{formatDate(user.created_at)}</TableCell>
                      <TableCell>
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                              <Link href={`/users/${user.id}`}>
                                <Eye className="mr-2 h-4 w-4" />
                                Voir
                              </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                              <Link href={`/users/${user.id}/edit`}>
                                <Edit className="mr-2 h-4 w-4" />
                                Modifier
                              </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              onClick={() => handleToggleActive(user.id)}
                            >
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
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              className="text-destructive"
                              onClick={() =>
                                handleDelete(user.id, user.full_name || user.email)
                              }
                            >
                              <Trash2 className="mr-2 h-4 w-4" />
                              Supprimer
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>

            {/* Pagination */}
            <div className="mt-4 flex items-center justify-between">
              <p className="text-sm text-muted-foreground">
                {total} utilisateur{total > 1 ? 's' : ''} au total
              </p>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => fetchUsers(currentPage - 1)}
                  disabled={currentPage <= 1}
                >
                  Precedent
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => fetchUsers(currentPage + 1)}
                  disabled={currentPage >= lastPage}
                >
                  Suivant
                </Button>
              </div>
            </div>
          </>
        )}
      </div>
    </>
  )
}
