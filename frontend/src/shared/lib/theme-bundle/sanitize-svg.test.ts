import { describe, expect, it } from 'vitest'
import { findDataUriSvgIssues, findSvgIssues } from './sanitize-svg'

describe('findSvgIssues', () => {
  it('flags <script>, event handlers, foreignObject, javascript: and external href', () => {
    expect(findSvgIssues('<svg><script>x()</script></svg>')).toContain(
      'data URI SVG contains <script>',
    )
    expect(findSvgIssues('<svg><rect onload="x()"/></svg>')).toContain(
      'data URI SVG contains an on* event handler',
    )
    expect(findSvgIssues('<svg><foreignObject/></svg>')).toContain(
      'data URI SVG contains <foreignObject>',
    )
    expect(findSvgIssues('<svg><a href="javascript:x()"/></svg>')).toContain(
      'data URI SVG contains a javascript: URI',
    )
    expect(findSvgIssues('<svg><use xlink:href="https://evil/x"/></svg>')).toContain(
      'data URI SVG contains an external/data href',
    )
  })

  it('returns nothing for a benign svg', () => {
    expect(findSvgIssues('<svg><path d="M0 0 L1 1"/><circle r="1"/></svg>')).toEqual([])
  })
})

describe('findDataUriSvgIssues', () => {
  it('ignores values without a data:image/svg+xml URI', () => {
    expect(findDataUriSvgIssues('url(#blur)')).toEqual([])
    expect(findDataUriSvgIssues('url(./logo.svg)')).toEqual([])
  })

  it('decodes percent-encoded svg data URIs and flags active content', () => {
    const value = `url("data:image/svg+xml,%3Csvg%20onload%3D%22steal()%22%3E%3C/svg%3E")`
    expect(findDataUriSvgIssues(value)).toContain('data URI SVG contains an on* event handler')
  })

  it('decodes base64 svg data URIs and flags <script>', () => {
    // <svg><script>alert(1)</script></svg>
    const b64 = btoa('<svg><script>alert(1)</script></svg>')
    const value = `background: url(data:image/svg+xml;base64,${b64})`
    expect(findDataUriSvgIssues(value)).toContain('data URI SVG contains <script>')
  })

  it('passes a benign data URI svg', () => {
    const value = `url("data:image/svg+xml,%3Csvg%3E%3Cpath%20d%3D%22M0%200%22/%3E%3C/svg%3E")`
    expect(findDataUriSvgIssues(value)).toEqual([])
  })
})
