import { setupServer } from 'msw/node'
import { entityHandlers } from './handlers/entity'
import { entityTypeHandlers } from './handlers/entity-type'
import { fieldDefHandlers } from './handlers/field-def'
import { intFieldHandlers } from './handlers/int-field'
import { textFieldHandlers } from './handlers/text-field'

export const mswServer = setupServer(
  ...entityTypeHandlers,
  ...entityHandlers,
  ...fieldDefHandlers,
  ...textFieldHandlers,
  ...intFieldHandlers,
)
