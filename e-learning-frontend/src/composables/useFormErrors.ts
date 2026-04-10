import { ref } from 'vue'

export function useFormErrors(initialErrors: Record<string, string> = {}) {
  const errors = ref<Record<string, string>>({ ...initialErrors })
  const submitError = ref('')

  function setErrors(apiErrors: Record<string, string[]>) {
    Object.keys(apiErrors).forEach((key) => {
      errors.value[key] = apiErrors[key][0]
    })
  }

  function setError(field: string, message: string) {
    errors.value[field] = message
  }

  function setSubmitError(message: string) {
    submitError.value = message
  }

  function clearError(field: string) {
    if (errors.value[field]) {
      delete errors.value[field]
    }
  }

  function clearErrors() {
    errors.value = {}
    submitError.value = ''
  }

  function handleApiError(err: any, defaultMessage = 'Có lỗi xảy ra, vui lòng thử lại') {
    const data = err.response?.data
    if (err.response?.status === 422 && data?.errors) {
      setErrors(data.errors)
    } else {
      setSubmitError(data?.message || defaultMessage)
    }
  }

  return {
    errors,
    submitError,
    setErrors,
    setError,
    setSubmitError,
    clearError,
    clearErrors,
    handleApiError
  }
}
