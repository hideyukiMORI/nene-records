import type { MessageCatalog } from './en'

/** French — common UI, nav, auth, and errors. Missing keys fall back to English. */
export const fr: Partial<MessageCatalog> = {
  'common.actions.edit': 'Modifier',
  'common.actions.delete': 'Supprimer',
  'common.actions.cancel': 'Annuler',
  'common.actions.retry': 'Réessayer',
  'common.actions.confirm': 'Confirmer',
  'common.actions.save': 'Enregistrer',
  'common.actions.saving': 'Enregistrement…',
  'common.actions.create': 'Créer',
  'common.actions.creating': 'Création…',
  'common.actions.add': 'Ajouter',
  'common.actions.adding': 'Ajout…',
  'common.actions.remove': 'Supprimer',
  'common.actions.deleting': 'Suppression…',
  'common.actions.backToHome': "Retour à l'accueil",

  'common.field.name': 'Nom',
  'common.field.slug': 'Identifiant',

  'common.error.unknown': 'Erreur inconnue',
  'common.error.unauthorized': 'Authentification requise. Veuillez vous connecter.',
  'common.error.forbidden': "Vous n'avez pas la permission d'effectuer cette action.",
  'common.error.notFound': 'La ressource demandée est introuvable.',
  'common.error.conflict': 'Un conflit est survenu. La ressource existe peut-être déjà.',
  'common.error.validation': 'Les données soumises sont invalides.',
  'common.error.rateLimit': 'Trop de requêtes. Veuillez patienter et réessayer.',
  'common.error.serverError': 'Une erreur serveur est survenue. Veuillez réessayer plus tard.',

  'common.dialog.close': 'Fermer le dialogue',

  'admin.nav.home': 'Accueil',
  'admin.nav.entityTypes': "Types d'entité",
  'admin.nav.tags': 'Étiquettes',
  'admin.nav.settings': 'Paramètres',
  'admin.nav.publicSite': 'Site public',
  'admin.nav.logout': 'Déconnexion',

  'admin.auth.appTitle': 'NeNe Records Admin',
  'admin.auth.subtitle': 'Connectez-vous pour continuer',
  'admin.auth.emailLabel': 'E-mail',
  'admin.auth.emailPlaceholder': 'admin@example.com',
  'admin.auth.passwordLabel': 'Mot de passe',
  'admin.auth.passwordPlaceholder': '••••••••',
  'admin.auth.signIn': 'Se connecter',
  'admin.auth.signingIn': 'Connexion…',
  'admin.auth.invalidCredentials': 'E-mail ou mot de passe incorrect',

  'admin.forbidden.title': 'Accès refusé',
  'admin.forbidden.description':
    "Vous êtes connecté, mais votre compte n'a pas la permission d'effectuer cette action.",
}
