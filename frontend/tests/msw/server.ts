import { setupServer } from 'msw/node'
import { entityHandlers } from './handlers/entity'
import { entityTypeHandlers } from './handlers/entity-type'
import { fieldDefHandlers } from './handlers/field-def'
import { textFieldHandlers } from './handlers/text-field'

export const mswServer = setupServer(
  ...entityTypeHandlers,
  ...entityHandlers,
  ...fieldDefHandlers,
  ...textFieldHandlers,
)
