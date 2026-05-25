import type { MessageCatalog } from './en'

/** Brazilian Portuguese — common UI, nav, auth, and errors. Missing keys fall back to English. */
export const ptBR: Partial<MessageCatalog> = {
  'common.actions.edit': 'Editar',
  'common.actions.delete': 'Excluir',
  'common.actions.cancel': 'Cancelar',
  'common.actions.retry': 'Tentar novamente',
  'common.actions.confirm': 'Confirmar',
  'common.actions.save': 'Salvar alterações',
  'common.actions.saving': 'Salvando…',
  'common.actions.create': 'Criar',
  'common.actions.creating': 'Criando…',
  'common.actions.add': 'Adicionar',
  'common.actions.adding': 'Adicionando…',
  'common.actions.remove': 'Remover',
  'common.actions.deleting': 'Excluindo…',
  'common.actions.backToHome': 'Voltar ao início',

  'common.field.name': 'Nome',
  'common.field.slug': 'Identificador',

  'common.error.unknown': 'Erro desconhecido',
  'common.error.unauthorized': 'Autenticação necessária. Por favor, faça login.',
  'common.error.forbidden': 'Você não tem permissão para realizar esta ação.',
  'common.error.notFound': 'O recurso solicitado não foi encontrado.',
  'common.error.conflict': 'Ocorreu um conflito. O recurso pode já existir.',
  'common.error.validation': 'Os dados enviados são inválidos.',
  'common.error.rateLimit': 'Muitas requisições. Por favor, aguarde e tente novamente.',
  'common.error.serverError': 'Ocorreu um erro no servidor. Por favor, tente novamente mais tarde.',

  'common.dialog.close': 'Fechar diálogo',

  'admin.nav.home': 'Início',
  'admin.nav.entityTypes': 'Tipos de entidade',
  'admin.nav.tags': 'Etiquetas',
  'admin.nav.settings': 'Configurações',
  'admin.nav.publicSite': 'Site público',
  'admin.nav.logout': 'Sair',

  'admin.auth.appTitle': 'NeNe Records Admin',
  'admin.auth.subtitle': 'Faça login para continuar',
  'admin.auth.emailLabel': 'E-mail',
  'admin.auth.emailPlaceholder': 'admin@example.com',
  'admin.auth.passwordLabel': 'Senha',
  'admin.auth.passwordPlaceholder': '••••••••',
  'admin.auth.signIn': 'Entrar',
  'admin.auth.signingIn': 'Entrando…',
  'admin.auth.invalidCredentials': 'E-mail ou senha incorretos',

  'admin.forbidden.title': 'Acesso negado',
  'admin.forbidden.description':
    'Você está conectado, mas sua conta não tem permissão para realizar esta ação.',
}
