import { useState, type SyntheticEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { useLogin } from '@/entities/auth'
import { Button, Input, Stack, Text } from '@/shared/ui'

export function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const navigate = useNavigate()
  const login = useLogin()

  const handleSubmit = (e: SyntheticEvent) => {
    e.preventDefault()
    login.mutate(
      { email, password },
      {
        onSuccess: () => {
          void navigate('/')
        },
      },
    )
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-surface px-inline-md">
      <div className="w-full max-w-sm rounded-lg border border-border bg-surface-raised p-stack-lg shadow-sm">
        <Stack gap="lg">
          <Stack gap="xs">
            <Text as="h1" variant="heading-md">
              NeNe Records Admin
            </Text>
            <Text muted>サインインしてください</Text>
          </Stack>

          {login.isError && (
            <div
              role="alert"
              className="rounded-md border border-red-200 bg-red-50 px-inline-sm py-stack-xs text-sm text-red-700"
            >
              メールアドレスまたはパスワードが正しくありません
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <Stack gap="md">
              <Stack gap="xs">
                <label htmlFor="email" className="text-sm font-medium text-text-primary">
                  メールアドレス
                </label>
                <Input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => {
                    setEmail(e.target.value)
                  }}
                  placeholder="admin@example.com"
                  required
                />
              </Stack>

              <Stack gap="xs">
                <label htmlFor="password" className="text-sm font-medium text-text-primary">
                  パスワード
                </label>
                <Input
                  id="password"
                  type="password"
                  value={password}
                  onChange={(e) => {
                    setPassword(e.target.value)
                  }}
                  placeholder="••••••••"
                  required
                />
              </Stack>

              <Button type="submit" disabled={login.isPending} className="w-full">
                {login.isPending ? 'サインイン中…' : 'サインイン'}
              </Button>
            </Stack>
          </form>
        </Stack>
      </div>
    </div>
  )
}
