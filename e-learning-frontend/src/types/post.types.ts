export interface PostCategory {
  id: number
  name: string
  slug: string
  description?: string
  created_at: string
  updated_at: string
}

export interface Tag {
  id: number
  name: string
  slug: string
  created_at: string
  updated_at: string
}

export interface Post {
  id: number
  title: string
  slug: string
  content: string
  thumbnail?: string
  author_id: number
  post_category_id?: number
  is_published: boolean
  published_at?: string
  views: number
  created_at: string
  updated_at: string
  author?: {
    id: number
    name: string
  }
  category?: PostCategory
  tags?: Tag[]
}

export interface PostComment {
  id: number
  post_id: number
  user_id: number
  user_type: string
  content: string
  parent_id?: number
  is_approved: boolean
  created_at: string
  updated_at: string
  commenter?: {
    id: number
    name: string
    avatar?: string
  }
  replies?: PostComment[]
}
