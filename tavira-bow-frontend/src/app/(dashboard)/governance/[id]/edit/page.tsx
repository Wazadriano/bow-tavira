'use client'

import { useEffect } from 'react'
import { useParams } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { GovernanceForm } from '@/components/governance/governance-form'
import { PageLoading, ErrorState } from '@/components/shared'
import { useGovernanceStore } from '@/stores/governance'
import { ArrowLeft } from 'lucide-react'

export default function EditGovernancePage() {
  const params = useParams()
  const id = Number(params.id)

  const { selectedItem, isLoadingItem, error, fetchById } = useGovernanceStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
    }
  }, [id, fetchById])

  if (isLoadingItem) {
    return <PageLoading text="Chargement..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Element introuvable"
        description={error || "Cet element n'existe pas."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  return (
    <>
      <Header
        title={`Modifier: ${selectedItem.activity}`}
        description="Modifier les informations de cet element"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href={`/governance/${id}`}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour au detail
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-3xl">
          <GovernanceForm item={selectedItem} mode="edit" />
        </div>
      </div>
    </>
  )
}
