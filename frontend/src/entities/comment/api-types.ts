export interface CommentDto {
  id: number
  entity_id: number
  author_name: string
  body: string
  is_approved: boolean
  created_at: string
}

export interface AdminCommentDto {
  id: number
  entity_id: number
  author_name: string
  author_email: string
  body: string
  is_approved: boolean
  created_at: string
}

export interface CommentListDto {
  items: CommentDto[]
}

export interface AdminCommentListDto {
  items: AdminCommentDto[]
}

export interface PostCommentRequestDto {
  author_name: string
  author_email: string
  body: string
}
