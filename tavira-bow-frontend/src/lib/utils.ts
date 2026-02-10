import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatDate(date: string | Date | null | undefined): string {
  if (!date) return '-'
  const d = new Date(date)
  if (isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

export function formatDateTime(date: string | Date | null | undefined): string {
  if (!date) return '-'
  const d = new Date(date)
  if (isNaN(d.getTime())) return '-'
  return d.toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Safe date parsing - returns null if invalid
export function safeParseDate(date: string | Date | null | undefined): Date | null {
  if (!date) return null
  const d = new Date(date)
  return isNaN(d.getTime()) ? null : d
}

// Safe toLocaleDateString
export function safeDateString(date: string | Date | null | undefined, locale = 'fr-FR'): string {
  const d = safeParseDate(date)
  return d ? d.toLocaleDateString(locale) : '-'
}

export function formatCurrency(
  amount: number | null | undefined,
  currency = 'EUR'
): string {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency,
  }).format(amount)
}

export function getRAGColor(status: string | null | undefined): string {
  switch (status?.toLowerCase()) {
    case 'blue':
      return 'badge-rag-blue'
    case 'green':
      return 'badge-rag-green'
    case 'amber':
      return 'badge-rag-amber'
    case 'red':
      return 'badge-rag-red'
    default:
      return 'bg-gray-100 text-gray-600'
  }
}

export function truncate(str: string, length: number): string {
  if (str.length <= length) return str
  return str.slice(0, length) + '...'
}

export function debounce<T extends (...args: unknown[]) => unknown>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void {
  let timeout: NodeJS.Timeout | null = null

  return (...args: Parameters<T>) => {
    if (timeout) clearTimeout(timeout)
    timeout = setTimeout(() => func(...args), wait)
  }
}
