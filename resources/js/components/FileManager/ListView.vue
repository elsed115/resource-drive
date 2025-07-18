<template>
  <div class="fm-list">
    <div class="fm-list-header bg-gray-50 dark:bg-gray-800">
      <div class="fm-list-name">Nome</div>
      <div class="fm-list-size">Dimensione</div>
      <div class="fm-list-modified">Ultima Modifica</div>
      <div class="fm-list-actions">Azioni</div>
    </div>
    <div
      v-for="item in items"
      :key="item.path"
      class="fm-list-item"
      @dblclick="$emit('itemDoubleClick', item)"
    >
      <div class="fm-list-name">
        <!-- thumb per immagini -->
        <img
          v-if="isImage(item)"
          :src="item.url"
          class="fm-list-thumb"
          @error="onImageError"
          loading="lazy"
        />

        <!-- icona cartella -->
        <svg
          v-else-if="item.type === 'folder'"
          class="fm-list-icon text-primary-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="0.75"
            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
          />
        </svg>

        <!-- icona “maskata” per tutti gli altri tipi -->
        <span
          v-else
          class="fm-list-icon icon-mask"
          :class="colorClass(item)"
          :style="maskStyle(item)"
        ></span>

        <!-- inline rename -->
        <div v-if="editingItem && editingItem.path === item.path">
          <input
            type="text"
            :value="newItemName"
            @input="$emit('update:newItemName', $event.target.value)"
            @keyup.enter="$emit('renameItem')"
            @blur="$emit('cancelEditing')"
            @keyup.esc="$emit('cancelEditing')"
            class="fm-inline-rename-input"
            ref="inlineRenameInput"
          />
        </div>
        <span v-else>{{ item.name }}</span>
      </div>

      <div class="fm-list-size">{{ formatSize(item.size) }}</div>
      <div class="fm-list-modified">{{ formatDate(item.last_modified) }}</div>
      <div class="fm-list-actions">
        <button
          @click="$emit('downloadItem', item)"
          v-if="item.type === 'file'"
          class="fm-action-btn"
          title="Download"
        >
          <!-- SVG download invariato -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
            <path
              d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z"
            />
            <path
              d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"
            />
          </svg>
        </button>
        <button @click="$emit('startEditing', item)" class="fm-action-btn" title="Rinomina">
          <!-- SVG rename invariato -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
            <path
              fill-rule="evenodd"
              d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
        <button @click="$emit('deleteItem', item)" class="fm-action-btn fm-action-btn-danger" title="Elimina">
          <!-- SVG delete invariato -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
            <path
              fill-rule="evenodd"
              d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.58.22-2.365.468a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193v-.443A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { isImage, getFileIcon, onImageError } from '../../utils/filetypes'
import { formatSize, formatDate } from '../../utils/formatters'

const props = defineProps(['items', 'editingItem', 'newItemName'])
const emit  = defineEmits([
  'update:newItemName',
  'itemDoubleClick',
  'renameItem',
  'cancelEditing',
  'downloadItem',
  'startEditing',
  'deleteItem'
])

const inlineRenameInput = ref(null)
watch(() => props.editingItem, newItem => {
  if (newItem) nextTick(() => inlineRenameInput.value?.focus())
})

// helper per estensione
function ext(item) {
  return item.name.split('.').pop().toLowerCase()
}

// classe colore basata su estensione
function colorClass(item) {
  switch (ext(item)) {
    case 'pdf':  return 'text-red-500'
    case 'doc':  return 'text-sky-500'
    case 'docx': return 'text-sky-500'
    case 'xls':  return 'text-green-500'
    case 'xlsx': return 'text-green-500'
    case 'zip':
    case 'rar':
    case '7z':   return 'text-yellow-600'
    default:     return 'text-gray-400'
  }
}

// style per mask
function maskStyle(item) {
  const url = getFileIcon(item)
  return {
    maskImage:       `url(${url})`,
    WebkitMaskImage: `url(${url})`,
  }
}
</script>

<style scoped>
.fm-list { display: flex; flex-direction: column; }

.fm-list-header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 120px 180px 120px;
  gap: 1rem;
  padding: 0.5rem 1.25rem;
  border-bottom: 1px solid var(--fm-border-color);
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--fm-text-color-dim);
}

.fm-list-item {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 120px 180px 120px;
  gap: 1rem;
  align-items: center;
  padding: 0.5rem 1.25rem;
  border-bottom: 1px solid var(--fm-border-color);
  transition: background-color 0.2s ease;
}
.fm-list-item:hover { background-color: var(--fm-bg-alt-color); }

.fm-list-name {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-weight: 500;
  color: var(--fm-text-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.fm-list-thumb {
  width: 32px;
  height: 32px;
  object-fit: cover;
  border-radius: var(--fm-border-radius-sm);
  flex-shrink: 0;
}

.fm-list-icon,
.icon-mask {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

/* mask icon */
.icon-mask {
  background-color: currentColor;
  mask-repeat: no-repeat;
  mask-position: center;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  -webkit-mask-position: center;
  -webkit-mask-size: contain;
}

.fm-list-size,
.fm-list-modified {
  font-size: 0.875rem;
  color: var(--fm-text-color-dim);
}

.fm-list-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.5rem;
}

.fm-action-btn {
  padding: 0.5rem;
  border-radius: 50%;
  color: var(--fm-text-color-dim);
  transition: all 0.2s ease;
}
.fm-action-btn:hover { background-color: var(--fm-bg-color); color: var(--fm-primary-color); }
.fm-action-btn-danger:hover { color: var(--fm-danger-color); }

.fm-inline-rename-input {
  padding: 0.25rem 0.5rem;
  border: 1px solid var(--fm-border-color-focus);
  border-radius: var(--fm-border-radius-sm);
  outline: none;
}
</style>
