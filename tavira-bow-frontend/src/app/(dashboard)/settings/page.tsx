'use client'

import { useEffect, useState } from 'react'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { PageLoading } from '@/components/shared'
import { useSettingsStore } from '@/stores/settings'
import { useUIStore } from '@/stores/ui'
import {
  Plus,
  Pencil,
  Trash2,
  Save,
  X,
  Settings,
  List,
  Database,
} from 'lucide-react'
import { toast } from 'sonner'

const LIST_TYPES = [
  { value: 'department' as const, label: 'Departements' },
  { value: 'activity' as const, label: 'Activites' },
  { value: 'entity' as const, label: 'Entites' },
  { value: 'vendor_category' as const, label: 'Categories Fournisseurs' },
]

export default function SettingsPage() {
  const [activeTab, setActiveTab] = useState('lists')
  const [selectedType, setSelectedType] = useState<'department' | 'activity' | 'entity' | 'vendor_category'>('department')
  const [editingId, setEditingId] = useState<number | null>(null)
  const [editValue, setEditValue] = useState('')
  const [editLabel, setEditLabel] = useState('')
  const [newValue, setNewValue] = useState('')
  const [newLabel, setNewLabel] = useState('')

  const {
    lists,
    systemSettings,
    isLoading,
    isSaving,
    fetchLists,
    fetchSystemSettings,
    createList,
    updateList,
    deleteList,
    updateSystemSetting,
  } = useSettingsStore()
  const { showConfirm } = useUIStore()

  useEffect(() => {
    fetchLists(selectedType)
    fetchSystemSettings()
  }, [fetchLists, fetchSystemSettings, selectedType])

  const filteredLists = lists.filter((item) => item.type === selectedType)

  const handleCreate = async () => {
    if (!newValue.trim() || !newLabel.trim()) {
      toast.error('Valeur et libelle requis')
      return
    }
    try {
      await createList({
        type: selectedType,
        value: newValue.trim(),
        label: newLabel.trim(),
        is_active: true,
      })
      setNewValue('')
      setNewLabel('')
      toast.success('Element ajoute')
    } catch {
      toast.error('Erreur lors de la creation')
    }
  }

  const handleUpdate = async (id: number) => {
    if (!editValue.trim() || !editLabel.trim()) {
      toast.error('Valeur et libelle requis')
      return
    }
    try {
      await updateList(id, { value: editValue.trim(), label: editLabel.trim() })
      setEditingId(null)
      toast.success('Element modifie')
    } catch {
      toast.error('Erreur lors de la modification')
    }
  }

  const handleDelete = (id: number, label: string) => {
    showConfirm({
      title: 'Supprimer cet element',
      description: `Voulez-vous vraiment supprimer "${label}"? Cette action est irreversible.`,
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await deleteList(id)
          toast.success('Element supprime')
        } catch {
          toast.error('Erreur lors de la suppression')
        }
      },
    })
  }

  const startEditing = (item: { id: number; value: string; label: string }) => {
    setEditingId(item.id)
    setEditValue(item.value)
    setEditLabel(item.label)
  }

  if (isLoading) {
    return <PageLoading text="Chargement des parametres..." />
  }

  return (
    <>
      <Header
        title="Parametres"
        description="Configuration de l'application"
      />

      <div className="p-6">
        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList>
            <TabsTrigger value="lists" className="flex items-center gap-2">
              <List className="h-4 w-4" />
              Listes
            </TabsTrigger>
            <TabsTrigger value="system" className="flex items-center gap-2">
              <Settings className="h-4 w-4" />
              Systeme
            </TabsTrigger>
          </TabsList>

          <TabsContent value="lists" className="mt-6">
            <div className="grid gap-6 lg:grid-cols-4">
              {/* Type selector */}
              <Card>
                <CardHeader>
                  <CardTitle>Types de listes</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    {LIST_TYPES.map((type) => (
                      <Button
                        key={type.value}
                        variant={selectedType === type.value ? 'default' : 'ghost'}
                        className="w-full justify-start"
                        onClick={() => setSelectedType(type.value)}
                      >
                        {type.label}
                        <Badge variant="secondary" className="ml-auto">
                          {lists.filter((l) => l.type === type.value).length}
                        </Badge>
                      </Button>
                    ))}
                  </div>
                </CardContent>
              </Card>

              {/* List items */}
              <div className="lg:col-span-3">
                <Card>
                  <CardHeader>
                    <CardTitle>
                      {LIST_TYPES.find((t) => t.value === selectedType)?.label}
                    </CardTitle>
                    <CardDescription>
                      Gerer les valeurs de cette liste
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    {/* Add new */}
                    <div className="mb-4 flex gap-2">
                      <Input
                        placeholder="Valeur (code)"
                        value={newValue}
                        onChange={(e) => setNewValue(e.target.value)}
                        className="max-w-[150px]"
                      />
                      <Input
                        placeholder="Libelle"
                        value={newLabel}
                        onChange={(e) => setNewLabel(e.target.value)}
                      />
                      <Button onClick={handleCreate} disabled={isSaving}>
                        <Plus className="mr-2 h-4 w-4" />
                        Ajouter
                      </Button>
                    </div>

                    {/* List */}
                    <div className="space-y-2">
                      {filteredLists.length === 0 ? (
                        <p className="py-8 text-center text-muted-foreground">
                          Aucun element dans cette liste
                        </p>
                      ) : (
                        filteredLists.map((item) => (
                          <div
                            key={item.id}
                            className="flex items-center justify-between rounded-lg border p-3"
                          >
                            {editingId === item.id ? (
                              <div className="flex flex-1 items-center gap-2">
                                <Input
                                  value={editValue}
                                  onChange={(e) => setEditValue(e.target.value)}
                                  className="max-w-[150px]"
                                />
                                <Input
                                  value={editLabel}
                                  onChange={(e) => setEditLabel(e.target.value)}
                                />
                                <Button
                                  size="sm"
                                  onClick={() => handleUpdate(item.id)}
                                  disabled={isSaving}
                                >
                                  <Save className="h-4 w-4" />
                                </Button>
                                <Button
                                  size="sm"
                                  variant="ghost"
                                  onClick={() => setEditingId(null)}
                                >
                                  <X className="h-4 w-4" />
                                </Button>
                              </div>
                            ) : (
                              <>
                                <div>
                                  <span className="font-medium">{item.label}</span>
                                  <span className="ml-2 text-sm text-muted-foreground">
                                    ({item.value})
                                  </span>
                                </div>
                                <div className="flex items-center gap-2">
                                  <Badge variant={item.is_active ? 'default' : 'secondary'}>
                                    {item.is_active ? 'Actif' : 'Inactif'}
                                  </Badge>
                                  <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => startEditing(item)}
                                  >
                                    <Pencil className="h-4 w-4" />
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => handleDelete(item.id, item.label)}
                                  >
                                    <Trash2 className="h-4 w-4" />
                                  </Button>
                                </div>
                              </>
                            )}
                          </div>
                        ))
                      )}
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          </TabsContent>

          <TabsContent value="system" className="mt-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Database className="h-5 w-5" />
                  Parametres systeme
                </CardTitle>
                <CardDescription>
                  Configuration globale de l&apos;application
                </CardDescription>
              </CardHeader>
              <CardContent>
                {systemSettings.length === 0 ? (
                  <p className="py-8 text-center text-muted-foreground">
                    Aucun parametre systeme configure
                  </p>
                ) : (
                  <div className="space-y-4">
                    {systemSettings.map((setting) => (
                      <div
                        key={setting.key}
                        className="flex items-center justify-between rounded-lg border p-4"
                      >
                        <div>
                          <p className="font-medium">{setting.key}</p>
                          {setting.description && (
                            <p className="text-sm text-muted-foreground">
                              {setting.description}
                            </p>
                          )}
                        </div>
                        <div className="flex items-center gap-2">
                          <Input
                            value={setting.value}
                            onChange={(e) => {
                              // Update local state handled by store
                            }}
                            className="max-w-[200px]"
                          />
                          <Button
                            size="sm"
                            onClick={() =>
                              updateSystemSetting(setting.key, setting.value)
                            }
                            disabled={isSaving}
                          >
                            <Save className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </>
  )
}
