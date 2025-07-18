<template>
  <transition name="fade-scale">
    <div
      v-show="visible"
      class="fm-dropzone"
      @dragover.prevent
      @drop.prevent="onLocalDrop"
    >
      <div class="fm-drop-icon">📁</div>
      <div>Rilascia i file per caricarli</div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const emit = defineEmits(['drop']);
const visible = ref(false);
let dragCounter = 0;

function onDragEnter(e) {
  e.preventDefault();
  dragCounter++;
  if (dragCounter > 0) visible.value = true;
}

function onDragLeave(e) {
  e.preventDefault();
  dragCounter--;
  if (dragCounter <= 0) {
    dragCounter = 0;
    visible.value = false;
  }
}

function onDragOver(e) {
  e.preventDefault();
}

function onDrop(e) {
  e.preventDefault();
  dragCounter = 0;
  visible.value = false;
  // emetto i file al parent
  emit('drop', Array.from(e.dataTransfer.files));
}

function onLocalDrop(e) {
  onDrop(e);
}

onMounted(() => {
  window.addEventListener('dragenter', onDragEnter);
  window.addEventListener('dragleave', onDragLeave);
  window.addEventListener('dragover', onDragOver);
  window.addEventListener('drop', onDrop);
});

onUnmounted(() => {
  window.removeEventListener('dragenter', onDragEnter);
  window.removeEventListener('dragleave', onDragLeave);
  window.removeEventListener('dragover', onDragOver);
  window.removeEventListener('drop', onDrop);
});
</script>

<style scoped>
.fm-dropzone {
  border: 2px dashed var(--fm-border-color, #e5e7eb);
  border-radius: var(--fm-border-radius, 0.75rem);
  padding: 3rem;
  text-align: center;
  background-color: color-mix(in srgb, var(--fm-primary-color, #3b82f6) 5%, var(--fm-bg-alt-color, #f9fafb));
  color: var(--fm-text-color-dim, #6b7285);
  cursor: pointer;
  margin: 1.5rem;
  transition: all .2s ease-in-out;
}
.fm-dropzone:hover {
  transform: scale(1.01);
  border-color: var(--fm-primary-color, #3b82f6);
  border-style: solid;
  color: var(--fm-primary-color, #3b82f6);
  box-shadow: 0 0 25px color-mix(in srgb, var(--fm-primary-color, #3b82f6) 15%, transparent);
}
.fm-drop-icon {
  font-size: 3rem;
  margin-bottom: 0.75rem;
}
/* fade-scale transition */
.fade-scale-enter-active, .fade-scale-leave-active {
  transition: all .2s ease-in-out;
}
.fade-scale-enter-from, .fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.97);
}
</style>
