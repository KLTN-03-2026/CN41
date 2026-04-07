import http from '@/plugins/axios'

export const uploadApi = {
  /** POST /admin/upload/video — multipart/form-data, field: file */
  video: (file, onProgress) =>
    http.post('/admin/upload/video', toFormData({ file }), {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: onProgress,
    }),

  /** POST /admin/upload/document — multipart/form-data, field: file */
  document: (file, onProgress) =>
    http.post('/admin/upload/document', toFormData({ file }), {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: onProgress,
    }),

  /**
   * POST /admin/upload/image — multipart/form-data
   * @param {File} file
   * @param {'images'|'thumbnails'|'avatars'|'banners'} [folder]
   */
  image: (file, folder = 'images', onProgress) => {
    const fd = toFormData({ file })
    if (folder) fd.append('folder', folder)
    return http.post('/admin/upload/image', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: onProgress,
    })
  },

  /** DELETE /admin/upload/{id} */
  destroy: (id) => http.delete(`/admin/upload/${id}`),
}

function toFormData(fields) {
  const fd = new FormData()
  for (const [key, val] of Object.entries(fields)) {
    fd.append(key, val)
  }
  return fd
}
