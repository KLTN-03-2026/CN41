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
  getPosts(params?: any) {
    return axios.get('/admin/posts', { params })
  },
  getPost(id: number | string) {
    return axios.get(`/admin/posts/${id}`)
  },
  createPost(data: any) {
    return axios.post('/admin/posts', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  updatePost(id: number | string, data: any) {
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
  getCategories(params?: any) {
    return axios.get('/admin/post-categories', { params })
  },
  getCategory(id: number) {
    return axios.get(`/admin/post-categories/${id}`)
  },
  createCategory(data: any) {
    return axios.post('/admin/post-categories', data)
  },
  updateCategory(id: number, data: any) {
    return axios.put(`/admin/post-categories/${id}`, data)
  },
  deleteCategory(id: number) {
    return axios.delete(`/admin/post-categories/${id}`)
  },

  // Tags
  getTags(params?: any) {
    return axios.get('/admin/tags', { params })
  },
  getTag(id: number) {
    return axios.get(`/admin/tags/${id}`)
  },
  createTag(data: any) {
    return axios.post('/admin/tags', data)
  },
  updateTag(id: number, data: any) {
    return axios.put(`/admin/tags/${id}`, data)
  },
  deleteTag(id: number) {
    return axios.delete(`/admin/tags/${id}`)
  },

  // Comments
  getComments(params?: any) {
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
  getClientPosts(params?: any) {
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
}

export default PostService
