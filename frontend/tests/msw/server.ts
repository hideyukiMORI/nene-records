import { setupServer } from 'msw/node'
import { boolFieldHandlers } from './handlers/bool-field'
import { dateTimeFieldHandlers } from './handlers/datetime-field'
import { entityHandlers } from './handlers/entity'
import { entityTypeHandlers } from './handlers/entity-type'
import { enumFieldHandlers } from './handlers/enum-field'
import { fieldDefHandlers } from './handlers/field-def'
import { intFieldHandlers } from './handlers/int-field'
import { tagHandlers } from './handlers/tag'
import { textFieldHandlers } from './handlers/text-field'

export const mswServer = setupServer(
  ...entityTypeHandlers,
  ...entityHandlers,
  ...fieldDefHandlers,
  ...textFieldHandlers,
  ...intFieldHandlers,
  ...enumFieldHandlers,
  ...boolFieldHandlers,
  ...dateTimeFieldHandlers,
  ...tagHandlers,
)
