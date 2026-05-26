export interface Comment {
  id: number
  entityId: number
  authorName: string
  body: string
  isApproved: boolean
  createdAt: string
}

export interface AdminComment {
  id: number
  entityId: number
  authorName: string
  authorEmail: string
  body: string
  isApproved: boolean
  createdAt: string
}

export interface CommentList {
  items: Comment[]
}

export interface AdminCommentList {
  items: AdminComment[]
}

export interface PostCommentInput {
  entityId: number
  authorName: string
  authorEmail: string
  body: string
}
