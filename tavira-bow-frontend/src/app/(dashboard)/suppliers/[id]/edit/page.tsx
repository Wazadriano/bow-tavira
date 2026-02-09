'use client'

import { useEffect } from 'react'
import { useParams } from 'next/navigation'
import Link from 'next/link'
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
    return <PageLoading text="Chargement du fournisseur..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Fournisseur introuvable"
        description={error || "Ce fournisseur n'existe pas."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  return (
    <>
      <Header
        title={`Modifier: ${selectedItem.name}`}
        description="Modifier les informations du fournisseur"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href={`/suppliers/${id}`}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour au detail
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
