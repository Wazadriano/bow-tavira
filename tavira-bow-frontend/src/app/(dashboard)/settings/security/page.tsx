import { useState } from 'react'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { post } from '@/lib/api'
import { useAuthUser, useAuthStore } from '@/stores/auth'
import { toast } from 'sonner'
import { ShieldCheck, ShieldOff, KeyRound, Copy, Eye, Loader2 } from 'lucide-react'

interface Enable2FAResponse {
  qr_code_url: string
  secret: string
}

interface Confirm2FAResponse {
  recovery_codes: string[]
}

type RecoveryCodesResponse = string[]

export default function SecuritySettingsPage() {
  const user = useAuthUser()
  const fetchUser = useAuthStore((state) => state.fetchUser)

  const is2FAEnabled = !!user?.two_factor_confirmed_at

  // Setup flow state
  const [setupStep, setSetupStep] = useState<'idle' | 'qr' | 'confirm' | 'recovery'>('idle')
  const [qrCodeUrl, setQrCodeUrl] = useState('')
  const [secret, setSecret] = useState('')
  const [confirmCode, setConfirmCode] = useState('')
  const [recoveryCodes, setRecoveryCodes] = useState<string[]>([])
  const [isLoading, setIsLoading] = useState(false)

  // Disable flow state
  const [showDisableDialog, setShowDisableDialog] = useState(false)
  const [disablePassword, setDisablePassword] = useState('')

  // View recovery codes state
  const [showRecoveryDialog, setShowRecoveryDialog] = useState(false)
  const [viewedRecoveryCodes, setViewedRecoveryCodes] = useState<string[]>([])

  const handleEnable2FA = async () => {
    setIsLoading(true)
    try {
      const data = await post<Enable2FAResponse>('/auth/2fa/enable')
      setQrCodeUrl(data.qr_code_url)
      setSecret(data.secret)
      setSetupStep('qr')
      toast.success('2FA setup initiated')
    } catch {
      toast.error('Failed to enable 2FA')
    } finally {
      setIsLoading(false)
    }
  }

  const handleConfirm2FA = async () => {
    if (confirmCode.length !== 6) {
      toast.error('Please enter a 6-digit code')
      return
    }
    setIsLoading(true)
    try {
      const data = await post<Confirm2FAResponse>('/auth/2fa/confirm', { code: confirmCode })
      setRecoveryCodes(data.recovery_codes)
      setSetupStep('recovery')
      await fetchUser()
      toast.success('2FA has been enabled successfully')
    } catch {
      toast.error('Invalid code. Please try again.')
    } finally {
      setIsLoading(false)
    }
  }

  const handleDisable2FA = async () => {
    if (!disablePassword.trim()) {
      toast.error('Password is required')
      return
    }
    setIsLoading(true)
    try {
      await post('/auth/2fa/disable', { password: disablePassword })
      setShowDisableDialog(false)
      setDisablePassword('')
      setSetupStep('idle')
      await fetchUser()
      toast.success('2FA has been disabled')
    } catch {
      toast.error('Invalid password or failed to disable 2FA')
    } finally {
      setIsLoading(false)
    }
  }

  const handleViewRecoveryCodes = async () => {
    setIsLoading(true)
    try {
      const data = await post<RecoveryCodesResponse>('/auth/2fa/recovery-codes')
      setViewedRecoveryCodes(data)
      setShowRecoveryDialog(true)
    } catch {
      toast.error('Failed to retrieve recovery codes')
    } finally {
      setIsLoading(false)
    }
  }

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text)
    toast.success('Copied to clipboard')
  }

  const copyAllCodes = (codes: string[]) => {
    navigator.clipboard.writeText(codes.join('\n'))
    toast.success('All recovery codes copied to clipboard')
  }

  const resetSetup = () => {
    setSetupStep('idle')
    setQrCodeUrl('')
    setSecret('')
    setConfirmCode('')
    setRecoveryCodes([])
  }

  return (
    <>
      <Header
        title="Security Settings"
        description="Manage your account security and two-factor authentication"
      />

      <div className="p-6 space-y-6 max-w-2xl">
        {/* Two-Factor Authentication Card */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <ShieldCheck className="h-5 w-5" />
                <div>
                  <CardTitle>Two-Factor Authentication</CardTitle>
                  <CardDescription className="mt-1">
                    Add an extra layer of security to your account using a TOTP authenticator app.
                  </CardDescription>
                </div>
              </div>
              {is2FAEnabled && (
                <Badge variant="green">Active</Badge>
              )}
            </div>
          </CardHeader>
          <CardContent>
            {/* 2FA Already Enabled */}
            {is2FAEnabled && setupStep === 'idle' && (
              <div className="space-y-4">
                <p className="text-sm text-muted-foreground">
                  Two-factor authentication is currently enabled on your account.
                  You will be asked for a verification code each time you sign in.
                </p>
                <div className="flex gap-3">
                  <Button
                    variant="outline"
                    onClick={handleViewRecoveryCodes}
                    disabled={isLoading}
                  >
                    {isLoading ? (
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    ) : (
                      <Eye className="mr-2 h-4 w-4" />
                    )}
                    View Recovery Codes
                  </Button>
                  <Button
                    variant="destructive"
                    onClick={() => setShowDisableDialog(true)}
                  >
                    <ShieldOff className="mr-2 h-4 w-4" />
                    Disable 2FA
                  </Button>
                </div>
              </div>
            )}

            {/* 2FA Not Enabled - Idle */}
            {!is2FAEnabled && setupStep === 'idle' && (
              <div className="space-y-4">
                <p className="text-sm text-muted-foreground">
                  Two-factor authentication is not enabled. Enable it to add an extra layer
                  of security to your account.
                </p>
                <Button onClick={handleEnable2FA} disabled={isLoading}>
                  {isLoading ? (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  ) : (
                    <KeyRound className="mr-2 h-4 w-4" />
                  )}
                  Enable 2FA
                </Button>
              </div>
            )}

            {/* Step 1: QR Code */}
            {setupStep === 'qr' && (
              <div className="space-y-4">
                <p className="text-sm text-muted-foreground">
                  Scan the QR code below with your authenticator app (Google Authenticator,
                  Authy, etc.), then enter the 6-digit code to confirm.
                </p>

                <div className="flex justify-center rounded-lg border bg-white p-4">
                  <img
                    src={`https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=${encodeURIComponent(qrCodeUrl)}`}
                    alt="2FA QR Code"
                    width={200}
                    height={200}
                  />
                </div>

                <div className="space-y-2">
                  <Label>Or enter this secret manually:</Label>
                  <div className="flex items-center gap-2">
                    <code className="flex-1 rounded border bg-muted px-3 py-2 text-sm font-mono break-all">
                      {secret}
                    </code>
                    <Button
                      variant="outline"
                      size="icon"
                      onClick={() => copyToClipboard(secret)}
                      title="Copy secret"
                    >
                      <Copy className="h-4 w-4" />
                    </Button>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="confirm-code">Verification Code</Label>
                  <div className="flex gap-2">
                    <Input
                      id="confirm-code"
                      placeholder="Enter 6-digit code"
                      value={confirmCode}
                      onChange={(e) => {
                        const value = e.target.value.replace(/\D/g, '').slice(0, 6)
                        setConfirmCode(value)
                      }}
                      maxLength={6}
                      className="max-w-[200px] font-mono text-center text-lg tracking-widest"
                    />
                    <Button onClick={handleConfirm2FA} disabled={isLoading || confirmCode.length !== 6}>
                      {isLoading ? (
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      ) : null}
                      Confirm
                    </Button>
                  </div>
                </div>

                <Button variant="ghost" onClick={resetSetup} className="mt-2">
                  Cancel
                </Button>
              </div>
            )}

            {/* Step 2: Recovery Codes (after successful confirm) */}
            {setupStep === 'recovery' && (
              <div className="space-y-4">
                <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950">
                  <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                    Save your recovery codes in a safe place. You will need them if you lose
                    access to your authenticator app. Each code can only be used once.
                  </p>
                </div>

                <div className="grid grid-cols-2 gap-2 rounded-lg border bg-muted p-4">
                  {recoveryCodes.map((code) => (
                    <code key={code} className="text-sm font-mono">
                      {code}
                    </code>
                  ))}
                </div>

                <div className="flex gap-3">
                  <Button
                    variant="outline"
                    onClick={() => copyAllCodes(recoveryCodes)}
                  >
                    <Copy className="mr-2 h-4 w-4" />
                    Copy All Codes
                  </Button>
                  <Button onClick={resetSetup}>
                    Done
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Disable 2FA Dialog */}
      <Dialog open={showDisableDialog} onOpenChange={setShowDisableDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Disable Two-Factor Authentication</DialogTitle>
            <DialogDescription>
              Enter your password to confirm disabling 2FA. This will make your account
              less secure.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2 py-4">
            <Label htmlFor="disable-password">Password</Label>
            <Input
              id="disable-password"
              type="password"
              placeholder="Enter your password"
              value={disablePassword}
              onChange={(e) => setDisablePassword(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter') handleDisable2FA()
              }}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => {
              setShowDisableDialog(false)
              setDisablePassword('')
            }}>
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={handleDisable2FA}
              disabled={isLoading || !disablePassword.trim()}
            >
              {isLoading ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : null}
              Disable 2FA
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* View Recovery Codes Dialog */}
      <Dialog open={showRecoveryDialog} onOpenChange={setShowRecoveryDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Recovery Codes</DialogTitle>
            <DialogDescription>
              Keep these codes safe. Each code can only be used once to sign in if you lose
              access to your authenticator app.
            </DialogDescription>
          </DialogHeader>
          <div className="grid grid-cols-2 gap-2 rounded-lg border bg-muted p-4">
            {viewedRecoveryCodes.map((code) => (
              <code key={code} className="text-sm font-mono">
                {code}
              </code>
            ))}
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => copyAllCodes(viewedRecoveryCodes)}
            >
              <Copy className="mr-2 h-4 w-4" />
              Copy All
            </Button>
            <Button onClick={() => setShowRecoveryDialog(false)}>
              Close
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  )
}
