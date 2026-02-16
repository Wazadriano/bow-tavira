import { createBrowserRouter, Navigate } from 'react-router-dom'
import DashboardLayout from '@/layouts/DashboardLayout'

// Auth
import LoginPage from '@/app/(auth)/login/page'

// Dashboard
import DashboardPage from '@/app/(dashboard)/dashboard/page'

// Tasks
import TasksPage from '@/app/(dashboard)/tasks/page'
import TasksNewPage from '@/app/(dashboard)/tasks/new/page'
import TaskDetailPage from '@/app/(dashboard)/tasks/[id]/page'
import TaskEditPage from '@/app/(dashboard)/tasks/[id]/edit/page'
import TasksCalendarPage from '@/app/(dashboard)/tasks/calendar/page'
import TasksKanbanPage from '@/app/(dashboard)/tasks/kanban/page'
import TasksGanttPage from '@/app/(dashboard)/tasks/gantt/page'
import TasksWorkloadPage from '@/app/(dashboard)/tasks/workload/page'
import TasksDashboardPage from '@/app/(dashboard)/tasks/dashboard/page'

// Risks
import RisksPage from '@/app/(dashboard)/risks/page'
import RisksNewPage from '@/app/(dashboard)/risks/new/page'
import RiskDetailPage from '@/app/(dashboard)/risks/[id]/page'
import RiskEditPage from '@/app/(dashboard)/risks/[id]/edit/page'
import RisksDashboardPage from '@/app/(dashboard)/risks/dashboard/page'
import RisksHeatmapPage from '@/app/(dashboard)/risks/heatmap/page'
import RisksActionsPage from '@/app/(dashboard)/risks/actions/page'
import RisksControlsPage from '@/app/(dashboard)/risks/controls/page'
import RisksThemePermissionsPage from '@/app/(dashboard)/risks/themes/permissions/page'

// Governance
import GovernancePage from '@/app/(dashboard)/governance/page'
import GovernanceNewPage from '@/app/(dashboard)/governance/new/page'
import GovernanceDetailPage from '@/app/(dashboard)/governance/[id]/page'
import GovernanceEditPage from '@/app/(dashboard)/governance/[id]/edit/page'
import GovernanceDashboardPage from '@/app/(dashboard)/governance/dashboard/page'
import GovernanceCalendarPage from '@/app/(dashboard)/governance/calendar/page'

// Suppliers
import SuppliersPage from '@/app/(dashboard)/suppliers/page'
import SuppliersNewPage from '@/app/(dashboard)/suppliers/new/page'
import SupplierDetailPage from '@/app/(dashboard)/suppliers/[id]/page'
import SupplierEditPage from '@/app/(dashboard)/suppliers/[id]/edit/page'
import SuppliersDashboardPage from '@/app/(dashboard)/suppliers/dashboard/page'
import SuppliersContractsPage from '@/app/(dashboard)/suppliers/contracts/page'
import SuppliersInvoicesPage from '@/app/(dashboard)/suppliers/invoices/page'
import SuppliersCalendarPage from '@/app/(dashboard)/suppliers/calendar/page'

// Users
import UsersPage from '@/app/(dashboard)/users/page'
import UsersNewPage from '@/app/(dashboard)/users/new/page'
import UserDetailPage from '@/app/(dashboard)/users/[id]/page'
import UserEditPage from '@/app/(dashboard)/users/[id]/edit/page'

// Teams
import TeamsPage from '@/app/(dashboard)/teams/page'
import TeamsNewPage from '@/app/(dashboard)/teams/new/page'
import TeamDetailPage from '@/app/(dashboard)/teams/[id]/page'
import TeamEditPage from '@/app/(dashboard)/teams/[id]/edit/page'

// Other
import SettingsPage from '@/app/(dashboard)/settings/page'
import SecuritySettingsPage from '@/app/(dashboard)/settings/security/page'
import ImportExportPage from '@/app/(dashboard)/import-export/page'
import AuditPage from '@/app/(dashboard)/audit/page'
import NotificationsPage from '@/app/(dashboard)/notifications/page'
import AlertsPage from '@/app/(dashboard)/alerts/page'
import LoginHistoryPage from '@/app/(dashboard)/admin/login-history/page'

