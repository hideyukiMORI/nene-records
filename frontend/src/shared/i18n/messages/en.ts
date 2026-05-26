/**
 * English message catalog — source of truth.
 *
 * Key naming: admin.{feature}.{element} | common.{element}
 * Param interpolation: {{paramName}}
 *
 * All other locales are `Partial<MessageCatalog>` and fall back to these values.
 */

export const en = {
  // ── Common ──────────────────────────────────────────────────────────────
  'common.actions.edit': 'Edit',
  'common.actions.delete': 'Delete',
  'common.actions.cancel': 'Cancel',
  'common.actions.retry': 'Retry',
  'common.actions.confirm': 'Confirm',
  'common.actions.save': 'Save changes',
  'common.actions.saving': 'Saving…',
  'common.actions.create': 'Create',
  'common.actions.creating': 'Creating…',
  'common.actions.add': 'Add',
  'common.actions.adding': 'Adding…',
  'common.actions.remove': 'Remove',
  'common.actions.deleting': 'Deleting…',
  'common.actions.backToHome': 'Back to home',

  'common.field.name': 'Name',
  'common.field.slug': 'Slug',

  'common.error.unknown': 'Unknown error',
  'common.error.unauthorized': 'Authentication required. Please sign in.',
  'common.error.forbidden': 'You do not have permission to perform this action.',
  'common.error.notFound': 'The requested resource was not found.',
  'common.error.conflict': 'A conflict occurred. The resource may already exist.',
  'common.error.validation': 'The submitted data is invalid.',
  'common.error.rateLimit': 'Too many requests. Please wait and try again.',
  'common.error.serverError': 'A server error occurred. Please try again later.',

  'common.dialog.close': 'Close dialog',

  // ── Admin nav ────────────────────────────────────────────────────────────
  'admin.nav.home': 'Home',
  'admin.nav.entityTypes': 'Entity types',
  'admin.nav.tags': 'Tags',
  'admin.nav.navigation': 'Navigation',
  'admin.nav.settings': 'Settings',
  'admin.nav.publicSite': 'Public site',
  'admin.nav.logout': 'Log out',
  'admin.theme.toggleDark': 'Switch to dark mode',
  'admin.theme.toggleLight': 'Switch to light mode',
  'admin.nav.openMenu': 'Open navigation menu',
  'admin.nav.closeMenu': 'Close navigation menu',

  // ── Auth (Login page) ────────────────────────────────────────────────────
  'admin.auth.appTitle': 'NeNe Records Admin',
  'admin.auth.subtitle': 'Sign in to continue',
  'admin.auth.emailLabel': 'Email',
  'admin.auth.emailPlaceholder': 'admin@example.com',
  'admin.auth.passwordLabel': 'Password',
  'admin.auth.passwordPlaceholder': '••••••••',
  'admin.auth.signIn': 'Sign in',
  'admin.auth.signingIn': 'Signing in…',
  'admin.auth.invalidCredentials': 'Invalid email or password',

  // ── Forbidden page ───────────────────────────────────────────────────────
  'admin.forbidden.title': 'Access denied',
  'admin.forbidden.description':
    'You are signed in, but your account does not have permission to perform this action.',

  // ── Home page ────────────────────────────────────────────────────────────
  'admin.home.title': 'Admin dashboard',
  'admin.home.description':
    'Phase 4 scaffold is running. Use Entity types to verify API integration via TanStack Query.',
  'admin.home.openPublicSite': 'Open public site →',

  // ── Entity types ─────────────────────────────────────────────────────────
  'admin.entityTypes.pageTitle': 'Entity types',
  'admin.entityTypes.existingList.title': 'Existing types',
  'admin.entityTypes.existingList.loading': 'Loading entity types…',
  'admin.entityTypes.existingList.error': 'Could not load entity types',
  'admin.entityTypes.existingList.empty.title': 'No entity types yet',
  'admin.entityTypes.existingList.empty.description':
    'Create your first entity type using the form above.',
  'admin.entityTypes.createForm.title': 'Create entity type',
  'admin.entityTypes.createForm.submit': 'Create entity type',
  'admin.entityTypes.createForm.submitting': 'Creating…',
  'admin.entityTypes.editForm.title': 'Edit entity type',
  'admin.entityTypes.editForm.save': 'Save changes',
  'admin.entityTypes.editForm.saving': 'Saving…',
  'admin.entityTypes.delete.title': 'Delete entity type?',
  'admin.entityTypes.delete.description': '"{{name}}" will be removed. This cannot be undone.',
  'admin.entityTypes.actions.fields': 'Fields',
  'admin.entityTypes.actions.records': 'Records',
  'admin.entityTypes.actions.backToTypes': 'Back to entity types',

  // ── Entity records ───────────────────────────────────────────────────────
  'admin.entityRecords.backToTypes': 'Back to entity types',
  'admin.entityRecords.backToRecords': 'Back to records',
  'admin.entityRecords.recordCount.one': '{{count}} record',
  'admin.entityRecords.recordCount.other': '{{count}} records',
  'admin.entityRecords.list.title': '{{name}} records',
  'admin.entityRecords.list.titleDefault': 'Records',
  'admin.entityRecords.list.loading': 'Loading records…',
  'admin.entityRecords.list.error': 'Could not load records',
  'admin.entityRecords.list.empty.title': 'No records yet',
  'admin.entityRecords.list.empty.description': 'Create your first record using the button above.',
  'admin.entityRecords.list.emptyFiltered.title': 'No matching records',
  'admin.entityRecords.list.emptyFiltered.description':
    'Try clearing the filters or selecting different criteria.',
  'admin.entityRecords.search.placeholder': 'Search records…',
  'admin.entityRecords.search.clear': 'Clear search',
  'admin.entityRecords.create.title': 'Create record',
  'admin.entityRecords.create.description':
    'Records are created for this entity type. Field values will be editable in a later phase.',
  'admin.entityRecords.create.submit': 'Create record',
  'admin.entityRecords.create.submitting': 'Creating…',
  'admin.entityRecords.delete.title': 'Delete record?',
  'admin.entityRecords.delete.description': 'Record #{{id}} will be soft-deleted.',

  // ── Field definitions ────────────────────────────────────────────────────
  'admin.fieldDefs.pageTitle': 'Fields',
  'admin.fieldDefs.schemaFor': 'Schema for {{slug}}',
  'admin.fieldDefs.list.title': 'Field definitions',
  'admin.fieldDefs.list.loading': 'Loading fields…',
  'admin.fieldDefs.list.error': 'Could not load fields',
  'admin.fieldDefs.list.empty.title': 'No fields yet',
  'admin.fieldDefs.list.empty.description': 'Add your first field definition using the form above.',
  'admin.fieldDefs.createForm.title': 'Add field',
  'admin.fieldDefs.createForm.fieldKeyLabel': 'Field key',
  'admin.fieldDefs.createForm.dataTypeLabel': 'Data type',
  'admin.fieldDefs.createForm.submit': 'Add field',
  'admin.fieldDefs.createForm.submitting': 'Adding…',
  'admin.fieldDefs.editForm.title': 'Edit field',
  'admin.fieldDefs.editForm.save': 'Save changes',
  'admin.fieldDefs.editForm.saving': 'Saving…',
  'admin.fieldDefs.delete.title': 'Delete field?',
  'admin.fieldDefs.delete.description': '"{{fieldKey}}" will be removed from the schema.',
  'admin.fieldDefs.dataType.text': 'Text',
  'admin.fieldDefs.dataType.markdown': 'Markdown',
  'admin.fieldDefs.dataType.int': 'Integer',
  'admin.fieldDefs.dataType.enum': 'Enum',
  'admin.fieldDefs.dataType.bool': 'Boolean',
  'admin.fieldDefs.dataType.datetime': 'Date & time',
  'admin.fieldDefs.dataType.image': 'Image',
  'admin.fieldDefs.dataType.file': 'File',
  'admin.fieldDefs.dataType.relation': 'Relation',

  // ── Media upload ─────────────────────────────────────────────────────────
  'admin.media.panelTitle': 'Media',
  'admin.media.uploadButton': 'Upload image',
  'admin.media.fileUploadButton': 'Upload file',
  'admin.media.uploading': 'Uploading…',
  'admin.media.uploadSuccess': 'Uploaded — URL copied to clipboard.',
  'admin.media.uploadError': 'Upload failed.',
  'admin.media.imagePreview': 'Image preview',
  'admin.media.noImage': 'No image selected',
  'admin.media.urlLabel': 'Image URL',
  'admin.media.fileDownload': 'Download',

  // ── Markdown editor ───────────────────────────────────────────────────────
  'admin.markdownEditor.preview': 'Preview',
  'admin.markdownEditor.write': 'Write',
  'admin.markdownEditor.empty': 'Nothing to preview.',

  // ── Tags ─────────────────────────────────────────────────────────────────
  'admin.tags.pageTitle': 'Tags',
  'admin.tags.list.title': 'Existing tags',
  'admin.tags.list.loading': 'Loading tags…',
  'admin.tags.list.error': 'Could not load tags',
  'admin.tags.list.empty.title': 'No tags yet',
  'admin.tags.list.empty.description': 'Create your first tag using the form above.',
  'admin.tags.createForm.title': 'Create tag',
  'admin.tags.createForm.submit': 'Create tag',
  'admin.tags.createForm.submitting': 'Creating…',
  'admin.tags.editForm.title': 'Edit tag',
  'admin.tags.editForm.save': 'Save changes',
  'admin.tags.editForm.saving': 'Saving…',
  'admin.tags.delete.title': 'Delete tag?',
  'admin.tags.delete.description':
    '"{{name}}" will be removed. Attached records keep their data but lose this tag.',

  // ── Entity tags (tags attached to a record) ──────────────────────────────
  'admin.entityTags.title': 'Tags',
  'admin.entityTags.loading': 'Loading tags…',
  'admin.entityTags.error': 'Could not load tags',
  'admin.entityTags.noAttached': 'No tags attached yet.',
  'admin.entityTags.noAvailable': 'No tags available',
  'admin.entityTags.selectPlaceholder': 'Select tag…',
  'admin.entityTags.addLabel': 'Add tag',
  'admin.entityTags.addSubmit': 'Add tag',
  'admin.entityTags.adding': 'Adding…',
  'admin.entityTags.remove': 'Remove',

  // ── Entity record detail ──────────────────────────────────────────────────
  'admin.entityRecord.backToRecords': 'Back to records',
  'admin.entityRecord.loading': 'Loading record…',
  'admin.entityRecord.error': 'Could not load record',
  'admin.entityRecord.notFound': 'Record not found.',
  'admin.entityRecord.id': 'Record #{{id}}',
  'admin.entityRecord.textFields.title': 'Field values',
  'admin.entityRecord.textFields.noFields.title': 'No editable fields defined',
  'admin.entityRecord.textFields.noFields.description':
    'Add field definitions for this entity type first.',
  'admin.entityRecord.textFields.saving': 'Saving…',
  'admin.entityRecord.textFields.save': 'Save values',

  // ── Entity status panel ───────────────────────────────────────────────────
  'admin.entityStatus.panelTitle': 'Publish status',
  'admin.entityStatus.slugLabel': 'Slug',
  'admin.entityStatus.slugPlaceholder': 'e.g. hello-world',
  'admin.entityStatus.saveSlug': 'Save slug',
  'admin.entityStatus.slugSaved': 'Saved.',
  'admin.entityStatus.publish': 'Publish',
  'admin.entityStatus.publishedAt': 'Published {{date}}',
  'admin.entityStatus.updateError': 'Failed to update status.',
  'admin.entityStatus.slugError': 'Failed to save slug. It may already be used by another record.',
  'admin.entityStatus.status.draft': 'Draft',
  'admin.entityStatus.status.published': 'Published',
  'admin.entityStatus.status.archived': 'Archived',

  // ── Relations (outgoing) ──────────────────────────────────────────────────
  'admin.relations.title': 'Relations',
  'admin.relations.loadingField': 'Loading {{fieldKey}}…',
  'admin.relations.fieldError': 'Could not load {{fieldKey}}',
  'admin.relations.noTargets': 'No targets linked yet.',
  'admin.relations.noTargetsAvailable': 'No targets available',
  'admin.relations.selectTarget': 'Select target…',
  'admin.relations.setTarget': 'Set target',
  'admin.relations.addTarget': 'Add target',
  'admin.relations.remove': 'Remove',
  'admin.relations.saving': 'Saving…',
  'admin.relations.relationType': 'relation · {{cardinality}} · target type #{{targetTypeId}}',

  // ── Relations (inverse / referenced-by) ──────────────────────────────────
  'admin.inverseRelations.title': 'Referenced by',
  'admin.inverseRelations.loadingPanel': 'Loading {{panelTitle}}…',
  'admin.inverseRelations.panelError': 'Could not load {{panelTitle}}',
  'admin.inverseRelations.noReferences': 'No records reference this target via {{fieldKey}}.',
  'admin.inverseRelations.open': 'Open',

  // ── Entity revisions ─────────────────────────────────────────────────────
  'admin.entityRevisions.title': 'Change history',
  'admin.entityRevisions.show': 'Show history',
  'admin.entityRevisions.hide': 'Hide history',
  'admin.entityRevisions.loading': 'Loading history…',
  'admin.entityRevisions.error': 'Could not load revision history.',
  'admin.entityRevisions.empty': 'No revisions yet.',

  // ── Entity SEO ────────────────────────────────────────────────────────────
  'admin.entitySeo.title': 'SEO',
  'admin.entitySeo.metaTitle': 'Meta title',
  'admin.entitySeo.metaTitle.placeholder': 'Override page title for search engines…',
  'admin.entitySeo.metaDescription': 'Meta description',
  'admin.entitySeo.metaDescription.placeholder': 'Override meta description for search engines…',
  'admin.entitySeo.save': 'Save SEO',
  'admin.entitySeo.saving': 'Saving…',
  'admin.entitySeo.saveSuccess': 'SEO settings saved.',

  // ── Settings ──────────────────────────────────────────────────────────────
  'admin.settings.pageTitle': 'Site settings',
  'admin.settings.description':
    'Configure site name, tagline, default meta description, and footer content for public pages.',
  'admin.settings.loading': 'Loading settings…',
  'admin.settings.error': 'Could not load site settings.',
  'admin.settings.save': 'Save',
  'admin.settings.saving': 'Saving…',
  'admin.settings.visibility.public': 'Public',
  'admin.settings.visibility.adminOnly': 'Admin only',
  'admin.settings.history.show': 'Show history',
  'admin.settings.history.hide': 'Hide history',
  'admin.settings.history.loading': 'Loading history…',
  'admin.settings.history.error': 'Could not load revision history.',
  'admin.settings.history.empty': 'No revisions yet.',

  // ── Navigation items ─────────────────────────────────────────────────────
  'admin.navigation.pageTitle': 'Navigation',
  'admin.navigation.description': 'Manage the navigation links shown in the public site header.',
  'admin.navigation.loading': 'Loading navigation items…',
  'admin.navigation.error': 'Could not load navigation items.',
  'admin.navigation.empty': 'No navigation items yet.',
  'admin.navigation.add': 'Add link',
  'admin.navigation.save': 'Save',
  'admin.navigation.saving': 'Saving…',
  'admin.navigation.delete': 'Delete',
  'admin.navigation.deleting': 'Deleting…',
  'admin.navigation.cancel': 'Cancel',
  'admin.navigation.label': 'Label',
  'admin.navigation.url': 'URL',
  'admin.navigation.displayOrder': 'Display order',
  'admin.navigation.form.labelPlaceholder': 'e.g. Blog',
  'admin.navigation.form.urlPlaceholder': 'e.g. /blog',
  'admin.navigation.createSuccess': 'Navigation item created.',
  'admin.navigation.updateSuccess': 'Navigation item updated.',
  'admin.navigation.deleteSuccess': 'Navigation item deleted.',
} as const

/** Complete message catalog type — derived from the English source of truth. */
export type MessageCatalog = typeof en
