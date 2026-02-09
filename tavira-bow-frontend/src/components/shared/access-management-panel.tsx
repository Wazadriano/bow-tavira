'use client'

import { useState, useEffect } from 'react'
import { Plus, X, Users, Building2, Loader2, Shield } from 'lucide-react'
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
import { api } from '@/lib/api'
import { toast } from 'sonner'

interface AccessEntry {
  id: number
  department?: string
  entity?: string
  user_id?: number
  user?: {
    id: number
    full_name: string
    email: string
  }
  access_level: 'read' | 'write' | 'admin'
  created_at: string
}

interface AccessManagementPanelProps {
  resourceType: 'governance' | 'supplier'
  resourceId: number
  currentAccess: AccessEntry[]
  onAccessUpdated?: () => void
}

const accessLevelLabels: Record<string, string> = {
  read: 'Lecture',
  write: 'Ecriture',
  admin: 'Admin',
}

const accessLevelColors: Record<string, string> = {
  read: 'bg-blue-100 text-blue-800',
  write: 'bg-green-100 text-green-800',
  admin: 'bg-purple-100 text-purple-800',
}

export function AccessManagementPanel({
  resourceType,
  resourceId,
  currentAccess,
  onAccessUpdated,
}: AccessManagementPanelProps) {
  const [isOpen, setIsOpen] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [departments, setDepartments] = useState<string[]>([])
  const [entities, setEntities] = useState<string[]>([])
  const [selectedDepartment, setSelectedDepartment] = useState<string>('')
  const [selectedEntity, setSelectedEntity] = useState<string>('')
  const [selectedLevel, setSelectedLevel] = useState<string>('read')

  useEffect(() => {
    const fetchOptions = async () => {
      try {
        // Fetch departments or entities based on resource type
        if (resourceType === 'governance') {
          const response = await api.get<{ data: Array<{ name: string }> }>(
            '/settings/lists?type=department'
          )
          setDepartments(response.data.data.map((d) => d.name))
        } else {
          const response = await api.get<{ data: Array<{ name: string }> }>(
            '/settings/lists?type=entity'
          )
          setEntities(response.data.data.map((e) => e.name))
        }
      } catch {
        // Fallback to demo data
        if (resourceType === 'governance') {
          setDepartments(['IT', 'Finance', 'Operations', 'Compliance', 'HR'])
        } else {
          setEntities(['Entity A', 'Entity B', 'Entity C', 'Entity D'])
        }
      }
    }

    if (isOpen) {
      fetchOptions()
    }
  }, [isOpen, resourceType])

  const handleAddAccess = async () => {
    const value = resourceType === 'governance' ? selectedDepartment : selectedEntity
    if (!value) return

    setIsLoading(true)
    try {
      const endpoint =
        resourceType === 'governance'
          ? `/governance/items/${resourceId}/access`
          : `/suppliers/${resourceId}/access`

      const payload =
        resourceType === 'governance'
          ? { department: value, access_level: selectedLevel }
          : { entity: value, access_level: selectedLevel }

      await api.post(endpoint, payload)
      toast.success('Acces ajoute')
      setIsOpen(false)
      setSelectedDepartment('')
      setSelectedEntity('')
      setSelectedLevel('read')
      onAccessUpdated?.()
    } catch {
      toast.error('Erreur lors de l\'ajout de l\'acces')
    } finally {
      setIsLoading(false)
    }
  }

  const handleRemoveAccess = async (accessId: number) => {
    try {
      const endpoint =
        resourceType === 'governance'
          ? `/governance/items/${resourceId}/access/${accessId}`
          : `/suppliers/${resourceId}/access/${accessId}`

      await api.delete(endpoint)
      toast.success('Acces supprime')
      onAccessUpdated?.()
    } catch {
      toast.error('Erreur lors de la suppression')
    }
  }

  const usedValues = currentAccess.map((a) =>
    resourceType === 'governance' ? a.department : a.entity
  )
  const availableOptions =
    resourceType === 'governance'
      ? departments.filter((d) => !usedValues.includes(d))
      : entities.filter((e) => !usedValues.includes(e))

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="flex items-center gap-2">
          <Shield className="h-5 w-5" />
          Gestion des acces
          {currentAccess.length > 0 && (
            <Badge variant="secondary" className="ml-2">
              {currentAccess.length}
            </Badge>
          )}
        </CardTitle>
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
          <DialogTrigger asChild>
            <Button variant="outline" size="sm">
              <Plus className="h-4 w-4 mr-1" />
              Ajouter
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Ajouter un acces</DialogTitle>
              <DialogDescription>
                {resourceType === 'governance'
                  ? 'Autoriser un departement a acceder a cet element de gouvernance'
                  : 'Autoriser une entite a acceder a ce fournisseur'}
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">
                  {resourceType === 'governance' ? 'Departement' : 'Entite'}
                </label>
                <Select
                  value={resourceType === 'governance' ? selectedDepartment : selectedEntity}
                  onValueChange={
                    resourceType === 'governance'
                      ? setSelectedDepartment
                      : setSelectedEntity
                  }
                >
                  <SelectTrigger>
                    <SelectValue
                      placeholder={`Selectionner ${
                        resourceType === 'governance' ? 'un departement' : 'une entite'
                      }...`}
                    />
                  </SelectTrigger>
                  <SelectContent>
                    {availableOptions.map((option) => (
                      <SelectItem key={option} value={option}>
                        <div className="flex items-center gap-2">
                          {resourceType === 'governance' ? (
                            <Users className="h-4 w-4" />
                          ) : (
                            <Building2 className="h-4 w-4" />
                          )}
                          {option}
                        </div>
                      </SelectItem>
                    ))}
                    {availableOptions.length === 0 && (
                      <div className="p-2 text-sm text-muted-foreground text-center">
                        Aucune option disponible
                      </div>
                    )}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">Niveau d&apos;acces</label>
                <Select value={selectedLevel} onValueChange={setSelectedLevel}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="read">
                      <div className="flex items-center gap-2">
                        <div className="h-2 w-2 rounded-full bg-blue-500" />
                        Lecture seule
                      </div>
                    </SelectItem>
                    <SelectItem value="write">
                      <div className="flex items-center gap-2">
                        <div className="h-2 w-2 rounded-full bg-green-500" />
                        Lecture et ecriture
                      </div>
                    </SelectItem>
                    <SelectItem value="admin">
                      <div className="flex items-center gap-2">
                        <div className="h-2 w-2 rounded-full bg-purple-500" />
                        Administration
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="flex justify-end gap-2">
                <Button variant="outline" onClick={() => setIsOpen(false)}>
                  Annuler
                </Button>
                <Button
                  onClick={handleAddAccess}
                  disabled={
                    isLoading ||
                    !(resourceType === 'governance' ? selectedDepartment : selectedEntity)
                  }
                >
                  {isLoading && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                  Ajouter
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </CardHeader>
      <CardContent>
        {currentAccess.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-4">
            Aucun acces configure - acces limite au createur
          </p>
        ) : (
          <div className="space-y-2">
            {currentAccess.map((access) => (
              <div
                key={access.id}
                className="flex items-center justify-between p-3 rounded-lg border bg-muted/30 group"
              >
                <div className="flex items-center gap-3">
                  {resourceType === 'governance' ? (
                    <Users className="h-4 w-4 text-muted-foreground" />
                  ) : (
                    <Building2 className="h-4 w-4 text-muted-foreground" />
                  )}
                  <div>
                    <p className="font-medium">
                      {access.department || access.entity}
                    </p>
                    {access.user && (
                      <p className="text-xs text-muted-foreground">
                        Ajoute par {access.user.full_name}
                      </p>
                    )}
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Badge className={accessLevelColors[access.access_level]}>
                    {accessLevelLabels[access.access_level]}
                  </Badge>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="opacity-0 group-hover:opacity-100 transition-opacity"
                    onClick={() => handleRemoveAccess(access.id)}
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
