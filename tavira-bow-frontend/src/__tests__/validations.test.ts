import { describe, it, expect } from 'vitest'
import {
  loginSchema,
  userSchema,
  userCreateSchema,
  workItemSchema,
  governanceSchema,
  supplierSchema,
  contractSchema,
  invoiceSchema,
  riskSchema,
  riskActionSchema,
  teamSchema,
  settingListSchema,
  globalSearchSchema,
} from '@/lib/validations'

// =============================================================================
// LOGIN
// =============================================================================
describe('loginSchema', () => {
  it('accepts valid credentials', () => {
    const result = loginSchema.safeParse({ email: 'test@example.com', password: 'password123' })
    expect(result.success).toBe(true)
  })

  it('rejects empty email', () => {
    const result = loginSchema.safeParse({ email: '', password: 'password123' })
    expect(result.success).toBe(false)
  })

  it('rejects invalid email format', () => {
    const result = loginSchema.safeParse({ email: 'not-an-email', password: 'password123' })
    expect(result.success).toBe(false)
  })

  it('rejects empty password', () => {
    const result = loginSchema.safeParse({ email: 'test@example.com', password: '' })
    expect(result.success).toBe(false)
  })
})

// =============================================================================
// USER
// =============================================================================
describe('userSchema', () => {
  const validUser = {
    email: 'user@example.com',
    full_name: 'John Doe',
    role: 'ADMIN' as const,
    is_active: true,
  }

  it('accepts valid user', () => {
    const result = userSchema.safeParse(validUser)
    expect(result.success).toBe(true)
  })

  it('rejects name over 100 chars', () => {
    const result = userSchema.safeParse({ ...validUser, full_name: 'a'.repeat(101) })
    expect(result.success).toBe(false)
  })

  it('rejects invalid role', () => {
    const result = userSchema.safeParse({ ...validUser, role: 'SUPERADMIN' })
    expect(result.success).toBe(false)
  })
})

describe('userCreateSchema', () => {
  it('requires password and confirmation match', () => {
    const result = userCreateSchema.safeParse({
      email: 'new@example.com',
      full_name: 'New User',
      role: 'MEMBER',
      password: 'password123',
      password_confirmation: 'password123',
    })
    expect(result.success).toBe(true)
  })

  it('rejects mismatched passwords', () => {
    const result = userCreateSchema.safeParse({
      email: 'new@example.com',
      full_name: 'New User',
      role: 'MEMBER',
      password: 'password123',
      password_confirmation: 'different',
    })
    expect(result.success).toBe(false)
  })

  it('rejects short password', () => {
    const result = userCreateSchema.safeParse({
      email: 'new@example.com',
      full_name: 'New User',
      role: 'MEMBER',
      password: 'short',
      password_confirmation: 'short',
    })
    expect(result.success).toBe(false)
  })
})

// =============================================================================
// WORK ITEM
// =============================================================================
describe('workItemSchema', () => {
  const validWorkItem = {
    ref_no: 'BOW-001',
    department: 'IT',
    description: 'Test work item',
  }

  it('accepts valid work item', () => {
    const result = workItemSchema.safeParse(validWorkItem)
    expect(result.success).toBe(true)
  })

  it('requires ref_no', () => {
    const result = workItemSchema.safeParse({ ...validWorkItem, ref_no: '' })
    expect(result.success).toBe(false)
  })

  it('requires department', () => {
    const result = workItemSchema.safeParse({ ...validWorkItem, department: '' })
    expect(result.success).toBe(false)
  })

  it('requires description', () => {
    const result = workItemSchema.safeParse({ ...validWorkItem, description: '' })
    expect(result.success).toBe(false)
  })

  it('accepts optional fields', () => {
    const result = workItemSchema.safeParse({
      ...validWorkItem,
      type: 'Enhancement',
      activity: 'Security',
      current_status: 'in_progress',
      priority_item: true,
      deadline: '2026-06-15',
      responsible_party_id: 1,
    })
    expect(result.success).toBe(true)
  })

  it('rejects invalid status enum', () => {
    const result = workItemSchema.safeParse({
      ...validWorkItem,
      current_status: 'invalid_status',
    })
    expect(result.success).toBe(false)
  })
})

