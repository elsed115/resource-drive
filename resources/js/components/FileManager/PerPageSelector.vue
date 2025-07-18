<template>
    <div class="fm-per-page">
        <label for="perPage" class="text-sm text-gray-600 dark:text-gray-400 mr-2">Elementi per pagina:</label>
        <select id="perPage" :value="modelValue" @change="$emit('update:modelValue', parseInt($event.target.value))" class="fm-select">
            <option v-for="option in options" :key="option" :value="option">{{ option }}</option>
        </select>
    </div>
</template>

<script setup>
defineProps(['modelValue', 'options']);
defineEmits(['update:modelValue']);
</script>

<style scoped>
.fm-per-page {
    display: flex;
    align-items: center;
}
.fm-select {
    background-color: var(--fm-bg-alt-color, #f9fafb);
    border: 1px solid var(--fm-border-color, #e5e7eb);
    border-radius: var(--fm-border-radius-sm, 0.5rem);
    padding: 0.4rem 0.7rem;
    color: var(--fm-text-color, #374151);
}
.dark .fm-select {
    background: var(--fm-bg-color);
}
</style>
