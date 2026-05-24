import { setupServer } from 'msw/node'
import { entityHandlers } from './handlers/entity'
import { entityTypeHandlers } from './handlers/entity-type'

export const mswServer = setupServer(...entityTypeHandlers, ...entityHandlers)
