import { setupServer } from 'msw/node'
import { entityTypeHandlers } from './handlers/entity-type'

export const mswServer = setupServer(...entityTypeHandlers)
