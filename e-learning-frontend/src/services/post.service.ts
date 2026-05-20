import axios from '@/plugins/axios'

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
  content: string
  parent_id?: number
  is_approved: boolean
  created_at: string
  updated_at: string
  user?: {
    id: number
    name: string
    avatar?: string
  }
  replies?: PostComment[]
}

const PostService = {
  // Posts
  getPosts(params?: Record<string, unknown>) {
    return axios.get('/admin/posts', { params })
  },
  getPost(id: number | string) {
    return axios.get(`/admin/posts/${id}`)
  },
  createPost(data: FormData) {
    return axios.post('/admin/posts', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  updatePost(id: number | string, data: FormData | Record<string, unknown>) {
    // Laravel spoofing for PUT with multipart/form-data
    if (data instanceof FormData) {
      data.append('_method', 'PUT')
      return axios.post(`/admin/posts/${id}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }
    return axios.put(`/admin/posts/${id}`, data)
  },
  deletePost(id: number) {
    return axios.delete(`/admin/posts/${id}`)
  },
  bulkDeletePosts(ids: number[]) {
    return axios.post('/admin/posts/bulk-delete', { ids })
  },

  // Categories
  getCategories(params?: Record<string, unknown>) {
    return axios.get('/admin/post-categories', { params })
  },
  getCategory(id: number) {
    return axios.get(`/admin/post-categories/${id}`)
  },
  createCategory(data: Record<string, unknown>) {
    return axios.post('/admin/post-categories', data)
  },
  updateCategory(id: number, data: Record<string, unknown>) {
    return axios.put(`/admin/post-categories/${id}`, data)
  },
  deleteCategory(id: number) {
    return axios.delete(`/admin/post-categories/${id}`)
  },

  // Tags
  getTags(params?: Record<string, unknown>) {
    return axios.get('/admin/tags', { params })
  },
  getTag(id: number) {
    return axios.get(`/admin/tags/${id}`)
  },
  createTag(data: Record<string, unknown>) {
    return axios.post('/admin/tags', data)
  },
  updateTag(id: number, data: Record<string, unknown>) {
    return axios.put(`/admin/tags/${id}`, data)
  },
  deleteTag(id: number) {
    return axios.delete(`/admin/tags/${id}`)
  },

  // Comments
  getComments(params?: Record<string, unknown>) {
    return axios.get('/admin/comments', { params })
  },
  approveComment(id: number) {
    return axios.patch(`/admin/comments/${id}/toggle-approval`)
  },
  deleteComment(id: number) {
    return axios.delete(`/admin/comments/${id}`)
  },
  bulkDeleteComments(ids: number[]) {
    return axios.post('/admin/comments/bulk-delete', { ids })
  },

  // Client API
  getClientPosts(params?: Record<string, unknown>) {
    return axios.get('/posts', { params })
  },
  getClientPost(slug: string) {
    return axios.get(`/posts/${slug}`)
  },
  getClientCategories() {
    return axios.get('/post-categories')
  },
  getClientTags() {
    return axios.get('/tags')
  },
  incrementViews(id: number) {
    return axios.post(`/posts/${id}/increment-views`)
  },
  storeComment(postId: number, content: string) {
    return axios.post(`/posts/${postId}/comments`, { content })
  },

  // Admin — approve/reject
  approvePost(id: number) {
    return axios.patch(`/admin/posts/${id}/approve`)
  },
  rejectPost(id: number, data: { rejection_reason: string }) {
    return axios.patch(`/admin/posts/${id}/reject`, data)
  },

  // Teacher portal — own posts
  getTeacherPosts(params?: Record<string, unknown>) {
    return axios.get('/teacher/posts', { params })
  },
  createTeacherPost(data: Record<string, unknown>) {
    return axios.post('/teacher/posts', data)
  },
  getTeacherPost(id: number | string) {
    return axios.get(`/teacher/posts/${id}`)
  },
  updateTeacherPost(id: number | string, data: Record<string, unknown>) {
    return axios.patch(`/teacher/posts/${id}`, data)
  },
  deleteTeacherPost(id: number) {
    return axios.delete(`/teacher/posts/${id}`)
  },
}

export default PostService
