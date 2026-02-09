'use client'

import { useEffect } from 'react'
import { useParams } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { WorkItemForm } from '@/components/workitems/workitem-form'
import { PageLoading, ErrorState } from '@/components/shared'
import { useWorkItemsStore } from '@/stores/workitems'
import { ArrowLeft } from 'lucide-react'

export default function EditWorkItemPage() {
  const params = useParams()
  const id = Number(params.id)

  const { selectedItem, isLoadingItem, error, fetchById } = useWorkItemsStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
    }
  }, [id, fetchById])

  if (isLoadingItem) {
    return <PageLoading text="Chargement de la tache..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Tache introuvable"
        description={error || "Cette tache n'existe pas ou a ete supprimee."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  return (
    <>
      <Header
        title={`Modifier: ${selectedItem.ref_no || `Tache #${selectedItem.id}`}`}
        description="Modifier les informations de cette tache"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href={`/tasks/${id}`}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour au detail
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-3xl">
          <WorkItemForm workItem={selectedItem} mode="edit" />
        </div>
      </div>
    </>
  )
}