// =============================================================================
// GOVERNANCE
// =============================================================================
describe('governanceSchema', () => {
  const validGovernance = {
    ref_no: 'GOV-001',
    activity: 'Board Meeting',
    description: 'Quarterly board meeting',
    department: 'Corporate Governance',
  }

  it('accepts valid governance item', () => {
    const result = governanceSchema.safeParse(validGovernance)
    expect(result.success).toBe(true)
  })

  it('requires activity', () => {
    const result = governanceSchema.safeParse({ ...validGovernance, activity: '' })
    expect(result.success).toBe(false)
  })

  it('rejects ref_no over 50 chars', () => {
    const result = governanceSchema.safeParse({ ...validGovernance, ref_no: 'G'.repeat(51) })
    expect(result.success).toBe(false)
  })

  it('accepts valid frequency enum', () => {
    const result = governanceSchema.safeParse({ ...validGovernance, frequency: 'quarterly' })
    expect(result.success).toBe(true)
  })

  it('rejects invalid frequency', () => {
    const result = governanceSchema.safeParse({ ...validGovernance, frequency: 'biweekly' })
    expect(result.success).toBe(false)
  })
})

// =============================================================================
// SUPPLIER
// =============================================================================
describe('supplierSchema', () => {
  it('accepts valid supplier', () => {
    const result = supplierSchema.safeParse({ name: 'Acme Corp' })
    expect(result.success).toBe(true)
  })

  it('requires name', () => {
    const result = supplierSchema.safeParse({ name: '' })
    expect(result.success).toBe(false)
  })

  it('rejects name over 200 chars', () => {
    const result = supplierSchema.safeParse({ name: 'X'.repeat(201) })
    expect(result.success).toBe(false)
  })

  it('accepts valid location enum', () => {
    const result = supplierSchema.safeParse({ name: 'Test', location: 'overseas' })
    expect(result.success).toBe(true)
  })

  it('rejects invalid location', () => {
    const result = supplierSchema.safeParse({ name: 'Test', location: 'mars' })
    expect(result.success).toBe(false)
  })
})

// =============================================================================
// CONTRACT
// =============================================================================
describe('contractSchema', () => {
  it('accepts valid contract', () => {
    const result = contractSchema.safeParse({
      supplier_id: 1,
      contract_ref: 'CTR-001',
    })
    expect(result.success).toBe(true)
  })

  it('requires supplier_id', () => {
    const result = contractSchema.safeParse({ contract_ref: 'CTR-001' })
    expect(result.success).toBe(false)
  })

  it('rejects negative value', () => {
    const result = contractSchema.safeParse({
      supplier_id: 1,
      contract_ref: 'CTR-001',
      value: -100,
    })
    expect(result.success).toBe(false)
  })
})

// =============================================================================
// INVOICE
// =============================================================================
describe('invoiceSchema', () => {
  it('accepts valid invoice', () => {
    const result = invoiceSchema.safeParse({
      supplier_id: 1,
      invoice_ref: 'INV-001',
      amount: 1500.50,
      invoice_date: '2026-01-15',
    })
    expect(result.success).toBe(true)
  })

  it('rejects negative amount', () => {
    const result = invoiceSchema.safeParse({
      supplier_id: 1,
      invoice_ref: 'INV-001',
      amount: -10,
      invoice_date: '2026-01-15',
    })
    expect(result.success).toBe(false)
  })

  it('requires invoice_date', () => {
    const result = invoiceSchema.safeParse({
      supplier_id: 1,
      invoice_ref: 'INV-001',
      amount: 100,
    })
    expect(result.success).toBe(false)
  })

  it('rejects invalid frequency', () => {
    const result = invoiceSchema.safeParse({
      supplier_id: 1,
      invoice_ref: 'INV-001',
      amount: 100,
      invoice_date: '2026-01-15',
      frequency: 'biweekly',
    })
    expect(result.success).toBe(false)
  })
})

