import { ref, computed, onMounted } from 'vue'
import debounce from 'lodash.debounce'

export function useFileManager(resourceName, resourceId) {
  // sub-path iniziale vuoto
  const root        = ref('')
  const defaultRoot = ref('')

  const dirs    = ref([])
  const files   = ref([])
  const loading = ref(false)
  const search  = ref('')
  const page    = ref(1)
  const perPage = ref(36)

  const fetchAll = async () => {
    loading.value = true
    const { data } = await window.Nova.request().get(
      `/nova-vendor/resource-file-manager/files/${resourceName}/${resourceId}`,
      { params: { path: root.value } }
    )
    // aggiorna root e dirs/files
    root.value        = data.root
    defaultRoot.value = data.root
    dirs.value  = data.dirs
    files.value = data.files
    loading.value = false
  }

  const basename = path => path.split('/').pop()

  const filtered = computed(() => {
    const q = search.value.toLowerCase()
    return {
      dirs: dirs.value.filter(d => basename(d).toLowerCase().includes(q)),
      files: files.value.filter(f => basename(f.path).toLowerCase().includes(q))
    }
  })

  const totalPages = computed(() =>
    Math.max(1, Math.ceil((filtered.value.dirs.length + filtered.value.files.length) / perPage.value))
  )

  const pagedDirs = computed(() =>
    filtered.value.dirs.slice((page.value-1)*perPage.value, page.value*perPage.value)
  )

  const pagedFiles = computed(() => {
    const used  = pagedDirs.value.length
    const start = (page.value-1)*perPage.value
                - filtered.value.dirs.slice(0, (page.value-1)*perPage.value).length
    return filtered.value.files.slice(Math.max(0, start), perPage.value - used)
  })

  const debouncedFetch = debounce(fetchAll, 300)

  function enterDir(dir) {
    // dir = "users/1/sub" → subPath = "sub"
    const sub = dir.replace(`${defaultRoot.value}/`, '')
    root.value = sub
    page.value = 1
    fetchAll()
  }

  function goUp() {
    const parts = root.value.split('/')
    parts.pop()
    root.value = parts.join('/')
    page.value = 1
    fetchAll()
  }

  onMounted(fetchAll)

  return {
    root,
    defaultRoot,
    dirs,
    files,
    loading,
    search,
    page,
    perPage,
    filtered,
    totalPages,
    pagedDirs,
    pagedFiles,
    basename,
    debouncedFetch,
    enterDir,
    goUp,
  }
}
