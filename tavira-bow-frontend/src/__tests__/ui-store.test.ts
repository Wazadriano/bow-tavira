import { describe, it, expect, beforeEach } from 'vitest'
import { useUIStore } from '@/stores/ui'
import { act } from '@testing-library/react'

describe('UI store', () => {
  beforeEach(() => {
    act(() => {
      useUIStore.setState({
        sidebarCollapsed: false,
        isGlobalLoading: false,
        modal: { isOpen: false, type: null, data: undefined },
        confirmDialog: {
          isOpen: false,
          title: '',
          description: '',
          onConfirm: null,
          variant: 'default',
        },
        theme: 'system',
        notificationsPanelOpen: false,
        _hasHydrated: false,
      })
    })
  })

  describe('sidebar', () => {
    it('starts expanded', () => {
      expect(useUIStore.getState().sidebarCollapsed).toBe(false)
    })

    it('toggles sidebar', () => {
      act(() => {
        useUIStore.getState().toggleSidebar()
      })
      expect(useUIStore.getState().sidebarCollapsed).toBe(true)

      act(() => {
        useUIStore.getState().toggleSidebar()
      })
      expect(useUIStore.getState().sidebarCollapsed).toBe(false)
    })

    it('sets sidebar collapsed directly', () => {
      act(() => {
        useUIStore.getState().setSidebarCollapsed(true)
      })
      expect(useUIStore.getState().sidebarCollapsed).toBe(true)
    })
  })

  describe('global loading', () => {
    it('starts not loading', () => {
      expect(useUIStore.getState().isGlobalLoading).toBe(false)
    })

    it('sets loading state', () => {
      act(() => {
        useUIStore.getState().setGlobalLoading(true)
      })
      expect(useUIStore.getState().isGlobalLoading).toBe(true)
    })
  })

  describe('modal', () => {
    it('starts closed', () => {
      expect(useUIStore.getState().modal.isOpen).toBe(false)
      expect(useUIStore.getState().modal.type).toBeNull()
    })

    it('opens modal with type and data', () => {
      act(() => {
        useUIStore.getState().openModal('edit-user', { id: 1 })
      })

      const modal = useUIStore.getState().modal
      expect(modal.isOpen).toBe(true)
      expect(modal.type).toBe('edit-user')
      expect(modal.data).toEqual({ id: 1 })
    })

    it('closes modal and clears data', () => {
      act(() => {
        useUIStore.getState().openModal('delete-item', { id: 5 })
      })

      act(() => {
        useUIStore.getState().closeModal()
      })

      const modal = useUIStore.getState().modal
      expect(modal.isOpen).toBe(false)
      expect(modal.type).toBeNull()
      expect(modal.data).toBeUndefined()
    })
  })

  describe('confirm dialog', () => {
    it('starts closed', () => {
      expect(useUIStore.getState().confirmDialog.isOpen).toBe(false)
    })

    it('shows confirm dialog', () => {
      const onConfirm = () => {}

      act(() => {
        useUIStore.getState().showConfirm({
          title: 'Delete item?',
          description: 'This cannot be undone',
          onConfirm,
          variant: 'destructive',
        })
      })

      const dialog = useUIStore.getState().confirmDialog
      expect(dialog.isOpen).toBe(true)
      expect(dialog.title).toBe('Delete item?')
      expect(dialog.description).toBe('This cannot be undone')
      expect(dialog.variant).toBe('destructive')
      expect(dialog.onConfirm).toBe(onConfirm)
    })

    it('hides confirm dialog and resets state', () => {
      act(() => {
        useUIStore.getState().showConfirm({
          title: 'Test',
          description: 'Test desc',
          onConfirm: () => {},
        })
      })

      act(() => {
        useUIStore.getState().hideConfirm()
      })

      const dialog = useUIStore.getState().confirmDialog
      expect(dialog.isOpen).toBe(false)
      expect(dialog.title).toBe('')
      expect(dialog.onConfirm).toBeNull()
    })

    it('defaults to default variant', () => {
      act(() => {
        useUIStore.getState().showConfirm({
          title: 'Test',
          description: 'Test desc',
          onConfirm: () => {},
        })
      })

      expect(useUIStore.getState().confirmDialog.variant).toBe('default')
    })
  })

  describe('theme', () => {
    it('defaults to system', () => {
      expect(useUIStore.getState().theme).toBe('system')
    })

    it('sets theme', () => {
      act(() => {
        useUIStore.getState().setTheme('dark')
      })
      expect(useUIStore.getState().theme).toBe('dark')

      act(() => {
        useUIStore.getState().setTheme('light')
      })
      expect(useUIStore.getState().theme).toBe('light')
    })
  })

  describe('notifications panel', () => {
    it('starts closed', () => {
      expect(useUIStore.getState().notificationsPanelOpen).toBe(false)
    })

    it('toggles notifications panel', () => {
      act(() => {
        useUIStore.getState().toggleNotificationsPanel()
      })
      expect(useUIStore.getState().notificationsPanelOpen).toBe(true)

      act(() => {
        useUIStore.getState().toggleNotificationsPanel()
      })
      expect(useUIStore.getState().notificationsPanelOpen).toBe(false)
    })
  })
})
