import { create } from 'zustand'
import { api } from '@/lib/api'

export type ImportType = 'workitems' | 'suppliers' | 'invoices' | 'risks' | 'governance'

export interface ColumnMapping {
  sourceColumn: string
  targetField: string
  required: boolean
  detected: boolean
}

export interface PreviewRow {
  row_number: number
  data: Record<string, string>
  errors: string[]
  warnings: string[]
}

export interface ImportPreview {
  total_rows: number
  valid_rows: number
  error_rows: number
  columns: string[]
  suggested_mapping: ColumnMapping[]
  preview_data: PreviewRow[]
}

export interface ImportProgress {
  status: 'idle' | 'uploading' | 'previewing' | 'importing' | 'completed' | 'error'
  progress: number
  total: number
  processed: number
  errors: number
  message: string
}

export interface ImportResult {
  success: boolean
  imported: number
  skipped: number
  errors: Array<{ row: number; message: string }>
}

export interface SheetInfo {
  name: string
  columns: number
  rows: number
  importable: boolean
}

interface ImportState {
  // Current import
  importType: ImportType | null
  file: File | null
  tempFile: string | null
  preview: ImportPreview | null
  mapping: ColumnMapping[]
  progress: ImportProgress

  // Sheet selection
  sheets: string[]
  sheetInfo: SheetInfo[]
  selectedSheet: string | null
  selectedSheets: string[]

  // Async job
  jobId: string | null

  // Loading states
  isUploading: boolean
  isPreviewing: boolean
  isImporting: boolean
  error: string | null

  // Actions
  setImportType: (type: ImportType) => void
  setFile: (file: File | null) => void
  setSelectedSheet: (sheet: string) => void
  setSelectedSheets: (sheets: string[]) => void
  toggleAllImportable: () => void
  uploadAndPreview: () => Promise<void>
  updateMapping: (index: number, targetField: string) => void
  confirmImport: () => Promise<ImportResult>
  downloadTemplate: (type: ImportType) => Promise<void>
  exportData: (type: ImportType) => Promise<void>
  reset: () => void
  clearError: () => void
}

const initialProgress: ImportProgress = {
  status: 'idle',
  progress: 0,
  total: 0,
  processed: 0,
  errors: 0,
  message: '',
}

// Target fields per import type - aligned with backend getExpectedColumns()
export const targetFields: Record<ImportType, Array<{ field: string; label: string; required: boolean }>> = {
  workitems: [
    { field: 'ref_no', label: 'Reference', required: true },
    { field: 'type', label: 'Type', required: false },
    { field: 'activity', label: 'Activity', required: false },
    { field: 'department', label: 'Department', required: true },
    { field: 'description', label: 'Description', required: true },
    { field: 'goal', label: 'Goal', required: false },
    { field: 'bau_or_transformative', label: 'BAU / Transformative', required: false },
    { field: 'impact_level', label: 'Impact Level', required: false },
    { field: 'current_status', label: 'Status', required: false },
    { field: 'rag_status', label: 'RAG Status', required: false },
    { field: 'deadline', label: 'Deadline', required: false },
    { field: 'completion_date', label: 'Completion Date', required: false },
    { field: 'monthly_update', label: 'Monthly Update', required: false },
    { field: 'comments', label: 'Comments', required: false },
    { field: 'update_frequency', label: 'Update Frequency', required: false },
    { field: 'responsible_party_id', label: 'Responsible Party', required: false },
    { field: 'department_head_id', label: 'Department Head', required: false },
    { field: 'tags', label: 'Tags', required: false },
    { field: 'priority_item', label: 'Priority', required: false },
    { field: 'cost_savings', label: 'Cost Savings', required: false },
    { field: 'cost_efficiency_fte', label: 'FTE Efficiency', required: false },
    { field: 'expected_cost', label: 'Expected Cost', required: false },
    { field: 'revenue_potential', label: 'Revenue Potential', required: false },
  ],
  suppliers: [
    { field: 'ref_no', label: 'Reference', required: true },
    { field: 'name', label: 'Name', required: true },
    { field: 'sage_category_id', label: 'Sage Category', required: false },
    { field: 'location', label: 'Location', required: false },
    { field: 'is_common_provider', label: 'Common Provider', required: false },
    { field: 'status', label: 'Status', required: false },
    { field: 'entities', label: 'Entities', required: false },
    { field: 'notes', label: 'Notes', required: false },
  ],
  invoices: [
    { field: 'supplier_ref', label: 'Supplier Ref', required: true },
    { field: 'invoice_ref', label: 'Invoice Ref', required: true },
    { field: 'description', label: 'Description', required: false },
    { field: 'amount', label: 'Amount', required: true },
    { field: 'currency', label: 'Currency', required: false },
    { field: 'invoice_date', label: 'Invoice Date', required: true },
    { field: 'due_date', label: 'Due Date', required: false },
    { field: 'frequency', label: 'Frequency', required: false },
    { field: 'status', label: 'Status', required: false },
  ],
  risks: [
    { field: 'ref_no', label: 'Reference', required: true },
    { field: 'theme_code', label: 'Theme Code (L1)', required: true },
    { field: 'category_code', label: 'Category Code (L2)', required: true },
    { field: 'name', label: 'Name', required: true },
    { field: 'description', label: 'Description', required: false },
    { field: 'tier', label: 'Tier', required: false },
    { field: 'owner_id', label: 'Owner', required: false },
    { field: 'responsible_party_id', label: 'Responsible Party', required: false },
    { field: 'financial_impact', label: 'Financial Impact (1-5)', required: false },
    { field: 'regulatory_impact', label: 'Regulatory Impact (1-5)', required: false },
    { field: 'reputational_impact', label: 'Reputational Impact (1-5)', required: false },
    { field: 'inherent_probability', label: 'Inherent Probability (1-5)', required: false },
  ],
  governance: [
    { field: 'ref_no', label: 'Reference', required: true },
    { field: 'activity', label: 'Activity', required: false },
    { field: 'description', label: 'Description', required: false },
    { field: 'department', label: 'Department', required: true },
    { field: 'frequency', label: 'Frequency', required: false },
    { field: 'location', label: 'Location', required: false },
    { field: 'current_status', label: 'Status', required: false },
    { field: 'deadline', label: 'Deadline', required: false },
    { field: 'responsible_party_id', label: 'Responsible Party', required: false },
    { field: 'tags', label: 'Tags', required: false },
  ],
}

