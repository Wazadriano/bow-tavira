// User types
export interface User {
  id: number
  email: string
  full_name: string
  role: 'admin' | 'member'
  department: string | null
  is_active: boolean
  two_factor_confirmed_at: string | null
  created_at: string
  updated_at: string
}

export interface UserDepartmentPermission {
  id: number
  user_id: number
  department: string
  can_view: boolean
  can_edit: boolean
  can_delete: boolean
}

// Work Item types
export interface WorkItem {
  id: number
  ref_no: string
  type: string | null
  activity: string | null
  department: string
  description: string
  goal: string | null
  bau_or_transformative: 'bau' | 'transformative' | null
  impact_level: 'low' | 'medium' | 'high' | null
  current_status: 'not_started' | 'in_progress' | 'on_hold' | 'completed' | null
  rag_status: 'blue' | 'green' | 'amber' | 'red' | null
  deadline: string | null
  completion_date: string | null
  monthly_update: string | null
  comments: string | null
  update_frequency: string | null
  responsible_party_id: number | null
  responsible_party: User | null
  department_head_id: number | null
  department_head: User | null
  tags: string[]
  priority_item: boolean
  cost_savings: number | null
  cost_efficiency_fte: number | null
  expected_cost: number | null
  revenue_potential: number | null
  dependencies?: TaskDependency[]
  attachments?: WorkItemAttachment[]
  assignments?: TaskAssignment[]
  created_at: string
  updated_at: string
}

// Governance types
export interface GovernanceItem {
  id: number
  ref_no: string
  activity: string | null
  description: string | null
  department: string
  location: string | null
  frequency: 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'annually' | null
  current_status: 'not_started' | 'in_progress' | 'completed' | null
  rag_status: 'blue' | 'green' | 'amber' | 'red' | null
  deadline: string | null
  completion_date: string | null
  monthly_update: string | null
  responsible_party_id: number | null
  responsible_party: User | null
  tags: string[] | null
  file_path: string | null
  is_overdue: boolean
  milestones: GovernanceMilestone[]
  access: GovernanceItemAccess[]
  created_at: string
  updated_at: string
}

// Supplier types
export interface Supplier {
  id: number
  ref_no: string
  name: string
  sage_category_id: number | null
  sage_category: SageCategory | null
  location: 'local' | 'overseas' | null
  is_common_provider: boolean
  status: 'active' | 'inactive' | 'pending' | null
  responsible_party_id: number | null
  responsible_party: User | null
  notes: string | null
  entities: SupplierEntity[]
  created_at: string
  updated_at: string
}

export interface SageCategory {
  id: number
  code: string
  name: string
  description: string | null
}

export interface SupplierEntity {
  id: number
  supplier_id: number
  entity: string
}

export interface SupplierContract {
  id: number
  supplier_id: number
  contract_ref: string
  description: string | null
  start_date: string | null
  end_date: string | null
  value: number | null
  currency: string
  status: 'active' | 'expired' | 'pending' | null
  auto_renewal: boolean
  notice_period_days: number | null
  created_at: string
  updated_at: string
}

export interface SupplierInvoice {
  id: number
  supplier_id: number
  invoice_ref: string
  description: string | null
  amount: number
  currency: string
  invoice_date: string
  due_date: string | null
  frequency: 'one_time' | 'monthly' | 'quarterly' | 'annually' | null
  status: 'pending' | 'paid' | 'overdue' | null
  created_at: string
  updated_at: string
}

// Risk types
export interface RiskTheme {
  id: number
  code: string
  name: string
  description: string | null
  color: string | null
  order: number
  is_active: boolean
  categories: RiskCategory[]
}

export interface RiskCategory {
  id: number
  theme_id: number
  theme: RiskTheme | null
  code: string
  name: string
  description: string | null
  risk_appetite_threshold: number | null
  order: number
  is_active: boolean
}

export interface Risk {
  id: number
  ref_no: string
  category_id: number
  category: RiskCategory | null
  name: string
  description: string | null
  tier: 'tier_1' | 'tier_2' | 'tier_3' | null
  owner_id: number | null
  owner: User | null
  responsible_party_id: number | null
  responsible_party: User | null
  financial_impact: number
  regulatory_impact: number
  reputational_impact: number
  inherent_probability: number
  inherent_risk_score: number | null
  inherent_rag: 'green' | 'amber' | 'red' | null
  residual_risk_score: number | null
  residual_rag: 'green' | 'amber' | 'red' | null
  appetite_status: 'within' | 'approaching' | 'exceeded' | null
  created_at: string
  updated_at: string
}

export interface ControlLibrary {
  id: number
  code: string
  name: string
  description: string | null
  control_type: 'preventive' | 'detective' | 'corrective' | null
  frequency: 'continuous' | 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'annually' | null
  is_active: boolean
}

