'use client'

import { useEffect } from 'react'
import { useParams, useRouter } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { UserForm } from '@/components/users'
import { PageLoading, ErrorState } from '@/components/shared'
import { useUsersStore, type UserFormData } from '@/stores/users'
import { ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'

export default function EditUserPage() {
  const params = useParams()
  const router = useRouter()
  const id = Number(params.id)

  const { selectedUser, isLoadingUser, isSaving, error, fetchById, update } =
    useUsersStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
    }
  }, [id, fetchById])

  const handleSubmit = async (data: UserFormData) => {
    try {
      await update(id, data)
      toast.success('Utilisateur mis a jour')
      router.push(`/users/${id}`)
    } catch {
      toast.error('Erreur lors de la mise a jour')
    }
  }

  if (isLoadingUser) {
    return <PageLoading text="Chargement..." />
  }

  if (error || !selectedUser) {
    return (
      <ErrorState
        title="Utilisateur introuvable"
        description={error || "Cet utilisateur n'existe pas."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  return (
    <>
      <Header
        title={`Modifier ${selectedUser.full_name || selectedUser.email}`}
        description="Modifier les informations de l'utilisateur"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href={`/users/${id}`}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour au detail
            </Link>
          </Button>
        </div>

        <div className="max-w-2xl">
          <UserForm
            user={selectedUser}
            onSubmit={handleSubmit}
            isLoading={isSaving}
          />
        </div>
      </div>
    </>
  )
}
