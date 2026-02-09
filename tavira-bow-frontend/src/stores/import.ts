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

interface ImportState {
  // Current import
  importType: ImportType | null
  file: File | null
  preview: ImportPreview | null
  mapping: ColumnMapping[]
  progress: ImportProgress

  // Loading states
  isUploading: boolean
  isPreviewing: boolean
  isImporting: boolean
  error: string | null

  // Actions
  setImportType: (type: ImportType) => void
  setFile: (file: File | null) => void
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

// Target fields per import type
export const targetFields: Record<ImportType, Array<{ field: string; label: string; required: boolean }>> = {
  workitems: [
    { field: 'title', label: 'Titre', required: true },
    { field: 'description', label: 'Description', required: false },
    { field: 'type', label: 'Type (BAU/Non-BAU)', required: true },
    { field: 'status', label: 'Statut', required: true },
    { field: 'priority', label: 'Priorite', required: false },
    { field: 'department', label: 'Departement', required: true },
    { field: 'responsible', label: 'Responsable', required: false },
    { field: 'due_date', label: 'Date echeance', required: false },
    { field: 'start_date', label: 'Date debut', required: false },
    { field: 'end_date', label: 'Date fin', required: false },
    { field: 'progress', label: 'Progression (%)', required: false },
  ],
  suppliers: [
    { field: 'name', label: 'Nom', required: true },
    { field: 'code', label: 'Code', required: false },
    { field: 'contact_name', label: 'Contact', required: false },
    { field: 'contact_email', label: 'Email', required: false },
    { field: 'contact_phone', label: 'Telephone', required: false },
    { field: 'address', label: 'Adresse', required: false },
    { field: 'city', label: 'Ville', required: false },
    { field: 'country', label: 'Pays', required: false },
    { field: 'status', label: 'Statut', required: true },
    { field: 'category', label: 'Categorie', required: false },
  ],
  invoices: [
    { field: 'invoice_number', label: 'Numero facture', required: true },
    { field: 'supplier_code', label: 'Code fournisseur', required: true },
    { field: 'amount', label: 'Montant', required: true },
    { field: 'currency', label: 'Devise', required: false },
    { field: 'invoice_date', label: 'Date facture', required: true },
    { field: 'due_date', label: 'Date echeance', required: false },
    { field: 'status', label: 'Statut', required: true },
    { field: 'description', label: 'Description', required: false },
    { field: 'sage_category', label: 'Categorie Sage', required: false },
  ],
  risks: [
    { field: 'code', label: 'Code risque', required: true },
    { field: 'name', label: 'Nom', required: true },
    { field: 'description', label: 'Description', required: false },
    { field: 'theme_code', label: 'Code theme (L1)', required: true },
    { field: 'category_code', label: 'Code categorie (L2)', required: true },
    { field: 'inherent_impact', label: 'Impact inherent (1-5)', required: true },
    { field: 'inherent_likelihood', label: 'Probabilite inherente (1-5)', required: true },
    { field: 'residual_impact', label: 'Impact residuel (1-5)', required: false },
    { field: 'residual_likelihood', label: 'Probabilite residuelle (1-5)', required: false },
    { field: 'owner', label: 'Proprietaire', required: false },
    { field: 'status', label: 'Statut', required: true },
  ],
  governance: [
    { field: 'title', label: 'Titre', required: true },
    { field: 'description', label: 'Description', required: false },
    { field: 'department', label: 'Departement', required: true },
    { field: 'frequency', label: 'Frequence', required: true },
    { field: 'responsible', label: 'Responsable', required: false },
    { field: 'next_review_date', label: 'Prochaine revue', required: false },
    { field: 'status', label: 'Statut', required: true },
  ],
}

export const useImportStore = create<ImportState>((set, get) => ({
  // Initial state
  importType: null,
  file: null,
  preview: null,
  mapping: [],
  progress: { ...initialProgress },
  isUploading: false,
  isPreviewing: false,
  isImporting: false,
  error: null,

  setImportType: (type) => {
    set({ importType: type, file: null, preview: null, mapping: [], error: null })
  },

  setFile: (file) => {
    set({ file, preview: null, mapping: [] })
  },

  uploadAndPreview: async () => {
    const { file, importType } = get()
    if (!file || !importType) return

    set({
      isUploading: true,
      isPreviewing: true,
      error: null,
      progress: { ...initialProgress, status: 'uploading', message: 'Upload du fichier...' },
    })

    try {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('type', importType)

      const response = await api.post<{ data: ImportPreview }>('/import/preview', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      const preview = response.data.data

      // Use suggested mapping or create default
      const mapping = preview.suggested_mapping.length > 0
        ? preview.suggested_mapping
        : preview.columns.map((col) => ({
            sourceColumn: col,
            targetField: '',
            required: false,
            detected: false,
          }))

      set({
        preview,
        mapping,
        isUploading: false,
        isPreviewing: false,
        progress: { ...initialProgress, status: 'previewing', message: 'Apercu pret' },
      })
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Erreur lors du preview'
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
    const { file, importType, mapping } = get()
    if (!file || !importType) {
      throw new Error('Fichier ou type manquant')
    }

    set({
      isImporting: true,
      error: null,
      progress: { ...initialProgress, status: 'importing', message: 'Import en cours...' },
    })

    try {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('type', importType)
      formData.append('mapping', JSON.stringify(mapping))

      const response = await api.post<{ data: ImportResult }>('/import/confirm', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      const result = response.data.data

      set({
        isImporting: false,
        progress: {
          status: 'completed',
          progress: 100,
          total: result.imported + result.skipped,
          processed: result.imported,
          errors: result.errors.length,
          message: `Import termine: ${result.imported} importes, ${result.skipped} ignores`,
        },
      })

      return result
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Erreur lors de l\'import'
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
      const message = error instanceof Error ? error.message : 'Erreur lors du telechargement'
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
      const message = error instanceof Error ? error.message : 'Erreur lors de l\'export'
      set({ error: message })
    }
  },

  reset: () => {
    set({
      importType: null,
      file: null,
      preview: null,
      mapping: [],
      progress: { ...initialProgress },
      isUploading: false,
      isPreviewing: false,
      isImporting: false,
      error: null,
    })
  },

  clearError: () => set({ error: null }),
}))
