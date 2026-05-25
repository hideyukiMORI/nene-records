import type { MessageCatalog } from './en'

/** Simplified Chinese — common UI, nav, auth, and errors. Missing keys fall back to English. */
export const zhHans: Partial<MessageCatalog> = {
  'common.actions.edit': '编辑',
  'common.actions.delete': '删除',
  'common.actions.cancel': '取消',
  'common.actions.retry': '重试',
  'common.actions.confirm': '确认',
  'common.actions.save': '保存更改',
  'common.actions.saving': '保存中…',
  'common.actions.create': '创建',
  'common.actions.creating': '创建中…',
  'common.actions.add': '添加',
  'common.actions.adding': '添加中…',
  'common.actions.remove': '移除',
  'common.actions.deleting': '删除中…',
  'common.actions.backToHome': '返回主页',

  'common.field.name': '名称',
  'common.field.slug': '标识符',

  'common.error.unknown': '未知错误',
  'common.error.unauthorized': '需要身份验证，请登录。',
  'common.error.forbidden': '您没有执行此操作的权限。',
  'common.error.notFound': '未找到请求的资源。',
  'common.error.conflict': '发生冲突，资源可能已存在。',
  'common.error.validation': '提交的数据无效。',
  'common.error.rateLimit': '请求过多，请稍后重试。',
  'common.error.serverError': '发生服务器错误，请稍后重试。',

  'common.dialog.close': '关闭对话框',

  'admin.nav.home': '主页',
  'admin.nav.entityTypes': '实体类型',
  'admin.nav.tags': '标签',
  'admin.nav.settings': '设置',
  'admin.nav.publicSite': '公共站点',
  'admin.nav.logout': '退出登录',

  'admin.auth.appTitle': 'NeNe Records Admin',
  'admin.auth.subtitle': '请登录以继续',
  'admin.auth.emailLabel': '邮箱',
  'admin.auth.emailPlaceholder': 'admin@example.com',
  'admin.auth.passwordLabel': '密码',
  'admin.auth.passwordPlaceholder': '••••••••',
  'admin.auth.signIn': '登录',
  'admin.auth.signingIn': '登录中…',
  'admin.auth.invalidCredentials': '邮箱或密码错误',

  'admin.forbidden.title': '访问被拒绝',
  'admin.forbidden.description': '您已登录，但您的账户没有执行此操作的权限。',
}