export const router = createBrowserRouter([
  {
    path: '/',
    element: <Navigate to="/dashboard" replace />,
  },
  {
    path: '/login',
    element: <LoginPage />,
  },
  {
    element: <DashboardLayout />,
    children: [
      { path: '/dashboard', element: <DashboardPage /> },

      // Tasks
      { path: '/tasks', element: <TasksPage /> },
      { path: '/tasks/new', element: <TasksNewPage /> },
      { path: '/tasks/calendar', element: <TasksCalendarPage /> },
      { path: '/tasks/kanban', element: <TasksKanbanPage /> },
      { path: '/tasks/gantt', element: <TasksGanttPage /> },
      { path: '/tasks/workload', element: <TasksWorkloadPage /> },
      { path: '/tasks/dashboard', element: <TasksDashboardPage /> },
      { path: '/tasks/:id', element: <TaskDetailPage /> },
      { path: '/tasks/:id/edit', element: <TaskEditPage /> },

      // Risks
      { path: '/risks', element: <RisksPage /> },
      { path: '/risks/new', element: <RisksNewPage /> },
      { path: '/risks/dashboard', element: <RisksDashboardPage /> },
      { path: '/risks/heatmap', element: <RisksHeatmapPage /> },
      { path: '/risks/actions', element: <RisksActionsPage /> },
      { path: '/risks/controls', element: <RisksControlsPage /> },
      { path: '/risks/themes/permissions', element: <RisksThemePermissionsPage /> },
      { path: '/risks/:id', element: <RiskDetailPage /> },
      { path: '/risks/:id/edit', element: <RiskEditPage /> },

      // Governance
      { path: '/governance', element: <GovernancePage /> },
      { path: '/governance/new', element: <GovernanceNewPage /> },
      { path: '/governance/dashboard', element: <GovernanceDashboardPage /> },
      { path: '/governance/calendar', element: <GovernanceCalendarPage /> },
      { path: '/governance/:id', element: <GovernanceDetailPage /> },
      { path: '/governance/:id/edit', element: <GovernanceEditPage /> },

      // Suppliers
      { path: '/suppliers', element: <SuppliersPage /> },
      { path: '/suppliers/new', element: <SuppliersNewPage /> },
      { path: '/suppliers/dashboard', element: <SuppliersDashboardPage /> },
      { path: '/suppliers/contracts', element: <SuppliersContractsPage /> },
      { path: '/suppliers/invoices', element: <SuppliersInvoicesPage /> },
      { path: '/suppliers/calendar', element: <SuppliersCalendarPage /> },
      { path: '/suppliers/:id', element: <SupplierDetailPage /> },
      { path: '/suppliers/:id/edit', element: <SupplierEditPage /> },

      // Users
      { path: '/users', element: <UsersPage /> },
      { path: '/users/new', element: <UsersNewPage /> },
      { path: '/users/:id', element: <UserDetailPage /> },
      { path: '/users/:id/edit', element: <UserEditPage /> },

      // Teams
      { path: '/teams', element: <TeamsPage /> },
      { path: '/teams/new', element: <TeamsNewPage /> },
      { path: '/teams/:id', element: <TeamDetailPage /> },
      { path: '/teams/:id/edit', element: <TeamEditPage /> },

      // Settings & Others
      { path: '/settings', element: <SettingsPage /> },
      { path: '/settings/security', element: <SecuritySettingsPage /> },
      { path: '/import-export', element: <ImportExportPage /> },
      { path: '/audit', element: <AuditPage /> },
      { path: '/notifications', element: <NotificationsPage /> },
      { path: '/alerts', element: <AlertsPage /> },
      { path: '/admin/login-history', element: <LoginHistoryPage /> },
    ],
  },
])
