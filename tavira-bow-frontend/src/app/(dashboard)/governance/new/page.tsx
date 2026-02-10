'use client'

import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { GovernanceForm } from '@/components/governance/governance-form'
import { ArrowLeft } from 'lucide-react'

export default function NewGovernancePage() {
  return (
    <>
      <Header
        title="New Governance Item"
        description="Add a new governance item"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href="/governance">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to list
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-3xl">
          <GovernanceForm mode="create" />
        </div>
      </div>
    </>
  )
}
