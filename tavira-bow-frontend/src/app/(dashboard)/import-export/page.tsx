'use client'

import { useState } from 'react'
import {
  Upload,
  Download,
  FileSpreadsheet,
  ArrowRight,
  RotateCcw,
  CheckCircle,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { toast } from 'sonner'
import { FileUpload, ColumnMapper, PreviewTable, ImportProgress } from '@/components/import-export'
import { useImportStore, type ImportType, targetFields } from '@/stores/import'

const importTypes: Array<{ value: ImportType; label: string; description: string }> = [
  {
    value: 'workitems',
    label: 'Work Items',
    description: 'Taches et projets du Book of Work',
  },
  {
    value: 'suppliers',
    label: 'Fournisseurs',
    description: 'Liste des fournisseurs',
  },
  {
    value: 'invoices',
    label: 'Factures',
    description: 'Factures fournisseurs (import bulk)',
  },
  {
    value: 'risks',
    label: 'Risques',
    description: 'Registre des risques (L3)',
  },
  {
    value: 'governance',
    label: 'Governance',
    description: 'Items de gouvernance',
  },
]

export default function ImportExportPage() {
  const [activeTab, setActiveTab] = useState<'import' | 'export'>('import')

  const {
    importType,
    file,
    preview,
    mapping,
    progress,
    isUploading,
    isPreviewing,
    isImporting,
    setImportType,
    setFile,
    uploadAndPreview,
    updateMapping,
    confirmImport,
    downloadTemplate,
    exportData,
    reset,
  } = useImportStore()

  const handleImport = async () => {
    try {
      const result = await confirmImport()
      if (result.success) {
        toast.success(`Import termine: ${result.imported} elements importes`)
      }
    } catch {
      toast.error('Erreur lors de l\'import')
    }
  }

  const handleExport = async (type: ImportType) => {
    try {
      await exportData(type)
      toast.success(`Export ${type} telecharge`)
    } catch {
      toast.error('Erreur lors de l\'export')
    }
  }

  const handleDownloadTemplate = async (type: ImportType) => {
    try {
      await downloadTemplate(type)
      toast.success('Template telecharge')
    } catch {
      toast.error('Erreur lors du telechargement du template')
    }
  }

  const canPreview = importType && file && !preview
  const canImport = preview && mapping.some((m) => m.targetField)
  const requiredFields = importType ? targetFields[importType].filter((f) => f.required) : []
  const mappedRequired = requiredFields.filter((f) =>
    mapping.some((m) => m.targetField === f.field)
  )
  const allRequiredMapped = mappedRequired.length === requiredFields.length

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Import / Export</h1>
        <p className="text-muted-foreground">
          Importez vos donnees depuis des fichiers CSV/Excel ou exportez vos donnees
        </p>
      </div>

      <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as 'import' | 'export')}>
        <TabsList className="grid w-full max-w-md grid-cols-2">
          <TabsTrigger value="import" className="gap-2">
            <Upload className="h-4 w-4" />
            Import
          </TabsTrigger>
          <TabsTrigger value="export" className="gap-2">
            <Download className="h-4 w-4" />
            Export
          </TabsTrigger>
        </TabsList>

        <TabsContent value="import" className="space-y-6 mt-6">
          {/* Step 1: Select type */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <span className="flex items-center justify-center w-6 h-6 rounded-full bg-primary text-primary-foreground text-sm">
                  1
                </span>
                Type de donnees
              </CardTitle>
              <CardDescription>
                Selectionnez le type de donnees que vous souhaitez importer
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {importTypes.map((type) => (
                  <button
                    key={type.value}
                    onClick={() => {
                      reset()
                      setImportType(type.value)
                    }}
                    className={`p-4 border rounded-lg text-left transition-colors hover:border-primary ${
                      importType === type.value
                        ? 'border-primary bg-primary/5'
                        : 'border-border'
                    }`}
                  >
                    <div className="flex items-center gap-2 mb-1">
                      <FileSpreadsheet className="h-4 w-4" />
                      <span className="font-medium">{type.label}</span>
                    </div>
                    <p className="text-sm text-muted-foreground">{type.description}</p>
                  </button>
                ))}
              </div>

              {importType && (
                <div className="mt-4 flex items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleDownloadTemplate(importType)}
                  >
                    <Download className="h-4 w-4 mr-2" />
                    Telecharger le template
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Step 2: Upload file */}
          {importType && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <span className="flex items-center justify-center w-6 h-6 rounded-full bg-primary text-primary-foreground text-sm">
                    2
                  </span>
                  Fichier source
                </CardTitle>
                <CardDescription>
                  Selectionnez votre fichier CSV ou Excel a importer
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <FileUpload
                  file={file}
                  onFileChange={setFile}
                  disabled={isUploading || isPreviewing}
                />

                {canPreview && (
                  <Button onClick={uploadAndPreview} disabled={isUploading || isPreviewing}>
                    {isUploading || isPreviewing ? (
                      <>Analyse en cours...</>
                    ) : (
                      <>
                        Analyser le fichier
                        <ArrowRight className="h-4 w-4 ml-2" />
                      </>
                    )}
                  </Button>
                )}

                <ImportProgress progress={progress} />
              </CardContent>
            </Card>
          )}

          {/* Step 3: Column mapping */}
          {preview && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <span className="flex items-center justify-center w-6 h-6 rounded-full bg-primary text-primary-foreground text-sm">
                    3
                  </span>
                  Mapping des colonnes
                </CardTitle>
                <CardDescription>
                  Associez les colonnes de votre fichier aux champs du systeme
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ColumnMapper
                  importType={importType!}
                  mapping={mapping}
                  onUpdateMapping={updateMapping}
                />
              </CardContent>
            </Card>
          )}

          {/* Step 4: Preview data */}
          {preview && mapping.some((m) => m.targetField) && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <span className="flex items-center justify-center w-6 h-6 rounded-full bg-primary text-primary-foreground text-sm">
                    4
                  </span>
                  Apercu et validation
                </CardTitle>
                <CardDescription>
                  Verifiez les donnees avant de lancer l&apos;import
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <PreviewTable preview={preview} mapping={mapping} />

                <div className="flex items-center justify-between pt-4 border-t">
                  <Button variant="outline" onClick={reset}>
                    <RotateCcw className="h-4 w-4 mr-2" />
                    Recommencer
                  </Button>

                  <Button
                    onClick={handleImport}
                    disabled={isImporting || !canImport || !allRequiredMapped}
                  >
                    {isImporting ? (
                      <>Import en cours...</>
                    ) : (
                      <>
                        <CheckCircle className="h-4 w-4 mr-2" />
                        Lancer l&apos;import ({preview.valid_rows} lignes)
                      </>
                    )}
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="export" className="space-y-6 mt-6">
          <Card>
            <CardHeader>
              <CardTitle>Exporter vos donnees</CardTitle>
              <CardDescription>
                Telechargez vos donnees au format Excel
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {importTypes.map((type) => (
                  <Card key={type.value} className="p-4">
                    <div className="flex items-center gap-2 mb-2">
                      <FileSpreadsheet className="h-5 w-5 text-primary" />
                      <span className="font-medium">{type.label}</span>
                    </div>
                    <p className="text-sm text-muted-foreground mb-4">
                      {type.description}
                    </p>
                    <Button
                      variant="outline"
                      className="w-full"
                      onClick={() => handleExport(type.value)}
                    >
                      <Download className="h-4 w-4 mr-2" />
                      Exporter
                    </Button>
                  </Card>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
