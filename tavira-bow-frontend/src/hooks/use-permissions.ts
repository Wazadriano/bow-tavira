import { useMemo } from 'react'
import { useAuthStore } from '@/stores/auth'

export function usePermissions() {
  const user = useAuthStore((state) => state.user)

  return useMemo(() => {
    const isAdmin = user?.role === 'admin'

    const deptPerms = user?.department_permissions ?? []
    const themePerms = user?.risk_theme_permissions ?? []

    function canViewDepartment(dept: string): boolean {
      if (isAdmin) return true
      return deptPerms.some((p) => p.department === dept && p.can_view)
    }

    function canEditInDepartment(dept: string): boolean {
      if (isAdmin) return true
      return deptPerms.some((p) => p.department === dept && p.can_edit_all)
    }

    function canCreateInDepartment(dept: string): boolean {
      if (isAdmin) return true
      return deptPerms.some((p) => p.department === dept && p.can_create_tasks)
    }

    function canEditStatusInDepartment(dept: string): boolean {
      if (isAdmin) return true
      return deptPerms.some((p) => p.department === dept && p.can_edit_status)
    }

    function canViewRiskTheme(themeId: number): boolean {
      if (isAdmin) return true
      return themePerms.some((p) => p.theme_id === themeId && p.can_view)
    }

    function canEditRiskTheme(themeId: number): boolean {
      if (isAdmin) return true
      return themePerms.some((p) => p.theme_id === themeId && p.can_edit)
    }

    function canCreateInRiskTheme(themeId: number): boolean {
      if (isAdmin) return true
      return themePerms.some((p) => p.theme_id === themeId && p.can_create)
    }

    function canDeleteInRiskTheme(themeId: number): boolean {
      if (isAdmin) return true
      return themePerms.some((p) => p.theme_id === themeId && p.can_delete)
    }

    return {
      isAdmin,
      canViewDepartment,
      canEditInDepartment,
      canCreateInDepartment,
      canEditStatusInDepartment,
      canViewRiskTheme,
      canEditRiskTheme,
      canCreateInRiskTheme,
      canDeleteInRiskTheme,
    }
  }, [user])
}
