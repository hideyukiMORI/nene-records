import type { AdminCommentDto, AdminCommentListDto, CommentDto, CommentListDto } from './api-types'
import type { AdminComment, AdminCommentList, Comment, CommentList } from './model'

export function mapCommentDtoToModel(dto: CommentDto): Comment {
  return {
    id: dto.id,
    entityId: dto.entity_id,
    authorName: dto.author_name,
    body: dto.body,
    isApproved: dto.is_approved,
    createdAt: dto.created_at,
  }
}

export function mapCommentListDtoToModel(dto: CommentListDto): CommentList {
  return {
    items: dto.items.map(mapCommentDtoToModel),
  }
}

export function mapAdminCommentDtoToModel(dto: AdminCommentDto): AdminComment {
  return {
    id: dto.id,
    entityId: dto.entity_id,
    authorName: dto.author_name,
    authorEmail: dto.author_email,
    body: dto.body,
    isApproved: dto.is_approved,
    createdAt: dto.created_at,
  }
}

export function mapAdminCommentListDtoToModel(dto: AdminCommentListDto): AdminCommentList {
  return {
    items: dto.items.map(mapAdminCommentDtoToModel),
  }
}
