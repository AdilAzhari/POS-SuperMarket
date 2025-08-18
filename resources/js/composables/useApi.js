import { ref } from 'vue'
import axios from 'axios'

export function useApi(
  url,
  options = { immediate: true }
) {
  const data = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const execute = async () => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.get(url)
      data.value = response.data

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'An error occurred'
      throw err
    } finally {
      loading.value = false
    }
  }

  const refresh = execute

  if (options.immediate) {
    execute()
  }

  return {
    data,
    loading,
    error,
    execute,
    refresh,
  }
}

export function useApiPost() {
  const loading = ref(false)
  const error = ref(null)

  const post = async (url, payload) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.post(url, payload)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'An error occurred'
      throw err
    } finally {
      loading.value = false
    }
  }

  const put = async (url, payload) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.put(url, payload)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'An error occurred'
      throw err
    } finally {
      loading.value = false
    }
  }

  const del = async (url) => {
    try {
      loading.value = true
      error.value = null

      await axios.delete(url)
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'An error occurred'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    post,
    put,
    delete: del,
  }
}
