import { Link } from 'react-router-dom'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { SupplierForm } from '@/components/suppliers/supplier-form'
import { ArrowLeft } from 'lucide-react'

export default function NewSupplierPage() {
  return (
    <>
      <Header
        title="New Supplier"
        description="Add a new supplier"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link to="/suppliers">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to list
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
