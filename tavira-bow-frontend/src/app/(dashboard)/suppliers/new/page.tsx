'use client'

import Link from 'next/link'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { SupplierForm } from '@/components/suppliers/supplier-form'
import { ArrowLeft } from 'lucide-react'

export default function NewSupplierPage() {
  return (
    <>
      <Header
        title="Nouveau fournisseur"
        description="Ajouter un nouveau fournisseur"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link href="/suppliers">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Retour a la liste
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-3xl">
          <SupplierForm mode="create" />
        </div>
      </div>
    </>
  )
}
