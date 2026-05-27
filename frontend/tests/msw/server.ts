import { setupServer } from 'msw/node'
import { authHandlers } from './handlers/auth'
import { boolFieldHandlers } from './handlers/bool-field'
import { dateTimeFieldHandlers } from './handlers/datetime-field'
import { entityRelationHandlers } from './handlers/entity-relation'
import { entityTagHandlers } from './handlers/entity-tag'
import { entityHandlers } from './handlers/entity'
import { entityTypeHandlers } from './handlers/entity-type'
import { enumFieldHandlers } from './handlers/enum-field'
import { fieldDefHandlers } from './handlers/field-def'
import { intFieldHandlers } from './handlers/int-field'
import { tagHandlers } from './handlers/tag'
import { textFieldHandlers } from './handlers/text-field'
import { userHandlers } from './handlers/user'

export const mswServer = setupServer(
  ...authHandlers,
  ...entityTypeHandlers,
  ...entityHandlers,
  ...entityTagHandlers,
  ...entityRelationHandlers,
  ...fieldDefHandlers,
  ...textFieldHandlers,
  ...intFieldHandlers,
  ...enumFieldHandlers,
  ...boolFieldHandlers,
  ...dateTimeFieldHandlers,
  ...tagHandlers,
  ...userHandlers,
)
