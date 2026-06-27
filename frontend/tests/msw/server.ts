import { setupServer } from 'msw/node'
import { accountHandlers } from './handlers/account'
import { authHandlers } from './handlers/auth'
import { blocksFieldHandlers } from './handlers/blocks-field'
import { boolFieldHandlers } from './handlers/bool-field'
import { commentHandlers } from './handlers/comment'
import { dateTimeFieldHandlers } from './handlers/datetime-field'
import { entityRelationHandlers } from './handlers/entity-relation'
import { entityTagHandlers } from './handlers/entity-tag'
import { entityHandlers } from './handlers/entity'
import { entityTypeHandlers } from './handlers/entity-type'
import { enumFieldHandlers } from './handlers/enum-field'
import { fieldDefHandlers } from './handlers/field-def'
import { intFieldHandlers } from './handlers/int-field'
import { publicPermalinkResolveHandlers } from './handlers/public-permalink-resolve'
import { publicRecordHierarchyHandlers } from './handlers/public-record-hierarchy'
import { tagHandlers } from './handlers/tag'
import { textFieldHandlers } from './handlers/text-field'
import { userHandlers } from './handlers/user'
import { wxrImportHandlers } from './handlers/wxr-import'

export const mswServer = setupServer(
  ...accountHandlers,
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
  ...blocksFieldHandlers,
  ...tagHandlers,
  ...userHandlers,
  ...commentHandlers,
  ...wxrImportHandlers,
  ...publicRecordHierarchyHandlers,
  ...publicPermalinkResolveHandlers,
)