export const useImportStore = create<ImportState>((set, get) => ({
  // Initial state
  importType: null,
  file: null,
  tempFile: null,
  preview: null,
  mapping: [],
  progress: { ...initialProgress },
  sheets: [],
  sheetInfo: [],
  selectedSheet: null,
  selectedSheets: [],
  jobId: null,
  isUploading: false,
  isPreviewing: false,
  isImporting: false,
  error: null,

  setImportType: (type) => {
    set({ importType: type, file: null, tempFile: null, preview: null, mapping: [], sheets: [], sheetInfo: [], selectedSheet: null, selectedSheets: [], jobId: null, error: null })
  },

  setFile: (file) => {
    set({ file, tempFile: null, preview: null, mapping: [], sheets: [], sheetInfo: [], selectedSheet: null, selectedSheets: [] })
  },

  setSelectedSheet: (sheet) => {
    set({ selectedSheet: sheet, preview: null, mapping: [] })
    // Re-trigger preview with selected sheet
    get().uploadAndPreview()
  },

  setSelectedSheets: (sheets) => {
    set({ selectedSheets: sheets })
  },

  toggleAllImportable: () => {
    const { sheetInfo, selectedSheets } = get()
    const importable = sheetInfo.filter((s) => s.importable).map((s) => s.name)
    const allSelected = importable.every((s) => selectedSheets.includes(s))
    set({ selectedSheets: allSelected ? [] : importable })
  },

  uploadAndPreview: async () => {
    const { file, importType, selectedSheet } = get()
    if (!file || !importType) return

    set({
      isUploading: true,
      isPreviewing: true,
      error: null,
      progress: { ...initialProgress, status: 'uploading', message: 'Uploading file...' },
    })

    try {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('type', importType)
      if (selectedSheet) {
        formData.append('sheet_name', selectedSheet)
      }

      const response = await api.post('/import/preview', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      const data = response.data

      // Extract sheets, sheet info, and selected sheet from response
      const sheets = data.sheets || []
      const sheetInfo: SheetInfo[] = data.sheet_info || []
      const respSelectedSheet = data.selected_sheet || null

      // Build mapping from backend column_mapping
      const headers = data.headers || []
      const backendMapping = data.column_mapping || {}

      const mapping: ColumnMapping[] = headers.map((col: string, index: number) => {
        const targetField = backendMapping[index] || ''
        const fieldDef = targetFields[importType]?.find((f) => f.field === targetField)
        return {
          sourceColumn: col || `Column ${index + 1}`,
          targetField,
          required: fieldDef?.required || false,
          detected: !!targetField,
        }
      }).filter((m: ColumnMapping) => m.sourceColumn !== null)

      const preview: ImportPreview = {
        total_rows: data.total_rows,
        valid_rows: data.total_rows - (data.validation_errors?.length || 0),
        error_rows: data.validation_errors?.length || 0,
        columns: headers,
        suggested_mapping: mapping,
        preview_data: (data.preview_rows || []).map((row: unknown[], i: number) => ({
          row_number: i + 2,
          data: Object.fromEntries(headers.map((h: string, j: number) => [h, row[j]])),
          errors: [],
          warnings: [],
        })),
      }

      // Auto-select importable sheets
      const importableSheets = sheetInfo.filter((s) => s.importable).map((s) => s.name)

      set({
        tempFile: data.temp_file || null,
        preview,
        mapping,
        sheets,
        sheetInfo,
        selectedSheet: respSelectedSheet,
        selectedSheets: importableSheets.length > 0 ? importableSheets : (respSelectedSheet ? [respSelectedSheet] : []),
        isUploading: false,
        isPreviewing: false,
        progress: { ...initialProgress, status: 'previewing', message: 'Preview ready' },
      })
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Error during preview'
      set({
        error: message,
        isUploading: false,
        isPreviewing: false,
        progress: { ...initialProgress, status: 'error', message },
      })
    }
  },

  updateMapping: (index, targetField) => {
    set((state) => ({
      mapping: state.mapping.map((m, i) =>
        i === index ? { ...m, targetField, detected: true } : m
      ),
    }))
  },

  confirmImport: async () => {
    const { tempFile, importType, mapping, selectedSheets, selectedSheet } = get()
    if (!tempFile || !importType) {
      throw new Error('File or type missing')
    }

    set({
      isImporting: true,
      error: null,
      jobId: null,
      progress: { ...initialProgress, status: 'importing', message: 'Starting import...' },
    })

    try {
      // Build column_mapping as { columnIndex: targetField } for the backend
      const columnMapping: Record<string, string> = {}
      mapping.forEach((m, index) => {
        if (m.targetField) {
          columnMapping[String(index)] = m.targetField
        }
      })

      const payload: Record<string, unknown> = {
        temp_file: tempFile,
        type: importType,
        column_mapping: columnMapping,
      }

      // Send sheet_names array if multiple sheets selected, otherwise single sheet_name
      if (selectedSheets.length > 1) {
        payload.sheet_names = selectedSheets
      } else if (selectedSheets.length === 1) {
        payload.sheet_name = selectedSheets[0]
      } else if (selectedSheet) {
        payload.sheet_name = selectedSheet
      }

      const response = await api.post('/import/confirm', payload)
      const { job_id } = response.data

      if (!job_id) {
        throw new Error('No job_id in response')
      }

      set({ jobId: job_id })

      // Poll for status
      return await new Promise<ImportResult>((resolve, reject) => {
        let pollCount = 0
        const maxPolls = 150 // 5 min at 2s intervals

        const poll = setInterval(async () => {
          pollCount++

          if (pollCount > maxPolls) {
            clearInterval(poll)
            const msg = 'Timeout: import is taking too long'
            set({
              error: msg,
              isImporting: false,
              progress: { ...initialProgress, status: 'error', message: msg },
            })
            reject(new Error(msg))
            return
          }

          try {
            const statusRes = await api.get(`/import/status/${job_id}`)
            const status = statusRes.data

            if (status.status === 'processing') {
              set({
                progress: {
                  status: 'importing',
                  progress: status.percentage || 0,
                  total: status.total || 0,
                  processed: status.processed || 0,
                  errors: 0,
                  message: status.message || 'Importing...',
                },
              })
            } else if (status.status === 'completed') {
              clearInterval(poll)
              const results = status.results || {}
              const importResult: ImportResult = {
                success: true,
                imported: (results.created || 0) + (results.updated || 0),
                skipped: results.skipped || 0,
                errors: (results.errors || []).map((e: string, i: number) => ({
                  row: i + 1,
                  message: e,
                })),
              }
              set({
                isImporting: false,
                progress: {
                  status: 'completed',
                  progress: 100,
                  total: results.total || 0,
                  processed: (results.created || 0) + (results.updated || 0),
                  errors: results.errors?.length || 0,
                  message: `Import completed: ${results.created || 0} created, ${results.updated || 0} updated, ${results.skipped || 0} skipped`,
                },
              })
              resolve(importResult)
            } else if (status.status === 'failed') {
              clearInterval(poll)
              const msg = status.message || 'Import failed'
              set({
                error: msg,
                isImporting: false,
                progress: { ...initialProgress, status: 'error', message: msg },
              })
              reject(new Error(msg))
            }
            // status === 'unknown' -> job not started yet, keep polling
          } catch {
            // Network error during polling - keep trying
          }
        }, 2000)
      })
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Error during import'
      set({
        error: message,
        isImporting: false,
        progress: { ...initialProgress, status: 'error', message },
      })
      throw error
    }
  },

  downloadTemplate: async (type) => {
    try {
      const response = await api.get(`/import/templates/${type}`, {
        responseType: 'blob',
      })

      const blob = new Blob([response.data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${type}_template.xlsx`
      link.click()
      window.URL.revokeObjectURL(url)
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Error downloading template'
      set({ error: message })
    }
  },

  exportData: async (type) => {
    try {
      const response = await api.get(`/export/${type}`, {
        responseType: 'blob',
      })

      const blob = new Blob([response.data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${type}_export_${new Date().toISOString().split('T')[0]}.xlsx`
      link.click()
      window.URL.revokeObjectURL(url)
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Error during export'
      set({ error: message })
    }
  },

  reset: () => {
    set({
      importType: null,
      file: null,
      tempFile: null,
      preview: null,
      mapping: [],
      progress: { ...initialProgress },
      sheets: [],
      sheetInfo: [],
      selectedSheet: null,
      selectedSheets: [],
      jobId: null,
      isUploading: false,
      isPreviewing: false,
      isImporting: false,
      error: null,
    })
  },

  clearError: () => set({ error: null }),
}))
