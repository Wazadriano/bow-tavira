import { useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Link } from 'react-router-dom'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { UserForm } from '@/components/users'
import { PageLoading, ErrorState } from '@/components/shared'
import { useUsersStore, type UserFormData } from '@/stores/users'
import { ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'

export default function EditUserPage() {
  const params = useParams()
  const navigate = useNavigate()
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
      toast.success('User updated successfully')
      navigate(`/users/${id}`)
    } catch {
      toast.error('Error during update')
    }
  }

  if (isLoadingUser) {
    return <PageLoading text="Loading..." />
  }

  if (error || !selectedUser) {
    return (
      <ErrorState
        title="User not found"
        description={error || "This user does not exist."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  return (
    <>
      <Header
        title={`Edit ${selectedUser.full_name || selectedUser.email}`}
        description="Edit user information"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link to={`/users/${id}`}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to details
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
