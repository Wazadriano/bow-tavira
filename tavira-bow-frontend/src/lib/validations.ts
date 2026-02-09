import { z } from 'zod'

// =============================================================================
// AUTH SCHEMAS
// =============================================================================

export const loginSchema = z.object({
  email: z.string().min(1, 'Email requis').email('Email invalide'),
  password: z.string().min(1, 'Mot de passe requis'),
})

export type LoginFormData = z.infer<typeof loginSchema>

// =============================================================================
// USER SCHEMAS
// =============================================================================

export const userSchema = z.object({
  email: z.string().min(1, 'Email requis').email('Email invalide'),
  password: z
    .string()
    .min(8, 'Minimum 8 caracteres')
    .optional()
    .or(z.literal('')),
  password_confirmation: z.string().optional(),
  full_name: z.string().min(1, 'Nom requis').max(100, 'Maximum 100 caracteres'),
  role: z.enum(['ADMIN', 'MEMBER']),
  is_active: z.boolean().default(true),
})

export const userCreateSchema = userSchema
  .extend({
    password: z.string().min(8, 'Minimum 8 caracteres'),
    password_confirmation: z.string().min(1, 'Confirmation requise'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Les mots de passe ne correspondent pas',
    path: ['password_confirmation'],
  })

export const userUpdateSchema = userSchema
  .omit({ password: true, password_confirmation: true })
  .extend({
    password: z.string().min(8, 'Minimum 8 caracteres').optional().or(z.literal('')),
    password_confirmation: z.string().optional(),
  })
  .refine(
    (data) => {
      if (data.password && data.password !== '') {
        return data.password === data.password_confirmation
      }
      return true
    },
    {
      message: 'Les mots de passe ne correspondent pas',
      path: ['password_confirmation'],
    }
  )

export type UserFormData = z.infer<typeof userSchema>

// =============================================================================
// WORK ITEM SCHEMAS
// =============================================================================

export const workItemSchema = z.object({
  ref_no: z.string().min(1, 'Reference requise'),
  type: z.string().optional(),
  activity: z.string().optional(),
  department: z.string().min(1, 'Departement requis'),
  description: z.string().min(1, 'Description requise'),
  bau_or_transformative: z.enum(['bau', 'transformative']).optional(),
  impact_level: z.enum(['low', 'medium', 'high']).optional(),
  current_status: z.enum(['not_started', 'in_progress', 'on_hold', 'completed']).optional(),
  deadline: z.string().optional(),
  responsible_party_id: z.number().optional(),
  tags: z.array(z.string()).optional(),
  priority_item: z.boolean().optional(),
})

// Re-export from types for compatibility
export type { WorkItemFormData } from '@/types'

// =============================================================================
// GOVERNANCE SCHEMAS
// =============================================================================

export const governanceSchema = z.object({
  activity: z.string().min(1, 'Activite requise').max(200, 'Maximum 200 caracteres'),
  description: z.string().optional(),
  department: z.string().min(1, 'Departement requis'),
  frequency: z.enum(['daily', 'weekly', 'monthly', 'quarterly', 'annually']).optional(),
  deadline: z.string().optional().nullable(),
  responsible_party_id: z.number().optional().nullable(),
})

// Re-export from types for compatibility
export type { GovernanceFormData } from '@/types'

export const governanceMilestoneSchema = z.object({
  title: z.string().min(1, 'Titre requis').max(200, 'Maximum 200 caracteres'),
  description: z.string().optional(),
  due_date: z.string().optional().nullable(),
  status: z.enum(['PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED']).default('PENDING'),
})

export type GovernanceMilestoneFormData = z.infer<typeof governanceMilestoneSchema>

// =============================================================================
// SUPPLIER SCHEMAS
// =============================================================================

export const supplierSchema = z.object({
  name: z.string().min(1, 'Nom requis').max(200, 'Maximum 200 caracteres'),
  sage_category_id: z.number().optional(),
  location: z.enum(['local', 'overseas']).optional(),
  is_common_provider: z.boolean().optional(),
  status: z.enum(['active', 'inactive', 'pending']).optional(),
  responsible_party_id: z.number().optional(),
  notes: z.string().optional(),
  entities: z.array(z.string()).optional(),
})

// Re-export from types for compatibility
export type { SupplierFormData } from '@/types'

export const contractSchema = z.object({
  supplier_id: z.number(),
  contract_ref: z.string().min(1, 'Reference requise').max(100, 'Maximum 100 caracteres'),
  description: z.string().optional(),
  start_date: z.string().optional(),
  end_date: z.string().optional(),
  value: z.number().min(0, 'Valeur positive requise').optional(),
  currency: z.string().optional(),
  auto_renewal: z.boolean().optional(),
  notice_period_days: z.number().min(0).optional(),
})

// Re-export from types for compatibility
export type { ContractFormData } from '@/types'

export const invoiceSchema = z.object({
  supplier_id: z.number(),
  invoice_ref: z.string().min(1, 'Reference requise').max(50, 'Maximum 50 caracteres'),
  description: z.string().optional(),
  amount: z.number().min(0, 'Montant positif requis'),
  currency: z.string().optional(),
  invoice_date: z.string().min(1, 'Date requise'),
  due_date: z.string().optional(),
  frequency: z.enum(['one_time', 'monthly', 'quarterly', 'annually']).optional(),
})

// Re-export from types for compatibility
export type { InvoiceFormData } from '@/types'

// =============================================================================
// RISK SCHEMAS
// =============================================================================

export const riskSchema = z.object({
  category_id: z.number({ required_error: 'Categorie requise' }),
  name: z.string().min(1, 'Nom requis').max(200, 'Maximum 200 caracteres'),
  description: z.string().optional(),
  owner_id: z.number().optional(),
  responsible_party_id: z.number().optional(),
  financial_impact: z.number().min(1).max(5),
  regulatory_impact: z.number().min(1).max(5),
  reputational_impact: z.number().min(1).max(5),
  inherent_probability: z.number().min(1).max(5),
})

// Re-export from types for compatibility
export type { RiskFormData } from '@/types'

export const riskControlSchema = z.object({
  control_id: z.number({ required_error: 'Control requis' }),
  effectiveness: z.enum(['effective', 'partially_effective', 'ineffective', 'none']).optional(),
  notes: z.string().optional(),
})

// Re-export from types for compatibility
export type { RiskControlFormData } from '@/types'

export const riskActionSchema = z.object({
  title: z.string().min(1, 'Titre requis').max(200, 'Maximum 200 caracteres'),
  description: z.string().optional(),
  owner_id: z.number().optional(),
  priority: z.enum(['low', 'medium', 'high', 'critical']).optional(),
  due_date: z.string().optional(),
})

// Re-export from types for compatibility
export type { RiskActionFormData } from '@/types'

// =============================================================================
// TEAM SCHEMAS
// =============================================================================

export const teamSchema = z.object({
  name: z.string().min(1, 'Nom requis').max(100, 'Maximum 100 caracteres'),
  description: z.string().optional(),
  is_active: z.boolean().optional(),
})

// Re-export from types for compatibility
export type { TeamFormData } from '@/types'

export const teamMemberSchema = z.object({
  user_id: z.number({ required_error: 'Utilisateur requis' }),
  is_lead: z.boolean().default(false),
})

export type TeamMemberFormData = z.infer<typeof teamMemberSchema>

// =============================================================================
// SETTINGS SCHEMAS
// =============================================================================

export const settingListSchema = z.object({
  type: z.string().min(1, 'Type requis'),
  value: z.string().min(1, 'Valeur requise').max(200, 'Maximum 200 caracteres'),
  label: z.string().min(1, 'Libelle requis').max(200, 'Maximum 200 caracteres'),
  sort_order: z.number().min(0).default(0),
  is_active: z.boolean().default(true),
})

export type SettingListFormData = z.infer<typeof settingListSchema>

export const systemSettingSchema = z.object({
  key: z.string().min(1, 'Cle requise').max(100, 'Maximum 100 caracteres'),
  value: z.string().min(1, 'Valeur requise'),
  description: z.string().optional(),
})

export type SystemSettingFormData = z.infer<typeof systemSettingSchema>

// =============================================================================
// MILESTONE SCHEMAS
// =============================================================================

export const taskMilestoneSchema = z.object({
  title: z.string().min(1, 'Titre requis').max(200, 'Maximum 200 caracteres'),
  description: z.string().optional(),
  due_date: z.string().optional().nullable(),
})

export type TaskMilestoneFormData = z.infer<typeof taskMilestoneSchema>

// =============================================================================
// IMPORT SCHEMAS
// =============================================================================

export const importPreviewSchema = z.object({
  file: z.instanceof(File, { message: 'Fichier requis' }),
  type: z.enum(['workitems', 'suppliers', 'invoices', 'risks']),
})

export const importConfirmSchema = z.object({
  mapping: z.record(z.string()),
  skip_errors: z.boolean().default(false),
})

// =============================================================================
// SEARCH SCHEMAS
// =============================================================================

export const globalSearchSchema = z.object({
  query: z.string().min(2, 'Minimum 2 caracteres'),
  types: z.array(z.enum(['workitems', 'governance', 'suppliers', 'risks', 'users'])).optional(),
})

export type GlobalSearchFormData = z.infer<typeof globalSearchSchema>
