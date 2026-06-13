import { createContext, useContext } from 'react'

/**
 * Carries the current page's primary markdown so region-placed widgets (e.g. the
 * TOC widget) can derive from the same content the main column renders. Empty by
 * default for pages that have no markdown body. Provide it with
 * `<PageContentContext.Provider value={markdown}>`.
 */
export const PageContentContext = createContext<string>('')

export function usePageContent(): string {
  return useContext(PageContentContext)
}
