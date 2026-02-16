import { useEffect } from 'react'
import { useParams } from 'react-router-dom'
import { Link } from 'react-router-dom'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { SupplierForm } from '@/components/suppliers/supplier-form'
import { PageLoading, ErrorState } from '@/components/shared'
import { useSuppliersStore } from '@/stores/suppliers'
import { ArrowLeft } from 'lucide-react'

export default function EditSupplierPage() {
  const params = useParams()
  const id = Number(params.id)

  const { selectedItem, isLoadingItem, error, fetchById } = useSuppliersStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
    }
  }, [id, fetchById])

  if (isLoadingItem) {
    return <PageLoading text="Loading supplier..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Supplier not found"
        description={error || "This supplier does not exist."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  return (
    <>
      <Header
        title={`Edit: ${selectedItem.name}`}
        description="Edit supplier information"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link to={`/suppliers/${id}`}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to details
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-3xl">
          <SupplierForm supplier={selectedItem} mode="edit" />
        </div>
      </div>
    </>
  )
}
