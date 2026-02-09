'use client'

import { useEffect } from 'react'
import { useParams, useRouter } from 'next/navigation'
import Link from 'next/link'
import { useQuery } from '@tanstack/react-query'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import { PageLoading, ErrorState } from '@/components/shared'
import { useTeamsStore } from '@/stores/teams'
import { useUIStore } from '@/stores/ui'
import { get } from '@/lib/api'
import { formatDate } from '@/lib/utils'
import type { User } from '@/types'
import {
  ArrowLeft,
  Edit,
  Trash2,
  Users,
  UserPlus,
  Crown,
  X,
} from 'lucide-react'
import { toast } from 'sonner'

export default function TeamDetailPage() {
  const params = useParams()
  const router = useRouter()
  const id = Number(params.id)

  const {
    selectedItem,
    isLoadingItem,
    error,
    members,
    fetchById,
    fetchMembers,
    addMember,
    updateMember,
    removeMember,
    remove,
  } = useTeamsStore()
  const { showConfirm } = useUIStore()

  const { data: users } = useQuery({
    queryKey: ['users'],
    queryFn: () => get<{ data: User[] }>('/users'),
  })

  useEffect(() => {
    if (id) {
      fetchById(id)
      fetchMembers(id)
    }
  }, [id, fetchById, fetchMembers])

  const handleDelete = () => {
    showConfirm({
      title: 'Supprimer cette equipe',
      description: 'Cette action est irreversible. Tous les membres seront retires de l\'equipe.',
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await remove(id)
          toast.success('Equipe supprimee')
          router.push('/teams')
        } catch {
          toast.error('Erreur lors de la suppression')
        }
      },
    })
  }

  const handleRemoveMember = (memberId: number, memberName: string) => {
    showConfirm({
      title: 'Retirer ce membre',
      description: `Voulez-vous retirer ${memberName} de l'equipe?`,
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await removeMember(id, memberId)
          toast.success('Membre retire')
        } catch {
          toast.error('Erreur lors du retrait')
        }
      },
    })
  }

  const handleToggleLead = async (memberId: number, currentIsLead: boolean) => {
    try {
      await updateMember(id, memberId, !currentIsLead)
      toast.success(currentIsLead ? 'Role de lead retire' : 'Promu chef d\'equipe')
    } catch {
      toast.error('Erreur lors de la modification')
    }
  }

  if (isLoadingItem) {
    return <PageLoading text="Chargement de l'equipe..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Equipe introuvable"
        description={error || "Cette equipe n'existe pas ou a ete supprimee."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  const team = selectedItem
  const memberIds = members.map((m) => m.user_id)
  const availableUsers = users?.data?.filter((u) => !memberIds.includes(u.id)) || []

  return (
    <>
      <Header
        title={team.name}
        description={team.description || 'Equipe'}
      />

      <div className="p-6">
        <div className="mb-6 flex items-center justify-between">
          <Button variant="ghost" asChild>
            <Link href="/teams">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour a la liste
            </Link>
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={`/teams/${id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Modifier
              </Link>
            </Button>
            <Button variant="destructive" onClick={handleDelete}>
              <Trash2 className="mr-2 h-4 w-4" />
              Supprimer
            </Button>
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          <div className="space-y-6 lg:col-span-2">
            {/* Members */}
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2">
                  <Users className="h-5 w-5" />
                  Membres ({members.length})
                </CardTitle>
              </CardHeader>
              <CardContent>
                {members.length === 0 ? (
                  <p className="py-8 text-center text-muted-foreground">
                    Aucun membre dans cette equipe
                  </p>
                ) : (
                  <div className="space-y-3">
                    {members.map((member) => (
                      <div
                        key={member.id}
                        className="flex items-center justify-between rounded-lg border p-3"
                      >
                        <div className="flex items-center gap-3">
                          <Avatar>
                            <AvatarFallback>
                              {member.user?.full_name?.[0]?.toUpperCase() || 'U'}
                            </AvatarFallback>
                          </Avatar>
                          <div>
                            <p className="font-medium">{member.user?.full_name}</p>
                            <p className="text-sm text-muted-foreground">
                              {member.user?.email}
                            </p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          {member.is_lead && (
                            <Badge className="bg-amber-100 text-amber-800">
                              <Crown className="mr-1 h-3 w-3" />
                              Lead
                            </Badge>
                          )}
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleToggleLead(member.id, member.is_lead)}
                          >
                            {member.is_lead ? 'Retirer lead' : 'Promouvoir lead'}
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() =>
                              handleRemoveMember(member.id, member.user?.full_name || '')
                            }
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

            {/* Add member */}
            {availableUsers.length > 0 && (
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <UserPlus className="h-5 w-5" />
                    Ajouter un membre
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    {availableUsers.slice(0, 6).map((user) => (
                      <Button
                        key={user.id}
                        variant="outline"
                        className="justify-start"
                        onClick={async () => {
                          try {
                            await addMember(id, user.id)
                            toast.success(`${user.full_name} ajoute a l'equipe`)
                          } catch {
                            toast.error('Erreur lors de l\'ajout')
                          }
                        }}
                      >
                        <Avatar className="mr-2 h-6 w-6">
                          <AvatarFallback className="text-xs">
                            {user.full_name?.[0]?.toUpperCase()}
                          </AvatarFallback>
                        </Avatar>
                        {user.full_name}
                      </Button>
                    ))}
                  </div>
                </CardContent>
              </Card>
            )}
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Details</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Statut</span>
                  <Badge variant={team.is_active ? 'default' : 'secondary'}>
                    {team.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </div>

                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Membres</span>
                  <span className="font-medium">{members.length}</span>
                </div>

                <Separator />

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Creee le</h4>
                  <p className="mt-1 text-sm">{formatDate(team.created_at)}</p>
                </div>

                <div>
                  <h4 className="text-sm font-medium text-muted-foreground">Modifiee le</h4>
                  <p className="mt-1 text-sm">{formatDate(team.updated_at)}</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </>
  )
}
