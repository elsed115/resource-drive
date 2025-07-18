<template>
    <div class="fm-breadcrumb">
        <button @click="$emit('navigate', '')" class="fm-btn-icon" title="Torna alla radice">
            <!-- Heroicons Solid • Home -->
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5 text-gray-500 hover:text-gray-700 transition-colors"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    d="M10.707 1.293a1 1 0 00-1.414 0L2 8.586V18a1 1 0 001 1h5a1 1 0 001-1v-4h2v4a1 1 0 001 1h5a1 1 0 001-1V8.586l-7.293-7.293z"
                />
            </svg>
        </button>
        <template v-for="(crumb, index) in crumbs">
            <span :key="'separator-' + index" class="opacity-50">/</span>
            <button :key="'crumb-' + index" @click="$emit('navigate', crumb.path)" class="fm-path">{{ crumb.name }}</button>
        </template>
    </div>
</template>

<script setup>
defineProps(['crumbs']);
defineEmits(['navigate']);
</script>

<style scoped>
.fm-breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--fm-text-color-dim, #6b7285);
}
.fm-btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  border-radius: 50%;
  padding: 0.5rem;
  color: var(--fm-text-color-dim, #6b7285);
  transition: color 0.2s ease;
}
.fm-btn-icon:hover {
  color: var(--fm-primary-color, #3b82f6);
}
.fm-path {
  font-weight: 500;
  color: var(--fm-text-color, #374151);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem 0.75rem;
  border-radius: var(--fm-border-radius-sm, 0.5rem);
  transition: background-color 0.2s ease, color 0.2s ease;
}
.fm-path:hover {
  background-color: var(--fm-bg-alt-color, #f9fafb);
  color: var(--fm-primary-color, #3b82f6);
}
</style>
