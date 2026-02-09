'use client'

import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { UserForm } from '@/components/users'
import { useUsersStore, type UserFormData } from '@/stores/users'
import { ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'

export default function NewUserPage() {
  const router = useRouter()
  const { create, isSaving } = useUsersStore()

  const handleSubmit = async (data: UserFormData) => {
    try {
      await create(data)
      toast.success('Utilisateur cree avec succes')
      router.push('/users')
    } catch {
      toast.error('Erreur lors de la creation')
    }
  }

  return (
    <>
      <Header
        title="Nouvel utilisateur"
        description="Creer un nouveau compte utilisateur"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href="/users">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour a la liste
            </Link>
          </Button>
        </div>

        <div className="max-w-2xl">
          <UserForm onSubmit={handleSubmit} isLoading={isSaving} />
        </div>
      </div>
    </>
  )
}
