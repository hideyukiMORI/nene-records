import { useEffect } from 'react'
import { composePublicDocumentTitle } from '@/shared/lib/public-document-title'

/**
 * Keeps `document.title` on the page's own title during hydration and client
 * navigation (#909). Before this, the only public writer was `PublicShell`'s
 * site-level effect, so every page collapsed to the bare site name after
 * hydration — and the GA4 page_view payload (which sends `document.title`)
 * became identical across all pages.
 *
 * On unmount the title falls back to the site name, so pages that don't set
 * their own title never inherit the previous page's.
 */
export function usePublicDocumentTitle(
  /**
   * `undefined` means "this render delegates the page to another component —
   * don't manage the title at all". Needed because parent effects run after
   * child effects: a delegating page that still set `siteName` would overwrite
   * the delegate's title (e.g. PublicBrowsePage → PublicRecordByPermalink).
   */
  pageTitle: string | null | undefined,
  siteName: string,
): void {
  useEffect(() => {
    if (pageTitle === undefined) {
      return
    }

    const composed = composePublicDocumentTitle(pageTitle, siteName)

    if (composed !== '') {
      document.title = composed
    }

    return () => {
      if (siteName !== '') {
        document.title = siteName
      }
    }
  }, [pageTitle, siteName])
}
