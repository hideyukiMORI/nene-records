import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { Button, Input } from '@/shared/ui'

export interface SearchWidgetProps {
  widget: Widget
}

/** Search box that navigates to the results page (/search?q=…) on submit. */
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
      className="flex items-end gap-inline-sm"
      role="search"
      onSubmit={(event) => {
        event.preventDefault()
        const q = input.trim()
        if (q !== '') {
          void navigate(`/search?q=${encodeURIComponent(q)}`)
        }
      }}
    >
      <div className="flex-1">
        <Input
          id={`search-widget-${String(widget.id)}`}
          label={t('public.search.label')}
          placeholder={placeholder}
          value={input}
          autoComplete="off"
          onChange={(event) => {
            setInput(event.target.value)
          }}
        />
      </div>
      <Button type="submit">{t('public.search.submit')}</Button>
    </form>
  )
}
