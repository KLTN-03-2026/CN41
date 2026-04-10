import { ref, computed } from 'vue'
import type { AdminCategory } from '@/types/admin-category.types'

export function useCategoryTree(allCategories: Readonly<{ value: AdminCategory[] }>) {
  const expandedIds = ref<Set<number>>(new Set())
  const allExpanded = ref(false)
  const searchQuery = ref('')

  // ── Helpers ────────────────────────────────────────────────────
  function hasChildren(parentId: number): boolean {
    const idx = allCategories.value.findIndex(c => c.id === parentId)
    if (idx < 0) return false
    const next = allCategories.value[idx + 1]
    if (!next) return false
    return next.depth > allCategories.value[idx].depth
  }

  function getChildCount(parentId: number): number {
    const idx = allCategories.value.findIndex(c => c.id === parentId)
    if (idx < 0) return 0
    const parentDepth = allCategories.value[idx].depth
    let count = 0
    for (let i = idx + 1; i < allCategories.value.length; i++) {
      if (allCategories.value[i].depth <= parentDepth) break
      if (allCategories.value[i].depth === parentDepth + 1) count++
    }
    return count
  }

  function isLastChild(cat: AdminCategory, visibleIdx: number, visibleList: AdminCategory[]): boolean {
    const next = visibleList[visibleIdx + 1]
    if (!next) return true
    return next.depth <= cat.depth
  }

  // ── Search matching ────────────────────────────────────────────
  const isSearching = computed(() => searchQuery.value.trim().length > 0)

  const matchedIds = computed(() => {
    const q = searchQuery.value.trim().toLowerCase()
    if (!q) return new Set<number>()

    const matched = new Set<number>()
    for (const cat of allCategories.value) {
      if (cat.name.toLowerCase().includes(q) || cat.slug.toLowerCase().includes(q)) {
        matched.add(cat.id)
      }
    }

    const withAncestors = new Set<number>(matched)
    for (const id of matched) {
      const idx = allCategories.value.findIndex(c => c.id === id)
      if (idx < 0) continue
      const targetDepth = allCategories.value[idx].depth
      for (let i = idx - 1; i >= 0; i--) {
        if (allCategories.value[i].depth < targetDepth) {
          withAncestors.add(allCategories.value[i].id)
        }
      }
    }
    return withAncestors
  })

  const matchCount = computed(() => {
    const q = searchQuery.value.trim().toLowerCase()
    if (!q) return 0
    return allCategories.value.filter(c =>
      c.name.toLowerCase().includes(q) || c.slug.toLowerCase().includes(q)
    ).length
  })

  // ── Visible list ───────────────────────────────────────────────
  const visibleCategories = computed(() => {
    if (isSearching.value) {
      return allCategories.value.filter(c => matchedIds.value.has(c.id))
    }

    const result: AdminCategory[] = []
    let skipBelow = -1

    for (const cat of allCategories.value) {
      if (skipBelow >= 0 && cat.depth > skipBelow) continue
      skipBelow = -1
      result.push(cat)
      if (hasChildren(cat.id) && !expandedIds.value.has(cat.id)) {
        skipBelow = cat.depth
      }
    }
    return result
  })

  // ── Expand / Collapse ──────────────────────────────────────────
  function collapseDescendants(parentId: number, s: Set<number>) {
    const idx = allCategories.value.findIndex(c => c.id === parentId)
    if (idx < 0) return
    const parentDepth = allCategories.value[idx].depth
    for (let i = idx + 1; i < allCategories.value.length; i++) {
      if (allCategories.value[i].depth <= parentDepth) break
      s.delete(allCategories.value[i].id)
    }
  }

  function toggleExpand(id: number) {
    const s = new Set(expandedIds.value)
    if (s.has(id)) {
      s.delete(id)
      collapseDescendants(id, s)
    } else {
      s.add(id)
    }
    expandedIds.value = s
    allExpanded.value = checkAllExpanded()
  }

  function checkAllExpanded(): boolean {
    for (const cat of allCategories.value) {
      if (hasChildren(cat.id) && !expandedIds.value.has(cat.id)) return false
    }
    return true
  }

  function expandAll() {
    const s = new Set<number>()
    for (const cat of allCategories.value) {
      if (hasChildren(cat.id)) s.add(cat.id)
    }
    expandedIds.value = s
    allExpanded.value = true
  }

  function toggleAll() {
    if (allExpanded.value) {
      expandedIds.value = new Set()
      allExpanded.value = false
    } else {
      expandAll()
    }
  }

  return {
    expandedIds,
    allExpanded,
    searchQuery,
    isSearching,
    matchCount,
    visibleCategories,
    hasChildren,
    getChildCount,
    isLastChild,
    toggleExpand,
    toggleAll,
    expandAll,
  }
}
