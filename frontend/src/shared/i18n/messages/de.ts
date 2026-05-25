import type { MessageCatalog } from './en'

/** German — common UI, nav, auth, and errors. Missing keys fall back to English. */
export const de: Partial<MessageCatalog> = {
  'common.actions.edit': 'Bearbeiten',
  'common.actions.delete': 'Löschen',
  'common.actions.cancel': 'Abbrechen',
  'common.actions.retry': 'Erneut versuchen',
  'common.actions.confirm': 'Bestätigen',
  'common.actions.save': 'Änderungen speichern',
  'common.actions.saving': 'Wird gespeichert…',
  'common.actions.create': 'Erstellen',
  'common.actions.creating': 'Wird erstellt…',
  'common.actions.add': 'Hinzufügen',
  'common.actions.adding': 'Wird hinzugefügt…',
  'common.actions.remove': 'Entfernen',
  'common.actions.deleting': 'Wird gelöscht…',
  'common.actions.backToHome': 'Zurück zur Startseite',

  'common.field.name': 'Name',
  'common.field.slug': 'Bezeichner',

  'common.error.unknown': 'Unbekannter Fehler',
  'common.error.unauthorized': 'Authentifizierung erforderlich. Bitte melden Sie sich an.',
  'common.error.forbidden': 'Sie haben keine Berechtigung, diese Aktion durchzuführen.',
  'common.error.notFound': 'Die angeforderte Ressource wurde nicht gefunden.',
  'common.error.conflict':
    'Es ist ein Konflikt aufgetreten. Die Ressource existiert möglicherweise bereits.',
  'common.error.validation': 'Die übermittelten Daten sind ungültig.',
  'common.error.rateLimit': 'Zu viele Anfragen. Bitte warten Sie und versuchen Sie es erneut.',
  'common.error.serverError':
    'Ein Serverfehler ist aufgetreten. Bitte versuchen Sie es später erneut.',

  'common.dialog.close': 'Dialog schließen',

  'admin.nav.home': 'Startseite',
  'admin.nav.entityTypes': 'Entitätstypen',
  'admin.nav.tags': 'Schlagwörter',
  'admin.nav.settings': 'Einstellungen',
  'admin.nav.publicSite': 'Öffentliche Website',
  'admin.nav.logout': 'Abmelden',

  'admin.auth.appTitle': 'NeNe Records Admin',
  'admin.auth.subtitle': 'Bitte melden Sie sich an',
  'admin.auth.emailLabel': 'E-Mail',
  'admin.auth.emailPlaceholder': 'admin@example.com',
  'admin.auth.passwordLabel': 'Passwort',
  'admin.auth.passwordPlaceholder': '••••••••',
  'admin.auth.signIn': 'Anmelden',
  'admin.auth.signingIn': 'Anmeldung…',
  'admin.auth.invalidCredentials': 'E-Mail oder Passwort falsch',

  'admin.forbidden.title': 'Zugriff verweigert',
  'admin.forbidden.description':
    'Sie sind angemeldet, aber Ihr Konto hat keine Berechtigung, diese Aktion durchzuführen.',
}
