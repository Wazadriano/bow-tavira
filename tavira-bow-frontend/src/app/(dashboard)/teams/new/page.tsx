import { Link } from 'react-router-dom'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { TeamForm } from '@/components/teams/team-form'
import { ArrowLeft } from 'lucide-react'

export default function NewTeamPage() {
  return (
    <>
      <Header
        title="New Team"
        description="Create a new team"
      />

      <div className="p-6">
        <div className="mb-6">
          <Button variant="ghost" asChild>
            <Link to="/teams">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to list
            </Link>
          </Button>
        </div>

        <div className="mx-auto max-w-2xl">
          <TeamForm mode="create" />
        </div>
      </div>
    </>
  )
}
