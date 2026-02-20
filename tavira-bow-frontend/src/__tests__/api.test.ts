import { describe, it, expect } from 'vitest'
import { api } from '@/lib/api'

describe('API client configuration', () => {
  it('has the correct default baseURL pattern', () => {
    expect(api.defaults.baseURL).toMatch(/\/api$/)
  })

  it('sends JSON content-type headers', () => {
    expect(api.defaults.headers['Content-Type']).toBe('application/json')
    expect(api.defaults.headers['Accept']).toBe('application/json')
  })

  it('has withCredentials enabled', () => {
    expect(api.defaults.withCredentials).toBe(true)
  })

  it('has request interceptors configured', () => {
    const requestInterceptors = api.interceptors.request as unknown as { handlers: unknown[] }
    expect(requestInterceptors.handlers.length).toBeGreaterThan(0)
  })

  it('has response interceptors configured', () => {
    const responseInterceptors = api.interceptors.response as unknown as { handlers: unknown[] }
    expect(responseInterceptors.handlers.length).toBeGreaterThan(0)
  })
})
