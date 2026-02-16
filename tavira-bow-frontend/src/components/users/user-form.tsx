import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import type { User } from '@/types'
import type { UserFormData } from '@/stores/users'

const userSchema = z.object({
  username: z.string().min(3, 'Minimum 3 characters'),
  email: z.string().email('Invalid email'),
  full_name: z.string().min(2, 'Minimum 2 characters'),
  role: z.enum(['admin', 'member']),
  department: z.string().optional(),
  is_active: z.boolean(),
  password: z.string().min(8, 'Minimum 8 characters').optional().or(z.literal('')),
  password_confirmation: z.string().optional().or(z.literal('')),
}).refine((data) => {
  if (data.password && data.password !== data.password_confirmation) {
    return false
  }
  return true
}, {
  message: 'Passwords do not match',
  path: ['password_confirmation'],
})

interface UserFormProps {
  user?: User
  onSubmit: (data: UserFormData) => Promise<void>
  isLoading?: boolean
}

const departments = ['IT', 'Finance', 'Operations', 'Compliance', 'HR', 'Legal', 'Marketing']

export function UserForm({ user, onSubmit, isLoading }: UserFormProps) {
  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<UserFormData>({
    resolver: zodResolver(userSchema),
    defaultValues: {
      username: '',
      email: '',
      full_name: '',
      role: 'member',
      department: '',
      is_active: true,
      password: '',
      password_confirmation: '',
    },
  })

  const role = watch('role')
  const isActive = watch('is_active')

  useEffect(() => {
    if (user) {
      setValue('username', user.email || '')
      setValue('email', user.email || '')
      setValue('full_name', user.full_name || '')
      setValue('role', user.role as 'admin' | 'member')
      setValue('department', user.department || '')
      setValue('is_active', user.is_active ?? true)
    }
  }, [user, setValue])

  const handleFormSubmit = async (data: UserFormData) => {
    // Remove empty passwords for updates
    if (user && !data.password) {
      delete data.password
      delete data.password_confirmation
    }
    await onSubmit(data)
  }

  return (
    <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Basic Information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="username">Username *</Label>
              <Input
                id="username"
                {...register('username')}
                placeholder="johndoe"
              />
              {errors.username && (
                <p className="text-sm text-destructive">{errors.username.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">Email *</Label>
              <Input
                id="email"
                type="email"
                {...register('email')}
                placeholder="john.doe@example.com"
              />
              {errors.email && (
                <p className="text-sm text-destructive">{errors.email.message}</p>
              )}
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="full_name">Full Name *</Label>
              <Input
                id="full_name"
                {...register('full_name')}
                placeholder="John Doe"
              />
              {errors.full_name && (
                <p className="text-sm text-destructive">{errors.full_name.message}</p>
              )}
            </div>

          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Role and Department</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="role">Role *</Label>
              <Select
                value={role}
                onValueChange={(value) => setValue('role', value as 'admin' | 'member')}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="admin">Administrator</SelectItem>
                  <SelectItem value="member">Member</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="department">Department</Label>
              <Select
                value={watch('department') || ''}
                onValueChange={(value) => setValue('department', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a department" />
                </SelectTrigger>
                <SelectContent>
                  {departments.map((dept) => (
                    <SelectItem key={dept} value={dept}>
                      {dept}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="is_active"
              checked={isActive}
              onChange={(e) => setValue('is_active', e.target.checked)}
              className="h-4 w-4 rounded border-gray-300"
            />
            <Label htmlFor="is_active">Active User</Label>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>
            {user ? 'Change Password' : 'Password'}
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="password">
                Password {!user && '*'}
              </Label>
              <Input
                id="password"
                type="password"
                {...register('password')}
                placeholder={user ? 'Leave empty to keep current' : '••••••••'}
              />
              {errors.password && (
                <p className="text-sm text-destructive">{errors.password.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="password_confirmation">Confirm Password</Label>
              <Input
                id="password_confirmation"
                type="password"
                {...register('password_confirmation')}
                placeholder="••••••••"
              />
              {errors.password_confirmation && (
                <p className="text-sm text-destructive">
                  {errors.password_confirmation.message}
                </p>
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-4">
        <Button type="submit" disabled={isLoading}>
          {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          {user ? 'Update' : 'Create User'}
        </Button>
      </div>
    </form>
  )
}
