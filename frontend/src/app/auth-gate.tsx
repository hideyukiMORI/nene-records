export interface AuthGateProps {
  children: React.ReactNode
}

/** Fail-closed auth shell — expanded when API session endpoints land. */
export function AuthGate({ children }: AuthGateProps) {
  return children
}
