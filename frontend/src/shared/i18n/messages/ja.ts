import type { MessageCatalog } from './en'

export const ja: Partial<MessageCatalog> = {
  // ── Common ──────────────────────────────────────────────────────────────
  'common.actions.edit': '編集',
  'common.actions.delete': '削除',
  'common.actions.cancel': 'キャンセル',
  'common.actions.retry': '再試行',
  'common.actions.confirm': '確認',
  'common.actions.save': '変更を保存',
  'common.actions.saving': '保存中…',
  'common.actions.create': '作成',
  'common.actions.creating': '作成中…',
  'common.actions.add': '追加',
  'common.actions.adding': '追加中…',
  'common.actions.remove': '削除',
  'common.actions.deleting': '削除中…',
  'common.actions.backToHome': 'ホームに戻る',

  'common.field.name': '名前',
  'common.field.slug': 'スラッグ',

  'common.error.unknown': '不明なエラー',
  'common.error.unauthorized': '認証が必要です。サインインしてください。',
  'common.error.forbidden': 'この操作を行う権限がありません。',
  'common.error.notFound': 'リソースが見つかりません。',
  'common.error.conflict': '競合が発生しました。リソースが既に存在する可能性があります。',
  'common.error.validation': '送信されたデータが無効です。',
  'common.error.rateLimit': 'リクエストが多すぎます。しばらく待ってから再試行してください。',
  'common.error.serverError': 'サーバーエラーが発生しました。後でもう一度お試しください。',

  'common.dialog.close': 'ダイアログを閉じる',

  // ── Admin nav ────────────────────────────────────────────────────────────
  'admin.nav.home': 'ホーム',
  'admin.nav.entityTypes': 'エンティティタイプ',
  'admin.nav.tags': 'タグ',
  'admin.nav.settings': '設定',
  'admin.nav.publicSite': 'パブリックサイト',
  'admin.nav.logout': 'ログアウト',

  // ── Auth ─────────────────────────────────────────────────────────────────
  'admin.auth.appTitle': 'NeNe Records Admin',
  'admin.auth.subtitle': 'サインインしてください',
  'admin.auth.emailLabel': 'メールアドレス',
  'admin.auth.emailPlaceholder': 'admin@example.com',
  'admin.auth.passwordLabel': 'パスワード',
  'admin.auth.passwordPlaceholder': '••••••••',
  'admin.auth.signIn': 'サインイン',
  'admin.auth.signingIn': 'サインイン中…',
  'admin.auth.invalidCredentials': 'メールアドレスまたはパスワードが正しくありません',

  // ── Forbidden ────────────────────────────────────────────────────────────
  'admin.forbidden.title': 'アクセス拒否',
  'admin.forbidden.description':
    'サインインしていますが、このアクションを実行する権限がありません。',

  // ── Home ─────────────────────────────────────────────────────────────────
  'admin.home.title': '管理ダッシュボード',
  'admin.home.description':
    'Phase 4 スキャフォールドが実行中です。エンティティタイプを使用して TanStack Query 経由の API 連携を確認してください。',
  'admin.home.openPublicSite': 'パブリックサイトを開く →',

  // ── Entity types ─────────────────────────────────────────────────────────
  'admin.entityTypes.pageTitle': 'エンティティタイプ',
  'admin.entityTypes.existingList.title': '既存のタイプ',
  'admin.entityTypes.existingList.loading': 'エンティティタイプを読み込み中…',
  'admin.entityTypes.existingList.error': 'エンティティタイプを読み込めませんでした',
  'admin.entityTypes.existingList.empty.title': 'エンティティタイプがまだありません',
  'admin.entityTypes.existingList.empty.description':
    '上のフォームから最初のエンティティタイプを作成してください。',
  'admin.entityTypes.createForm.title': 'エンティティタイプを作成',
  'admin.entityTypes.createForm.submit': 'エンティティタイプを作成',
  'admin.entityTypes.createForm.submitting': '作成中…',
  'admin.entityTypes.editForm.title': 'エンティティタイプを編集',
  'admin.entityTypes.editForm.save': '変更を保存',
  'admin.entityTypes.editForm.saving': '保存中…',
  'admin.entityTypes.delete.title': 'エンティティタイプを削除しますか？',
  'admin.entityTypes.delete.description': '「{{name}}」が削除されます。この操作は元に戻せません。',
  'admin.entityTypes.actions.fields': 'フィールド',
  'admin.entityTypes.actions.records': 'レコード',
  'admin.entityTypes.actions.backToTypes': 'エンティティタイプに戻る',

  // ── Entity records ───────────────────────────────────────────────────────
  'admin.entityRecords.backToTypes': 'エンティティタイプに戻る',
  'admin.entityRecords.backToRecords': 'レコードに戻る',
  'admin.entityRecords.recordCount.one': '{{count}} 件のレコード',
  'admin.entityRecords.recordCount.other': '{{count}} 件のレコード',
  'admin.entityRecords.list.title': '{{name}} のレコード',
  'admin.entityRecords.list.titleDefault': 'レコード',
  'admin.entityRecords.list.loading': 'レコードを読み込み中…',
  'admin.entityRecords.list.error': 'レコードを読み込めませんでした',
  'admin.entityRecords.list.empty.title': 'レコードがまだありません',
  'admin.entityRecords.list.empty.description': '上のボタンから最初のレコードを作成してください。',
  'admin.entityRecords.list.emptyFiltered.title': '一致するレコードがありません',
  'admin.entityRecords.list.emptyFiltered.description':
    'フィルターをクリアするか、別の条件を選択してください。',
  'admin.entityRecords.create.title': 'レコードを作成',
  'admin.entityRecords.create.description':
    'このエンティティタイプのレコードを作成します。フィールド値は後のフェーズで編集できます。',
  'admin.entityRecords.create.submit': 'レコードを作成',
  'admin.entityRecords.create.submitting': '作成中…',
  'admin.entityRecords.delete.title': 'レコードを削除しますか？',
  'admin.entityRecords.delete.description': 'レコード #{{id}} がソフト削除されます。',

  // ── Field definitions ────────────────────────────────────────────────────
  'admin.fieldDefs.pageTitle': 'フィールド',
  'admin.fieldDefs.schemaFor': '{{slug}} のスキーマ',
  'admin.fieldDefs.list.title': 'フィールド定義',
  'admin.fieldDefs.list.loading': 'フィールドを読み込み中…',
  'admin.fieldDefs.list.error': 'フィールドを読み込めませんでした',
  'admin.fieldDefs.list.empty.title': 'フィールドがまだありません',
  'admin.fieldDefs.list.empty.description':
    '上のフォームから最初のフィールド定義を追加してください。',
  'admin.fieldDefs.createForm.title': 'フィールドを追加',
  'admin.fieldDefs.createForm.fieldKeyLabel': 'フィールドキー',
  'admin.fieldDefs.createForm.dataTypeLabel': 'データ型',
  'admin.fieldDefs.createForm.submit': 'フィールドを追加',
  'admin.fieldDefs.createForm.submitting': '追加中…',
  'admin.fieldDefs.editForm.title': 'フィールドを編集',
  'admin.fieldDefs.editForm.save': '変更を保存',
  'admin.fieldDefs.editForm.saving': '保存中…',
  'admin.fieldDefs.delete.title': 'フィールドを削除しますか？',
  'admin.fieldDefs.delete.description': '「{{fieldKey}}」がスキーマから削除されます。',
  'admin.fieldDefs.dataType.text': 'テキスト',
  'admin.fieldDefs.dataType.int': '整数',
  'admin.fieldDefs.dataType.enum': '列挙型',
  'admin.fieldDefs.dataType.bool': 'ブール値',
  'admin.fieldDefs.dataType.datetime': '日時',

  // ── Tags ─────────────────────────────────────────────────────────────────
  'admin.tags.pageTitle': 'タグ',
  'admin.tags.list.title': '既存のタグ',
  'admin.tags.list.loading': 'タグを読み込み中…',
  'admin.tags.list.error': 'タグを読み込めませんでした',
  'admin.tags.list.empty.title': 'タグがまだありません',
  'admin.tags.list.empty.description': '上のフォームから最初のタグを作成してください。',
  'admin.tags.createForm.title': 'タグを作成',
  'admin.tags.createForm.submit': 'タグを作成',
  'admin.tags.createForm.submitting': '作成中…',
  'admin.tags.editForm.title': 'タグを編集',
  'admin.tags.editForm.save': '変更を保存',
  'admin.tags.editForm.saving': '保存中…',
  'admin.tags.delete.title': 'タグを削除しますか？',
  'admin.tags.delete.description':
    '「{{name}}」が削除されます。紐づくレコードはデータを保持しますが、このタグは失われます。',

  // ── Entity tags ──────────────────────────────────────────────────────────
  'admin.entityTags.title': 'タグ',
  'admin.entityTags.loading': 'タグを読み込み中…',
  'admin.entityTags.error': 'タグを読み込めませんでした',
  'admin.entityTags.noAttached': 'タグがまだ付いていません。',
  'admin.entityTags.noAvailable': '利用可能なタグがありません',
  'admin.entityTags.selectPlaceholder': 'タグを選択…',
  'admin.entityTags.addLabel': 'タグを追加',
  'admin.entityTags.addSubmit': 'タグを追加',
  'admin.entityTags.adding': '追加中…',
  'admin.entityTags.remove': '削除',

  // ── Settings ──────────────────────────────────────────────────────────────
  'admin.settings.pageTitle': 'サイト設定',
  'admin.settings.description':
    'パブリックページのサイト名、タグライン、デフォルトメタ説明、フッターコンテンツを設定します。',
  'admin.settings.loading': '設定を読み込み中…',
  'admin.settings.error': 'サイト設定を読み込めませんでした。',
  'admin.settings.save': '保存',
  'admin.settings.saving': '保存中…',
  'admin.settings.visibility.public': '公開',
  'admin.settings.visibility.adminOnly': '管理者のみ',
  'admin.settings.history.show': '履歴を表示',
  'admin.settings.history.hide': '履歴を非表示',
  'admin.settings.history.loading': '履歴を読み込み中…',
  'admin.settings.history.error': '変更履歴を読み込めませんでした。',
  'admin.settings.history.empty': 'まだ変更履歴がありません。',
}
