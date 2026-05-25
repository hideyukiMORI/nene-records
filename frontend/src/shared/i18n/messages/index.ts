import type { SupportedLocale } from '../locales'
import type { MessageCatalog } from './en'
import { en } from './en'
import { ja } from './ja'
import { fr } from './fr'
import { zhHans } from './zh-Hans'
import { ptBR } from './pt-BR'
import { de } from './de'

const MESSAGES: Record<SupportedLocale, Partial<MessageCatalog>> = {
  en,
  ja,
  fr,
  'zh-Hans': zhHans,
  'pt-BR': ptBR,
  de,
}

export function getMessages(locale: SupportedLocale): Partial<MessageCatalog> {
  return MESSAGES[locale]
}
