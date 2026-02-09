'use client'

import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { WorkItemForm } from '@/components/workitems/workitem-form'
import { ArrowLeft } from 'lucide-react'

export default function NewWorkItemPage() {
  return (
    <>
      <Header
        title="Nouvelle tache"
        description="Creer une nouvelle tache dans le Book of Work"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href="/tasks">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour a la liste
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-3xl">
          <WorkItemForm mode="create" />
        </div>
      </div>
    </>
  )
}
