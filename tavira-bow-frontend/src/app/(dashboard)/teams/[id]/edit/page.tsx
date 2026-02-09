'use client'

import { useEffect } from 'react'
import { useParams } from 'next/navigation'
import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { TeamForm } from '@/components/teams/team-form'
import { PageLoading, ErrorState } from '@/components/shared'
import { useTeamsStore } from '@/stores/teams'
import { ArrowLeft } from 'lucide-react'

export default function EditTeamPage() {
  const params = useParams()
  const id = Number(params.id)

  const { selectedItem, isLoadingItem, error, fetchById } = useTeamsStore()

  useEffect(() => {
    if (id) {
      fetchById(id)
    }
  }, [id, fetchById])

  if (isLoadingItem) {
    return <PageLoading text="Chargement de l'equipe..." />
  }

  if (error || !selectedItem) {
    return (
      <ErrorState
        title="Equipe introuvable"
        description={error || "Cette equipe n'existe pas."}
        onRetry={() => fetchById(id)}
      />
    )
  }

  return (
    <>
      <Header
        title={`Modifier: ${selectedItem.name}`}
        description="Modifier les informations de l'equipe"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href={`/teams/${id}`}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour au detail
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-2xl">
          <TeamForm team={selectedItem} mode="edit" />
        </div>
      </div>
    </>
  )
}
