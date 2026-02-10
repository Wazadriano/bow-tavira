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
      toast.success('User created successfully')
      router.push('/users')
    } catch {
      toast.error('Error during creation')
    }
  }

  return (
    <>
      <Header
        title="New User"
        description="Create a new user account"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href="/users">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to list
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