export interface RiskControl {
  id: number
  risk_id: number
  control_id: number
  control: ControlLibrary | null
  is_active: boolean
  effectiveness: 'effective' | 'partially_effective' | 'ineffective' | 'none' | null
  last_review_date: string | null
  notes: string | null
}

export interface RiskAction {
  id: number
  risk_id: number
  title: string
  description: string | null
  owner_id: number | null
  owner: User | null
  status: 'open' | 'in_progress' | 'completed' | 'cancelled' | null
  priority: 'low' | 'medium' | 'high' | 'critical' | null
  due_date: string | null
  completion_date: string | null
  notes: string | null
  is_overdue: boolean
}

// API Response types
export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface DashboardStats {
  total_tasks: number
  completed_tasks: number
  overdue_tasks: number
  total_suppliers: number
  total_risks: number
  high_risks: number
  tasks_by_rag: {
    blue: number
    green: number
    amber: number
    red: number
  }
}

export interface HeatmapData {
  matrix: HeatmapCell[]
  summary: {
    total_risks: number
    average_score: number
    max_score: number
    by_rag: {
      green: number
      amber: number
      red: number
    }
  }
  type: 'inherent' | 'residual'
}

export interface HeatmapCell {
  impact: number
  probability: number
  score: number
  rag: 'green' | 'amber' | 'red'
  count: number
  risks: { id: number; ref_no: string; name: string }[]
}

// Form types
export interface LoginFormData {
  email: string
  password: string
}

export interface WorkItemFormData {
  ref_no: string
  type?: string
  activity?: string
  department: string
  description: string
  bau_or_transformative?: 'bau' | 'transformative'
  impact_level?: 'low' | 'medium' | 'high'
  current_status?: 'not_started' | 'in_progress' | 'on_hold' | 'completed'
  deadline?: string
  responsible_party_id?: number
  tags?: string[]
  priority_item?: boolean
}

// Team types
export interface Team {
  id: number
  name: string
  description: string | null
  is_active: boolean
  created_at: string
  updated_at: string
  members: TeamMember[]
  member_count: number
}

export interface TeamMember {
  id: number
  team_id: number
  user_id: number
  is_lead: boolean
  created_at: string
  user: User
}

// Milestone types
export interface TaskMilestone {
  id: number
  work_item_id: number
  title: string
  description: string | null
  due_date: string | null
  completion_date: string | null
  status: 'not_started' | 'in_progress' | 'completed' | null
  rag_status: 'blue' | 'green' | 'amber' | 'red' | null
  order: number
  is_completed: boolean
  is_overdue: boolean
  assignments: MilestoneAssignment[]
  created_at: string
  updated_at: string
}

export interface MilestoneAssignment {
  id: number
  milestone_id: number
  user_id: number
  user: User
  created_at: string
}

export interface GovernanceMilestone {
  id: number
  governance_item_id: number
  title: string
  description: string | null
  due_date: string | null
  completion_date: string | null
  status: 'not_started' | 'in_progress' | 'completed' | null
  rag_status: 'blue' | 'green' | 'amber' | 'red' | null
  order: number
  is_completed: boolean
  is_overdue: boolean
  created_at: string
  updated_at: string
}

// Task Assignment types
export interface TaskAssignment {
  id: number
  work_item_id: number
  user_id: number
  assignment_type: 'owner' | 'member'
  acknowledged_at: string | null
  created_at: string
  user: User
}

// Task Dependency types
export interface TaskDependency {
  id: number
  work_item_id: number
  depends_on_id: number
  dependency_type: 'blocks' | 'required' | 'related' | null
  depends_on: WorkItem
}

// Attachment types
export interface Attachment {
  id: number
  filename: string
  original_filename: string
  file_path: string
  file_size: number
  file_size_formatted: string
  mime_type: string
  version?: number | string
  uploaded_by_id: number | null
  uploaded_by: User | null
  created_at: string
}

export interface WorkItemAttachment extends Attachment {
  work_item_id: number
}

export interface GovernanceAttachment extends Attachment {
  governance_item_id: number
}

export interface SupplierAttachment extends Attachment {
  supplier_id: number
}

export interface RiskAttachment extends Attachment {
  risk_id: number
}

export interface ContractAttachment extends Attachment {
  contract_id: number
  version: string
  is_current: boolean
}

