import { useTranslation } from '@/shared/i18n'
import { Button, LoadingState, Stack, Text } from '@/shared/ui'
import type { RecordPageDisplayState } from '../hooks/useRecordPageDisplay'

const CHECK_LABEL_CLASS =
  'flex cursor-pointer items-start gap-3 rounded-md border border-transparent p-2 hover:bg-surface-raised'
const CHECK_INPUT_CLASS = 'mt-0.5 h-4 w-4 shrink-0 accent-accent'

/**
 * "Record page display" settings section (#775): the site-wide defaults for the
 * public record page's comments section and related-records block. Records can
 * override each with their tri-state show_comments / show_related.
 */
export function RecordPageDisplayView({
  config,
  setComments,
  setRelated,
  isLoading,
  isSaving,
  isDirty,
  save,
}: RecordPageDisplayState) {
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.recordPage.loading')}</LoadingState>
  }

  return (
    <Stack gap="sm">
      <Text muted>{t('admin.recordPage.description')}</Text>

      <fieldset className="m-0 flex flex-col gap-stack-xs border-0 p-0">
        <label htmlFor="record-page-comments" className={CHECK_LABEL_CLASS}>
          <input
            id="record-page-comments"
            type="checkbox"
            className={CHECK_INPUT_CLASS}
            checked={config.comments}
            disabled={isSaving}
            onChange={(event) => {
              setComments(event.target.checked)
            }}
          />
          <span className="text-sm font-medium text-text-primary">
            {t('admin.recordPage.commentsLabel')}
          </span>
        </label>
        <label htmlFor="record-page-related" className={CHECK_LABEL_CLASS}>
          <input
            id="record-page-related"
            type="checkbox"
            className={CHECK_INPUT_CLASS}
            checked={config.related}
            disabled={isSaving}
            onChange={(event) => {
              setRelated(event.target.checked)
            }}
          />
          <span className="text-sm font-medium text-text-primary">
            {t('admin.recordPage.relatedLabel')}
          </span>
        </label>
      </fieldset>

      <div>
        <Button onClick={save} disabled={!isDirty || isSaving}>
          {isSaving ? t('admin.recordPage.saving') : t('admin.recordPage.save')}
        </Button>
      </div>
    </Stack>
  )
}
