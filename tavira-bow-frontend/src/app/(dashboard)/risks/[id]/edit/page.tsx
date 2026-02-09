'use client'

import { useEffect } from 'react'
import { useParams } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { RiskForm } from '@/components/risks/risk-form'
import { PageLoading, ErrorState } from '@/components/shared'
import { useRisksStore } from '@/stores/risks'
import { ArrowLeft } from 'lucide-react'

export default function EditRiskPage() {
  const params = useParams()
  const id = Number(params.id)

  const { selectedItem, isLoadingItem, error, fetchById } = useRisksStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
    }
  }, [id, fetchById])

  if (isLoadingItem) {
    return <PageLoading text="Chargement du risque..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Risque introuvable"
        description={error || "Ce risque n'existe pas."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  return (
    <>
      <Header
        title={`Modifier: ${selectedItem.ref_no}`}
        description="Modifier les informations du risque"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href={`/risks/${id}`}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour au detail
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-3xl">
          <RiskForm risk={selectedItem} mode="edit" />
        </div>
      </div>
    </>
  )
}