// Settings types
export interface SettingList {
  id: number
  type: 'department' | 'activity' | 'entity' | 'vendor_category'
  value: string
  label: string
  order: number
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface SystemSetting {
  id: number
  key: string
  value: string
  type: 'string' | 'number' | 'boolean' | 'json'
  description: string | null
  updated_at: string
}

// Access Control types
export interface SupplierAccess {
  id: number
  supplier_id: number
  user_id: number
  can_view: boolean
  can_edit: boolean
  can_create: boolean
  can_delete: boolean
  user: User
}

export interface GovernanceItemAccess {
  id: number
  governance_item_id: number
  user_id: number
  can_view: boolean
  can_edit: boolean
  user: User
}

export interface RiskThemePermission {
  id: number
  user_id: number
  theme_id: number
  can_view: boolean
  can_edit: boolean
  can_create: boolean
  can_delete: boolean
  theme: RiskTheme
}

// Filter types
export interface WorkItemFilters {
  department?: string
  status?: string
  rag_status?: string
  priority_item?: boolean
  search?: string
  responsible_party_id?: number
}

export interface SupplierFilters {
  location?: string
  status?: string
  sage_category_id?: number
  search?: string
}

export interface RiskFilters {
  theme_id?: number
  category_id?: number
  tier?: string
  rag_status?: string
  appetite_status?: string
  owner_id?: number
  search?: string
}

export interface GovernanceFilters {
  department?: string
  status?: string
  rag_status?: string
  frequency?: string
  search?: string
}

// Dashboard extended types
export interface DashboardAlert {
  id: number
  type: 'work_item' | 'contract' | 'risk' | 'governance'
  severity: 'warning' | 'danger' | 'info'
  title: string
  message: string
  due_date: string | null
  link: string
}

export interface SupplierDashboardStats {
  total_suppliers: number
  active_suppliers: number
  total_contracts: number
  expiring_contracts: number
  expired_contracts: number
  ytd_spend: number
  contracts_by_status: {
    active: number
    expired: number
    pending: number
  }
}

export interface RiskDashboardStats {
  total_risks: number
  outside_appetite: number
  tier_a_count: number
  tier_b_count: number
  tier_c_count: number
  by_theme: { theme: string; count: number; color: string }[]
  overdue_actions: number
  open_actions: number
}

// Search types
export interface SearchResult {
  type: 'work_item' | 'supplier' | 'risk' | 'governance'
  id: number
  ref_no: string
  title: string
  description: string | null
  rag_status: string | null
  link: string
}

export interface SearchParams {
  query: string
  types?: ('work_item' | 'supplier' | 'risk' | 'governance')[]
  department?: string
  tags?: string[]
}

// Import/Export types
export interface ImportPreview {
  total_rows: number
  valid_rows: number
  invalid_rows: number
  columns: string[]
  sample_data: Record<string, unknown>[]
  errors: { row: number; field: string; message: string }[]
}

export interface ImportResult {
  success: boolean
  imported: number
  skipped: number
  errors: { row: number; message: string }[]
}

export interface ExportOptions {
  type: 'work_items' | 'suppliers' | 'risks' | 'governance'
  format: 'xlsx' | 'csv'
  filters?: Record<string, unknown>
  columns?: string[]
}

// Notification types
export interface AppNotification {
  id: string
  type: string
  notifiable_type: string
  notifiable_id: number
  data: {
    type: string
    message: string
    [key: string]: unknown
  }
  read_at: string | null
  created_at: string
  updated_at: string
}

export interface NotificationListResponse {
  notifications: AppNotification[]
  unread_count: number
  meta: {
    current_page: number
    last_page: number
    total: number
  }
}

// Form data types
export interface SupplierFormData {
  name: string
  sage_category_id?: number
  location?: 'local' | 'overseas'
  is_common_provider?: boolean
  status?: 'active' | 'inactive' | 'pending'
  responsible_party_id?: number
  notes?: string
  entities?: string[]
}

export interface RiskFormData {
  category_id: number
  name: string
  description?: string
  owner_id?: number
  responsible_party_id?: number
  financial_impact: number
  regulatory_impact: number
  reputational_impact: number
  inherent_probability: number
}

export interface GovernanceFormData {
  ref_no: string
  activity: string
  description: string
  department: string
  frequency?: 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'annually'
  deadline?: string
  responsible_party_id?: number
}

export interface TeamFormData {
  name: string
  description?: string
  is_active?: boolean
}

export interface ContractFormData {
  supplier_id: number
  contract_ref: string
  description?: string
  start_date?: string
  end_date?: string
  value?: number
  currency?: string
  auto_renewal?: boolean
  notice_period_days?: number
}

export interface InvoiceFormData {
  supplier_id: number
  invoice_ref: string
  description?: string
  amount: number
  currency?: string
  invoice_date: string
  due_date?: string
  frequency?: 'one_time' | 'monthly' | 'quarterly' | 'annually'
}

export interface MilestoneFormData {
  title: string
  description?: string
  due_date?: string
  status?: 'not_started' | 'in_progress' | 'completed'
}

export interface RiskActionFormData {
  title: string
  description?: string
  owner_id?: number
  priority?: 'low' | 'medium' | 'high' | 'critical'
  due_date?: string
}

export interface RiskControlFormData {
  control_id: number
  effectiveness?: 'effective' | 'partially_effective' | 'ineffective' | 'none'
  notes?: string
}
