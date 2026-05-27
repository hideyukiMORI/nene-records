import { setupWorker } from 'msw/browser'
import { authHandlers } from '../../tests/msw/handlers/auth'
import { boolFieldHandlers } from '../../tests/msw/handlers/bool-field'
import { dateTimeFieldHandlers } from '../../tests/msw/handlers/datetime-field'
import { entityRelationHandlers } from '../../tests/msw/handlers/entity-relation'
import { entityTagHandlers } from '../../tests/msw/handlers/entity-tag'
import { entityHandlers } from '../../tests/msw/handlers/entity'
import { entityTypeHandlers } from '../../tests/msw/handlers/entity-type'
import { enumFieldHandlers } from '../../tests/msw/handlers/enum-field'
import { fieldDefHandlers } from '../../tests/msw/handlers/field-def'
import { intFieldHandlers } from '../../tests/msw/handlers/int-field'
import { tagHandlers } from '../../tests/msw/handlers/tag'
import { textFieldHandlers } from '../../tests/msw/handlers/text-field'
import { userHandlers } from '../../tests/msw/handlers/user'

export const worker = setupWorker(
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
