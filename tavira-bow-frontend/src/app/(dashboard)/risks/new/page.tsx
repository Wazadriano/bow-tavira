'use client'

import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { RiskForm } from '@/components/risks/risk-form'
import { ArrowLeft } from 'lucide-react'

export default function NewRiskPage() {
  return (
    <>
      <Header
        title="New Risk"
        description="Register a new risk"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href="/risks">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to list
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-3xl">
          <RiskForm mode="create" />
        </div>
      </div>
    </>
  )
}
