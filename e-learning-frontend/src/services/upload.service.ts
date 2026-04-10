import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse } from '@/types'

function toFormData(fields: Record<string, unknown>): FormData {
  const fd = new FormData()
  for (const [key, val] of Object.entries(fields)) {
    fd.append(key, val as string | Blob)
  }
  return fd
}

export const uploadService = {
  /** POST /api/v1/admin/upload/video — multipart/form-data, field: file */
  video: (file: File, onProgress?: (e: ProgressEvent) => void): Promise<AxiosResponse<ApiResponse<unknown>>> =>
    http.post('/admin/upload/video', toFormData({ file }), {
      headers: { 'Content-Type': undefined },
      timeout: 120000, // 2 phút cho upload file lớn
      onUploadProgress: onProgress,
    }),

  /** POST /api/v1/admin/upload/document — multipart/form-data, field: file */
  document: (file: File, onProgress?: (e: ProgressEvent) => void): Promise<AxiosResponse<ApiResponse<unknown>>> =>
    http.post('/admin/upload/document', toFormData({ file }), {
      headers: { 'Content-Type': undefined },
      timeout: 120000,
      onUploadProgress: onProgress,
    }),

  /**
   * POST /api/v1/admin/upload/image — multipart/form-data
   * @param folder 'images' | 'thumbnails' | 'avatars' | 'banners'
   */
  image: (file: File, folder = 'images', onProgress?: (e: ProgressEvent) => void): Promise<AxiosResponse<ApiResponse<unknown>>> => {
    const fd = toFormData({ file })
    if (folder) fd.append('folder', folder)
    return http.post('/admin/upload/image', fd, {
      headers: { 'Content-Type': undefined },
      timeout: 120000,
      onUploadProgress: onProgress,
    })
  },

  /** DELETE /api/v1/admin/upload/{id} */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/upload/${id}`),
}
