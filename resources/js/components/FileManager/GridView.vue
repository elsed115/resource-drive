<template>
  <div class="fm-grid p-4">
    <div
      v-for="item in items"
      :key="item.path"
      class="fm-cell"
      @dblclick="$emit('itemDoubleClick', item)"
      @contextmenu.prevent="$emit('itemContextMenu', $event, item)"
      tabindex="0"
    >
      <div class="fm-content">
        <div class="fm-icon-wrapper">
          <!-- thumb per le immagini -->
          <img
            v-if="isImage(item)"
            :src="item.url"
            class="fm-thumb"
            @error="onImageError"
            loading="lazy"
          />

          <!-- icona cartella -->
          <svg
            v-else-if="item.type === 'folder'"
            class="fm-icon text-primary-500"
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
            class="fm-icon icon-mask"
            :class="colorClass(item)"
            :style="maskStyle(item)"
          ></span>
        </div>

        <span class="fm-label">{{ item.name }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { isImage, getFileIcon, onImageError } from '../../utils/filetypes'

// Estrae estensione
function ext(item) {
  return item.name.split('.').pop().toLowerCase()
}

// Sceglie la classe colore in base al tipo
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

// Genera lo style object per la mask
function maskStyle(item) {
  const url = getFileIcon(item)
  return {
    maskImage:       `url(${url})`,
    WebkitMaskImage: `url(${url})`,
  }
}

defineProps(['items'])
defineEmits(['itemDoubleClick', 'itemContextMenu'])
</script>

<style scoped>
.fm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 1.5rem;
}
.fm-cell {
  position: relative;
  background: var(--fm-bg-alt-color);
  border: 1px solid transparent;
  border-radius: var(--fm-border-radius);
  overflow: hidden;
  transition: all .25s cubic-bezier(0.4, 0, 0.2, 1);
  aspect-ratio: 1/1;
  cursor: pointer;
  box-shadow: var(--fm-shadow-sm);
}
.fm-cell:hover,
.fm-cell:focus-visible {
  border-color: var(--fm-border-color-focus);
  transform: translateY(-4px);
  box-shadow: var(--fm-shadow-lg);
  outline: none;
}
.dark .fm-cell {
  background: color-mix(in srgb, var(--fm-bg-alt-color) 50%, transparent);
}
.fm-content {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0.75rem;
  text-align: center;
  overflow: hidden;
}
.fm-icon-wrapper {
  flex-grow: 1;
  width: 55%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.75rem;
}
.fm-icon {
  width: 100%;
  height: 100%;
  opacity: 0.9;
}
.fm-thumb {
  max-width: 100%;
  max-height: 100%;
  object-fit: cover;
  border-radius: var(--fm-border-radius-sm);
}
.fm-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--fm-text-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
  padding: 0.5rem;
  background: color-mix(in srgb, var(--fm-bg-color) 80%, transparent);
  position: absolute;
  bottom: 0;
  left: 0;
  border-top: 1px solid var(--fm-border-color);
}
.dark .fm-label {
  background: color-mix(in srgb, var(--fm-bg-alt-color) 80%, transparent);
}

/* ------------ MASK ICON ------------ */
.icon-mask {
  display: inline-block;
  background-color: currentColor;
  mask-repeat: no-repeat;
  mask-position: center;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  -webkit-mask-position: center;
  -webkit-mask-size: contain;
}
</style>
