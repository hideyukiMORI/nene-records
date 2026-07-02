import { useTranslation } from '@/shared/i18n'
import { Button, Input, LoadingState, Select, Stack, Text } from '@/shared/ui'
import type { FrontPageState } from '../hooks/useFrontPage'

const RADIO_LABEL_CLASS =
  'flex cursor-pointer items-start gap-3 rounded-md border border-transparent p-2 hover:bg-surface-raised'
const RADIO_INPUT_CLASS = 'mt-0.5 h-4 w-4 shrink-0 accent-accent'

/**
 * "Home page display" settings section (#701): choose between the latest-feed
 * home (default) and pinning a published record as the site home, picked by
 * content type + a searchable record list. Persists the `front_page` setting.
 */
export function FrontPageView({
  mode,
  setMode,
  entityTypeId,
  setEntityTypeId,
  recordId,
  setRecordId,
  search,
  setSearch,
  typeOptions,
  recordOptions,
  isLoading,
  isRecordsLoading,
  isSaving,
  canSave,
  save,
}: FrontPageState) {
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.frontPage.loading')}</LoadingState>
  }

  return (
    <Stack gap="sm">
      <Text muted>{t('admin.frontPage.description')}</Text>

      <fieldset className="m-0 flex flex-col gap-stack-xs border-0 p-0">
        <label htmlFor="front-page-mode-latest" className={RADIO_LABEL_CLASS}>
          <input
            id="front-page-mode-latest"
            type="radio"
            name="front-page-mode"
            className={RADIO_INPUT_CLASS}
            checked={mode === 'latest'}
            disabled={isSaving}
            onChange={() => {
              setMode('latest')
            }}
          />
          <span className="text-sm font-medium text-text-primary">
            {t('admin.frontPage.modeLatest')}
          </span>
        </label>
        <label htmlFor="front-page-mode-page" className={RADIO_LABEL_CLASS}>
          <input
            id="front-page-mode-page"
            type="radio"
            name="front-page-mode"
            className={RADIO_INPUT_CLASS}
            checked={mode === 'page'}
            disabled={isSaving}
            onChange={() => {
              setMode('page')
            }}
          />
          <span className="text-sm font-medium text-text-primary">
            {t('admin.frontPage.modePage')}
          </span>
        </label>
      </fieldset>

      {mode === 'page' ? (
        <Stack gap="sm">
          <Select
            id="front-page-type"
            label={t('admin.frontPage.typeLabel')}
            value={entityTypeId === null ? '' : String(entityTypeId)}
            disabled={isSaving}
            onChange={(event) => {
              const value = event.target.value
              setEntityTypeId(value === '' ? null : Number(value))
              setRecordId(null)
            }}
          >
            {typeOptions.map((option) => (
              <option key={option.id} value={String(option.id)}>
                {option.name}
              </option>
            ))}
          </Select>

          <Input
            id="front-page-search"
            label={t('admin.frontPage.searchLabel')}
            type="text"
            value={search}
            placeholder={t('admin.frontPage.searchPlaceholder')}
            disabled={isSaving}
            onChange={(event) => {
              setSearch(event.target.value)
            }}
          />

          <Select
            id="front-page-record"
            label={t('admin.frontPage.recordLabel')}
            value={recordId === null ? '' : String(recordId)}
            disabled={isSaving || isRecordsLoading}
            onChange={(event) => {
              const value = event.target.value
              setRecordId(value === '' ? null : Number(value))
            }}
          >
            <option value="">
              {isRecordsLoading
                ? t('admin.frontPage.recordsLoading')
                : recordOptions.length === 0
                  ? t('admin.frontPage.noRecords')
                  : t('admin.frontPage.recordPlaceholder')}
            </option>
            {recordOptions.map((option) => (
              <option key={option.id} value={String(option.id)}>
                {option.label}
              </option>
            ))}
          </Select>
        </Stack>
      ) : null}

      <div>
        <Button onClick={save} disabled={!canSave}>
          {isSaving ? t('admin.frontPage.saving') : t('admin.frontPage.save')}
        </Button>
      </div>
    </Stack>
  )
}
