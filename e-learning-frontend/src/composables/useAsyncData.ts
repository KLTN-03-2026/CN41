import { ref, type Ref } from 'vue'

interface AsyncDataOptions<T> {
  onError?: (err: any) => void
  transform?: (data: any) => T
}

export function useAsyncData<T>(
  fetchFn: (...args: any[]) => Promise<any>,
  initialData: T,
  options: AsyncDataOptions<T> = {}
) {
  const data: Ref<T> = ref(initialData) as Ref<T>
  const loading = ref(false)
  const error = ref<any>(null)

  async function execute(...args: any[]) {
    loading.value = true
    error.value = null
    try {
      const response = await fetchFn(...args)
      let resultData = response?.data?.data !== undefined ? response.data.data : response?.data
      if (options.transform) {
        resultData = options.transform(resultData)
      }
      data.value = resultData
      return response
    } catch (err: any) {
      error.value = err
      if (options.onError) {
        options.onError(err)
      }
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    data,
    loading,
    error,
    execute
  }
}
