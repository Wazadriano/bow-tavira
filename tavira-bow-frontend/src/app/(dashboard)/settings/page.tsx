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
import { usePermissions } from '@/hooks/use-permissions'
import { useNavigate } from 'react-router-dom'

const LIST_TYPES = [
  { value: 'department' as const, label: 'Departments' },
  { value: 'activity' as const, label: 'Activities' },
  { value: 'entity' as const, label: 'Entities' },
  { value: 'vendor_category' as const, label: 'Supplier Categories' },
]

export default function SettingsPage() {
  const { isAdmin } = usePermissions()
  const navigate = useNavigate()
  const [activeTab, setActiveTab] = useState('lists')
  const [selectedType, setSelectedType] = useState<'department' | 'activity' | 'entity' | 'vendor_category'>('department')
  const [editingId, setEditingId] = useState<number | null>(null)
  const [editValue, setEditValue] = useState('')
  const [editLabel, setEditLabel] = useState('')
  const [newValue, setNewValue] = useState('')
  const [newLabel, setNewLabel] = useState('')
  const [modifiedSettings, setModifiedSettings] = useState<Record<string, string>>({})

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
    if (!isAdmin) {
      navigate('/dashboard', { replace: true })
      return
    }
    fetchLists(selectedType)
    fetchSystemSettings()
  }, [fetchLists, fetchSystemSettings, selectedType, isAdmin, navigate])

  const filteredLists = lists.filter((item) => item.type === selectedType)

  const handleCreate = async () => {
    if (!newValue.trim() || !newLabel.trim()) {
      toast.error('Value and label required')
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
      toast.success('Item added')
    } catch {
      toast.error('Error during creation')
    }
  }

  const handleUpdate = async (id: number) => {
    if (!editValue.trim() || !editLabel.trim()) {
      toast.error('Value and label required')
      return
    }
    try {
      await updateList(id, { value: editValue.trim(), label: editLabel.trim() })
      setEditingId(null)
      toast.success('Item updated')
    } catch {
      toast.error('Error during update')
    }
  }

  const handleDelete = (id: number, label: string) => {
    showConfirm({
      title: 'Delete this item',
      description: `Do you really want to delete "${label}"? This action is irreversible.`,
      variant: 'destructive',
      onConfirm: async () => {
        try {
          await deleteList(id)
          toast.success('Item deleted')
        } catch {
          toast.error('Error during deletion')
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
    return <PageLoading text="Loading settings..." />
  }

  return (
    <>
      <Header
        title="Settings"
        description="Application configuration"
      />

      <div className="p-6">
        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList>
            <TabsTrigger value="lists" className="flex items-center gap-2">
              <List className="h-4 w-4" />
              Lists
            </TabsTrigger>
            <TabsTrigger value="system" className="flex items-center gap-2">
              <Settings className="h-4 w-4" />
              System
            </TabsTrigger>
          </TabsList>

          <TabsContent value="lists" className="mt-6">
            <div className="grid gap-6 lg:grid-cols-4">
              {/* Type selector */}
              <Card>
                <CardHeader>
                  <CardTitle>List Types</CardTitle>
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
                      Manage values for this list
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    {/* Add new */}
                    <div className="mb-4 flex gap-2">
                      <Input
                        placeholder="Value (code)"
                        value={newValue}
                        onChange={(e) => setNewValue(e.target.value)}
                        className="max-w-[150px]"
                      />
                      <Input
                        placeholder="Label"
                        value={newLabel}
                        onChange={(e) => setNewLabel(e.target.value)}
                      />
                      <Button onClick={handleCreate} disabled={isSaving}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add
                      </Button>
                    </div>

                    {/* List */}
                    <div className="space-y-2">
                      {filteredLists.length === 0 ? (
                        <p className="py-8 text-center text-muted-foreground">
                          No items in this list
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
                                    {item.is_active ? 'Active' : 'Inactive'}
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
                  System Settings
                </CardTitle>
                <CardDescription>
                  Global application configuration
                </CardDescription>
              </CardHeader>
              <CardContent>
                {systemSettings.length === 0 ? (
                  <p className="py-8 text-center text-muted-foreground">
                    No system settings configured
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
                            value={modifiedSettings[setting.key] ?? setting.value}
                            onChange={(e) => {
                              setModifiedSettings((prev) => ({
                                ...prev,
                                [setting.key]: e.target.value,
                              }))
                            }}
                            className="max-w-[200px]"
                          />
                          <Button
                            size="sm"
                            onClick={async () => {
                              const value = modifiedSettings[setting.key] ?? setting.value
                              await updateSystemSetting(setting.key, value)
                              setModifiedSettings((prev) => {
                                const next = { ...prev }
                                delete next[setting.key]
                                return next
                              })
                              toast.success('Setting updated')
                            }}
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
