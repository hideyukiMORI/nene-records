import { type MessageKey, useTranslation } from '@/shared/i18n'
import { Select } from '@/shared/ui'

interface EnumSelectProps<T extends string> {
  id: string
  label: string
  value: T
  options: readonly T[]
  labelKeys: Record<T, MessageKey>
  disabled: boolean
  onChange: (value: T) => void
}

/**
 * A `<Select>` over a fixed string enum whose options carry i18n label keys.
 * Replaces the per-field `Select` + `options.map` + label-key record that the
 * block inspector repeated for kind / variant / layout / chartType / tone / size.
 */
export function EnumSelect<T extends string>({
  id,
  label,
  value,
  options,
  labelKeys,
  disabled,
  onChange,
}: EnumSelectProps<T>) {
  const { t } = useTranslation()
  return (
    <Select
      id={id}
      label={label}
      value={value}
      disabled={disabled}
      onChange={(event) => {
        onChange(event.target.value as T)
      }}
    >
      {options.map((option) => (
        <option key={option} value={option}>
          {t(labelKeys[option])}
        </option>
      ))}
    </Select>
  )
}