// =============================================================================
// RISK
// =============================================================================
describe('riskSchema', () => {
  const validRisk = {
    category_id: 1,
    name: 'Credit Risk',
    financial_impact: 3,
    regulatory_impact: 2,
    reputational_impact: 4,
    inherent_probability: 3,
  }

  it('accepts valid risk', () => {
    const result = riskSchema.safeParse(validRisk)
    expect(result.success).toBe(true)
  })

  it('requires category_id', () => {
    const { category_id, ...rest } = validRisk
    const result = riskSchema.safeParse(rest)
    expect(result.success).toBe(false)
  })

  it('requires name', () => {
    const result = riskSchema.safeParse({ ...validRisk, name: '' })
    expect(result.success).toBe(false)
  })

  it('rejects impact below 1', () => {
    const result = riskSchema.safeParse({ ...validRisk, financial_impact: 0 })
    expect(result.success).toBe(false)
  })

  it('rejects impact above 5', () => {
    const result = riskSchema.safeParse({ ...validRisk, financial_impact: 6 })
    expect(result.success).toBe(false)
  })

  it('rejects probability below 1', () => {
    const result = riskSchema.safeParse({ ...validRisk, inherent_probability: 0 })
    expect(result.success).toBe(false)
  })

  it('rejects probability above 5', () => {
    const result = riskSchema.safeParse({ ...validRisk, inherent_probability: 6 })
    expect(result.success).toBe(false)
  })
})

describe('riskActionSchema', () => {
  it('accepts valid risk action', () => {
    const result = riskActionSchema.safeParse({ title: 'Mitigate credit exposure' })
    expect(result.success).toBe(true)
  })

  it('requires title', () => {
    const result = riskActionSchema.safeParse({ title: '' })
    expect(result.success).toBe(false)
  })

  it('rejects invalid priority', () => {
    const result = riskActionSchema.safeParse({ title: 'Test', priority: 'urgent' })
    expect(result.success).toBe(false)
  })

  it('accepts valid priority', () => {
    const result = riskActionSchema.safeParse({ title: 'Test', priority: 'critical' })
    expect(result.success).toBe(true)
  })
})

// =============================================================================
// TEAM
// =============================================================================
describe('teamSchema', () => {
  it('accepts valid team', () => {
    const result = teamSchema.safeParse({ name: 'Risk Committee' })
    expect(result.success).toBe(true)
  })

  it('rejects empty name', () => {
    const result = teamSchema.safeParse({ name: '' })
    expect(result.success).toBe(false)
  })

  it('rejects name over 100 chars', () => {
    const result = teamSchema.safeParse({ name: 'T'.repeat(101) })
    expect(result.success).toBe(false)
  })
})

// =============================================================================
// SETTINGS
// =============================================================================
describe('settingListSchema', () => {
  it('accepts valid setting', () => {
    const result = settingListSchema.safeParse({
      type: 'department',
      value: 'IT',
      label: 'Information Technology',
      sort_order: 0,
      is_active: true,
    })
    expect(result.success).toBe(true)
  })

  it('requires type', () => {
    const result = settingListSchema.safeParse({ type: '', value: 'IT', label: 'IT' })
    expect(result.success).toBe(false)
  })

  it('rejects value over 200 chars', () => {
    const result = settingListSchema.safeParse({ type: 'dept', value: 'X'.repeat(201), label: 'IT' })
    expect(result.success).toBe(false)
  })
})

// =============================================================================
// SEARCH
// =============================================================================
describe('globalSearchSchema', () => {
  it('accepts valid search query', () => {
    const result = globalSearchSchema.safeParse({ query: 'test query' })
    expect(result.success).toBe(true)
  })

  it('rejects query under 2 chars', () => {
    const result = globalSearchSchema.safeParse({ query: 'a' })
    expect(result.success).toBe(false)
  })

  it('accepts search with type filter', () => {
    const result = globalSearchSchema.safeParse({
      query: 'test',
      types: ['workitems', 'risks'],
    })
    expect(result.success).toBe(true)
  })

  it('rejects invalid search type', () => {
    const result = globalSearchSchema.safeParse({
      query: 'test',
      types: ['invalid_type'],
    })
    expect(result.success).toBe(false)
  })
})
