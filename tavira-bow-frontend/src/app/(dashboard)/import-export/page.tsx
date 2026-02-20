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
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import { toast } from 'sonner'
import { FileUpload, ColumnMapper, PreviewTable, ImportProgress, UserSuggestions, DuplicateWarnings } from '@/components/import-export'
import { useImportStore, type ImportType, targetFields } from '@/stores/import'

const importTypes: Array<{ value: ImportType; label: string; description: string }> = [
  {
    value: 'workitems',
    label: 'Work Items',
    description: 'Tasks and projects from the Book of Work',
  },
  {
    value: 'suppliers',
    label: 'Suppliers',
    description: 'Supplier list',
  },
  {
    value: 'invoices',
    label: 'Invoices',
    description: 'Supplier invoices (bulk import)',
  },
  {
    value: 'risks',
    label: 'Risks',
    description: 'Risk register (L3)',
  },
  {
    value: 'governance',
    label: 'Governance',
    description: 'Governance items',
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
    sheets,
    sheetInfo,
    selectedSheet,
    selectedSheets,
    isUploading,
    isPreviewing,
    isImporting,
    setImportType,
    setFile,
    setSelectedSheet,
    setSelectedSheets,
    toggleAllImportable,
    uploadAndPreview,
    updateMapping,
    userSuggestions,
    userOverrides,
    acceptSuggestion,
    rejectSuggestion,
    duplicates,
    duplicatesAcknowledged,
    acknowledgeDuplicates,
    confirmImport,
    downloadTemplate,
    exportData,
    reset,
  } = useImportStore()

  const handleImport = async () => {
    try {
      const result = await confirmImport()
      if (result.success) {
        toast.success(`Import completed: ${result.imported} items imported`)
      }
    } catch {
      toast.error('Error during import')
    }
  }

  const handleExport = async (type: ImportType) => {
    try {
      await exportData(type)
      toast.success(`Export ${type} downloaded`)
    } catch {
      toast.error('Error during export')
    }
  }

  const handleDownloadTemplate = async (type: ImportType) => {
    try {
      await downloadTemplate(type)
      toast.success('Template downloaded')
    } catch {
      toast.error('Error downloading template')
    }
  }

  const canPreview = importType && file && !preview && sheets.length === 0
  const canImport = preview && mapping.some((m) => m.targetField)
  const requiredFields = importType ? targetFields[importType].filter((f) => f.required) : []
  const mappedRequired = requiredFields.filter((f) =>
    mapping.some((m) => m.targetField === f.field)
  )
  const allRequiredMapped = mappedRequired.length === requiredFields.length

  // Step numbering accounting for sheet selection step
  const hasSheets = sheets.length > 1
  const sheetStepOffset = hasSheets ? 1 : 0

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Import / Export</h1>
        <p className="text-muted-foreground">
          Import your data from CSV/Excel files or export your data
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
                Data Type
              </CardTitle>
              <CardDescription>
                Select the type of data you want to import
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
                    Download Template
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
                  Source File
                </CardTitle>
                <CardDescription>
                  Select your CSV or Excel file to import
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
                      <>Analysing...</>
                    ) : (
                      <>
                        Analyse File
                        <ArrowRight className="h-4 w-4 ml-2" />
                      </>
                    )}
                  </Button>
                )}

                <ImportProgress progress={progress} />
              </CardContent>
            </Card>
          )}

          {/* Step 2.5: Sheet selection (only for multi-sheet Excel files) */}
          {hasSheets && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <span className="flex items-center justify-center w-6 h-6 rounded-full bg-primary text-primary-foreground text-sm">
                    3
                  </span>
                  Sheet Selection
                </CardTitle>
                <CardDescription>
                  The file contains {sheets.length} sheets. Select the ones to import.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="select-all-importable"
                    checked={sheetInfo.filter((s) => s.importable).every((s) => selectedSheets.includes(s.name))}
                    onCheckedChange={() => toggleAllImportable()}
                  />
                  <label htmlFor="select-all-importable" className="text-sm font-medium cursor-pointer">
                    Import all compatible sheets ({sheetInfo.filter((s) => s.importable).length})
                  </label>
                </div>
                <div className="border-t pt-3 space-y-2">
                  {(sheetInfo.length > 0 ? sheetInfo : sheets.map((s) => ({ name: s, columns: 0, rows: 0, importable: true }))).map((sheet) => (
                    <div
                      key={sheet.name}
                      className={`flex items-center justify-between p-3 rounded-lg border ${
                        !sheet.importable ? 'opacity-50 bg-muted' : ''
                      }`}
                    >
                      <div className="flex items-center space-x-3">
                        <Checkbox
                          id={`sheet-${sheet.name}`}
                          disabled={!sheet.importable}
                          checked={selectedSheets.includes(sheet.name)}
                          onCheckedChange={(checked: boolean | "indeterminate") => {
                            if (checked) {
                              setSelectedSheets([...selectedSheets, sheet.name])
                            } else {
                              setSelectedSheets(selectedSheets.filter((s) => s !== sheet.name))
                            }
                          }}
                        />
                        <label htmlFor={`sheet-${sheet.name}`} className={`text-sm cursor-pointer ${!sheet.importable ? 'text-muted-foreground' : ''}`}>
                          {sheet.name}
                          {sheet.name === 'BOW List' && <span className="text-primary ml-1">(primary source)</span>}
                        </label>
                      </div>
                      <div className="flex items-center gap-2">
                        {sheet.rows > 0 && (
                          <Badge variant="secondary" className="text-xs">
                            {sheet.rows} rows
                          </Badge>
                        )}
                        {sheet.columns > 0 && (
                          <Badge variant="outline" className="text-xs">
                            {sheet.columns} cols
                          </Badge>
                        )}
                        {!sheet.importable && (
                          <Badge variant="destructive" className="text-xs">
                            Not importable
                          </Badge>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          )}

          {/* Step 3 (or 4): Column mapping */}
          {preview && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <span className="flex items-center justify-center w-6 h-6 rounded-full bg-primary text-primary-foreground text-sm">
                    {3 + sheetStepOffset}
                  </span>
                  Column Mapping
                </CardTitle>
                <CardDescription>
                  Map the columns from your file to the system fields.
                  {mapping.filter((m) => m.detected).length > 0 && (
                    <span className="ml-1 font-medium">
                      {mapping.filter((m) => m.detected).length}/{mapping.length} columns auto-detected.
                    </span>
                  )}
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

          {/* User suggestions (between mapping and preview) */}
          {preview && userSuggestions.length > 0 && (
            <Card>
              <CardContent className="pt-6">
                <UserSuggestions
                  suggestions={userSuggestions}
                  overrides={userOverrides}
                  onAccept={acceptSuggestion}
                  onReject={rejectSuggestion}
                />
              </CardContent>
            </Card>
          )}

          {/* Duplicate detection warnings */}
          {preview && duplicates.length > 0 && (
            <Card>
              <CardContent className="pt-6">
                <DuplicateWarnings
                  duplicates={duplicates}
                  acknowledged={duplicatesAcknowledged}
                  onAcknowledge={acknowledgeDuplicates}
                />
              </CardContent>
            </Card>
          )}

          {/* Step 4 (or 5): Preview data */}
          {preview && mapping.some((m) => m.targetField) && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <span className="flex items-center justify-center w-6 h-6 rounded-full bg-primary text-primary-foreground text-sm">
                    {4 + sheetStepOffset}
                  </span>
                  Preview and Validation
                </CardTitle>
                <CardDescription>
                  Review the data before starting the import
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <PreviewTable preview={preview} mapping={mapping} />

                <div className="flex items-center justify-between pt-4 border-t">
                  <Button variant="outline" onClick={reset}>
                    <RotateCcw className="h-4 w-4 mr-2" />
                    Start Over
                  </Button>

                  <Button
                    onClick={handleImport}
                    disabled={isImporting || !canImport || !allRequiredMapped || (duplicates.length > 0 && !duplicatesAcknowledged)}
                  >
                    {isImporting ? (
                      <>Import in progress...</>
                    ) : (
                      <>
                        <CheckCircle className="h-4 w-4 mr-2" />
                        Start Import ({preview.total_rows} rows{selectedSheets.length > 1 ? `, ${selectedSheets.length} sheets` : ''})
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
              <CardTitle>Export Your Data</CardTitle>
              <CardDescription>
                Download your data in Excel format
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
                      Export
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
