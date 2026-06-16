import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { IconSearch } from '@/shared/ui/icons/Icons'

export interface SearchWidgetProps {
  widget: Widget
}

/**
 * Search box that navigates to the results page (/search?q=…) on submit.
 * Renders the magazine "pill" form from the public-home handoff: an inline
 * search icon + borderless input, submitted on Enter (no separate button), so
 * it fits the narrow sidebar column without overflowing.
 */
export function SearchWidget({ widget }: SearchWidgetProps) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [input, setInput] = useState('')

  const placeholder =
    typeof widget.settings['placeholder'] === 'string' && widget.settings['placeholder'] !== ''
      ? widget.settings['placeholder']
      : t('public.search.placeholder')

  return (
    <form
      className="search"
      role="search"
      onSubmit={(event) => {
        event.preventDefault()
        const q = input.trim()
        if (q !== '') {
          void navigate(`/search?q=${encodeURIComponent(q)}`)
        }
      }}
    >
      <IconSearch size={16} />
      <input
        id={`search-widget-${String(widget.id)}`}
        type="search"
        aria-label={t('public.search.label')}
        placeholder={placeholder}
        value={input}
        autoComplete="off"
        onChange={(event) => {
          setInput(event.target.value)
        }}
      />
    </form>
  )
}
