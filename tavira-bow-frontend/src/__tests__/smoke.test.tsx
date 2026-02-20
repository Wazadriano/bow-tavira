import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { cn, formatDate, formatCurrency, truncate } from '@/lib/utils'
import { Button } from '@/components/ui/button'

describe('utility functions', () => {
  it('cn merges class names correctly', () => {
    expect(cn('px-2', 'py-3')).toBe('px-2 py-3')
    expect(cn('px-2', false && 'hidden')).toBe('px-2')
    expect(cn('px-2 py-2', 'py-3')).toBe('px-2 py-3')
  })

  it('formatDate returns formatted date or dash', () => {
    expect(formatDate(null)).toBe('-')
    expect(formatDate(undefined)).toBe('-')
    expect(formatDate('invalid')).toBe('-')
    const result = formatDate('2024-06-15')
    expect(result).toMatch(/15/)
    expect(result).toMatch(/06/)
    expect(result).toMatch(/2024/)
  })

  it('formatCurrency formats EUR amounts', () => {
    expect(formatCurrency(null)).toBe('-')
    expect(formatCurrency(undefined)).toBe('-')
    const result = formatCurrency(1234.56)
    expect(result).toContain('1')
    expect(result).toContain('234')
  })

  it('truncate shortens strings', () => {
    expect(truncate('hello', 10)).toBe('hello')
    expect(truncate('hello world this is long', 10)).toBe('hello worl...')
  })
})

describe('Button component', () => {
  it('renders without crashing', () => {
    render(<Button>Click me</Button>)
    expect(screen.getByRole('button', { name: 'Click me' })).toBeInTheDocument()
  })

  it('renders with variant and size props', () => {
    render(<Button variant="destructive" size="sm">Delete</Button>)
    const button = screen.getByRole('button', { name: 'Delete' })
    expect(button).toBeInTheDocument()
    expect(button.className).toContain('destructive')
  })

  it('is disabled when disabled prop is passed', () => {
    render(<Button disabled>Disabled</Button>)
    expect(screen.getByRole('button', { name: 'Disabled' })).toBeDisabled()
  })
})
