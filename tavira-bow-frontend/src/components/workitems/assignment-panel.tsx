import { useState, useEffect } from 'react'
import { UserPlus, X, Loader2, User, CheckCircle2, Clock } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { useWorkItemsStore } from '@/stores/workitems'
import { useUsersStore } from '@/stores/users'
import { useAuthUser } from '@/stores/auth'
import { toast } from 'sonner'
import type { TaskAssignment } from '@/types'

interface AssignmentPanelProps {
  workItemId: number
  currentAssignments: TaskAssignment[]
  onAssignmentsUpdated?: () => void
}

export function AssignmentPanel({
  workItemId,
  currentAssignments,
  onAssignmentsUpdated,
}: AssignmentPanelProps) {
  const [isOpen, setIsOpen] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [acknowledging, setAcknowledging] = useState<number | null>(null)
  const [selectedUserId, setSelectedUserId] = useState<string>('')
  const [assignmentType, setAssignmentType] = useState<'owner' | 'member'>('member')

  const { assignUser, unassignUser, acknowledgeAssignment } = useWorkItemsStore()
  const { users, fetchUsers } = useUsersStore()
  const currentUser = useAuthUser()

  const assignedUserIds = currentAssignments.map((a) => a.user_id)
  const availableUsers = users.filter((u) => !assignedUserIds.includes(u.id))

  useEffect(() => {
    if (isOpen && users.length === 0) {
      fetchUsers(1)
    }
  }, [isOpen, users.length, fetchUsers])

  const handleAddAssignment = async () => {
    if (!selectedUserId) return
    setIsLoading(true)
    try {
      await assignUser(workItemId, Number(selectedUserId), assignmentType)
      toast.success('User assigned')
      setSelectedUserId('')
      setIsOpen(false)
      onAssignmentsUpdated?.()
    } catch {
      toast.error('Error assigning user')
    } finally {
      setIsLoading(false)
    }
  }

  const handleRemoveAssignment = async (userId: number) => {
    try {
      await unassignUser(workItemId, userId)
      toast.success('User unassigned')
      onAssignmentsUpdated?.()
    } catch {
      toast.error('Error unassigning user')
    }
  }

  const handleAcknowledge = async (assignmentId: number) => {
    setAcknowledging(assignmentId)
    try {
      await acknowledgeAssignment(assignmentId)
      toast.success('Assignment acknowledged')
      onAssignmentsUpdated?.()
    } catch {
      toast.error('Error acknowledging assignment')
    } finally {
      setAcknowledging(null)
    }
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="flex items-center gap-2">
          <User className="h-5 w-5" />
          Assignments ({currentAssignments.length})
        </CardTitle>
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
          <DialogTrigger asChild>
            <Button size="sm" variant="outline">
              <UserPlus className="h-4 w-4 mr-2" />
              Add
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Assign user</DialogTitle>
              <DialogDescription>Add an owner or member to this work item</DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">User</label>
                <Select value={selectedUserId} onValueChange={setSelectedUserId}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a user" />
                  </SelectTrigger>
                  <SelectContent>
                    {availableUsers.map((user) => (
                      <SelectItem key={user.id} value={String(user.id)}>
                        {user.full_name ?? user.email}
                      </SelectItem>
                    ))}
                    {availableUsers.length === 0 && (
                      <SelectItem value="_none" disabled>
                        No users available (all assigned)
                      </SelectItem>
                    )}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Role</label>
                <Select value={assignmentType} onValueChange={(v) => setAssignmentType(v as 'owner' | 'member')}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="owner">Owner</SelectItem>
                    <SelectItem value="member">Member</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="flex justify-end gap-2">
                <Button variant="outline" onClick={() => setIsOpen(false)}>
                  Cancel
                </Button>
                <Button
                  disabled={!selectedUserId || selectedUserId === '_none' || isLoading}
                  onClick={handleAddAssignment}
                >
                  {isLoading && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                  Add
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </CardHeader>
      <CardContent>
        {currentAssignments.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-4">
            No users assigned
          </p>
        ) : (
          <div className="space-y-2">
            {currentAssignments.map((a) => (
              <div
                key={a.id}
                className="flex items-center justify-between rounded-lg border p-3"
              >
                <div className="flex items-center gap-2">
                  <User className="h-4 w-4 text-muted-foreground" />
                  <span className="font-medium">
                    {a.user?.full_name ?? a.user?.email ?? `User #${a.user_id}`}
                  </span>
                  <Badge variant={a.assignment_type === 'owner' ? 'default' : 'secondary'}>
                    {a.assignment_type}
                  </Badge>
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        {a.acknowledged_at ? (
                          <CheckCircle2 className="h-4 w-4 text-green-600" />
                        ) : (
                          <Clock className="h-4 w-4 text-amber-500" />
                        )}
                      </TooltipTrigger>
                      <TooltipContent>
                        {a.acknowledged_at
                          ? `Acknowledged: ${new Date(a.acknowledged_at).toLocaleDateString()}`
                          : 'Pending acknowledgement'}
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
                <div className="flex items-center gap-1">
                  {currentUser?.id === a.user_id && !a.acknowledged_at && (
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={acknowledging === a.id}
                      onClick={() => handleAcknowledge(a.id)}
                    >
                      {acknowledging === a.id ? (
                        <Loader2 className="h-4 w-4 mr-1 animate-spin" />
                      ) : (
                        <CheckCircle2 className="h-4 w-4 mr-1" />
                      )}
                      Acknowledge
                    </Button>
                  )}
                  <Button
                    variant="ghost"
                    size="icon"
                    className="text-destructive hover:text-destructive"
                    onClick={() => handleRemoveAssignment(a.user_id)}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
