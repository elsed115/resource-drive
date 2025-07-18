<template>
    <div class="fm-view-switcher">
        <button @click="$emit('update:modelValue', 'grid')" :class="['fm-view-btn', {'active': modelValue === 'grid'}]" title="Vista Griglia">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v2.5A2.25 2.25 0 004.25 9h2.5A2.25 2.25 0 009 6.75v-2.5A2.25 2.25 0 006.75 2h-2.5zm0 9A2.25 2.25 0 002 13.25v2.5A2.25 2.25 0 004.25 18h2.5A2.25 2.25 0 009 15.75v-2.5A2.25 2.25 0 006.75 11h-2.5zm9-9A2.25 2.25 0 0011 4.25v2.5A2.25 2.25 0 0013.25 9h2.5A2.25 2.25 0 0018 6.75v-2.5A2.25 2.25 0 0015.75 2h-2.5zM13.25 11a2.25 2.25 0 00-2.25 2.25v2.5a2.25 2.25 0 002.25 2.25h2.5a2.25 2.25 0 002.25-2.25v-2.5a2.25 2.25 0 00-2.25-2.25h-2.5z" clip-rule="evenodd" /></svg>
        </button>
        <button @click="$emit('update:modelValue', 'list')" :class="['fm-view-btn', {'active': modelValue === 'list'}]" title="Vista Elenco">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10zm0 5.25a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
        </button>
    </div>
</template>

<script setup>
defineProps(['modelValue']);
defineEmits(['update:modelValue']);
</script>

<style scoped>
.fm-view-switcher {
    display: flex;
    background-color: var(--fm-bg-alt-color, #f9fafb);
    border-radius: var(--fm-border-radius-sm, 0.5rem);
    padding: 0.25rem;
    border: 1px solid var(--fm-border-color, #e5e7eb);
}
.fm-view-btn {
    padding: 0.4rem 0.7rem;
    background: transparent;
    color: var(--fm-text-color-dim, #6b7285);
    border: none;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    cursor: pointer;
}
.fm-view-btn.active {
    background-color: var(--fm-bg-color, #fff);
    color: var(--fm-primary-color, #3b82f6);
    box-shadow: var(--fm-shadow-sm, 0 1px 2px 0 rgb(0 0 0 / 0.05));
}
.dark .fm-view-btn.active {
    background-color: var(--fm-bg-color);
}
</style>
