import { ref, type Ref } from 'vue'

interface AsyncDataOptions<T, R> {
  onError?: (err: unknown) => void
  transform?: (data: R) => T
}

export function useAsyncData<T, R = unknown>(
  fetchFn: (...args: unknown[]) => Promise<{ data: { data: R } | R }>,
  initialData: T,
  options: AsyncDataOptions<T, R> = {}
) {
  const data: Ref<T> = ref(initialData) as Ref<T>
  const loading = ref(false)
  const error = ref<unknown>(null)

  async function execute(...args: unknown[]): Promise<{ data: { data: R } | R }> {
    loading.value = true
    error.value = null
    try {
      const response = await fetchFn(...args)
      const rawResponseData = response.data as Record<string, unknown>
      const rawData = rawResponseData?.data !== undefined 
        ? rawResponseData.data 
        : response.data
      
      let resultData: T
      if (options.transform) {
        resultData = options.transform(rawData as R)
      } else {
        resultData = rawData as unknown as T
      }
      
      data.value = resultData
      return response
    } catch (err: unknown) {
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
