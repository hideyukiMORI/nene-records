const { chromium } = require('@playwright/test')

const BASE = 'https://records.nene-suite.com'

async function check(page, url, expectSelector) {
  const consoleErrors = []
  const failed = []
  page.on('console', (m) => {
    if (m.type() === 'error') consoleErrors.push(m.text())
  })
  page.on('requestfailed', (r) => failed.push(`${r.url()} ${r.failure()?.errorText ?? ''}`))
  page.on('response', (r) => {
    const u = r.url()
    if (u.includes('/assets/') && r.status() >= 400) failed.push(`${u} -> ${r.status()}`)
  })

  await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 })
  await page.waitForSelector(expectSelector, { timeout: 15000 })

  // Base is derived from <base href> (CSP-safe); root must keep "/".
  const baseHref = await page.evaluate(() => document.querySelector('base')?.getAttribute('href') ?? null)
  const rootHtmlLen = await page.evaluate(() => document.getElementById('root')?.innerHTML.length ?? 0)
  const cspErrors = consoleErrors.filter((e) => /Content Security Policy/i.test(e))

  console.log(`\n== ${url} ==`)
  console.log('  SPA mounted (#root content len):', rootHtmlLen)
  console.log('  <base href>:', JSON.stringify(baseHref))
  console.log('  asset/4xx failures:', failed.length ? failed : 'none')
  console.log('  CSP errors:', cspErrors.length ? cspErrors.slice(0, 3) : 'none')

  return { ok: failed.length === 0 && rootHtmlLen > 0 && cspErrors.length === 0, baseHref, failed }
}

;(async () => {
  const browser = await chromium.launch()
  try {
    // Admin SPA shell (login page) — the asset-delivery change matters most here.
    const admin = await check(await (await browser.newContext()).newPage(), `${BASE}/admin`, 'input, form, [data-testid], main')
    // Public SSR record → SPA hydration.
    const pub = await check(await (await browser.newContext()).newPage(), `${BASE}/posts/1`, '#root')

    const allOk = admin.ok && pub.ok && admin.baseHref === '/' && pub.baseHref === '/'
    console.log('\n==== RESULT:', allOk ? '✅ PASS (root unbroken, <base href>="/", no CSP errors)' : '❌ FAIL', '====')
    process.exit(allOk ? 0 : 1)
  } finally {
    await browser.close()
  }
})().catch((e) => {
  console.error('ERR', e.message)
  process.exit(1)
})
