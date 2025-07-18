<template>
    <div>
        <div v-if="viewMode === 'grid'" class="fm-grid">
            <div v-for="i in 12" :key="i" class="fm-cell fm-skeleton"></div>
        </div>
        <div v-else class="fm-list">
            <div v-for="i in 5" :key="i" class="fm-list-item fm-skeleton"></div>
        </div>
    </div>
</template>

<script setup>
defineProps(['viewMode']);
</script>

<style scoped>
@keyframes sk {
    0% { background-position: 100% 0 }
    100% { background-position: -100% 0 }
}
.fm-skeleton {
    background: linear-gradient(90deg, var(--fm-bg-alt-color, #f9fafb) 25%, color-mix(in srgb, var(--fm-text-color-dim, #9ca3af) 10%, var(--fm-bg-alt-color, #f9fafb)) 50%, var(--fm-bg-alt-color, #f9fafb) 75%);
    background-size: 200% 100%;
    animation: sk 1.5s infinite linear;
    cursor: default;
    box-shadow: none;
    border-color: var(--fm-border-color, #e5e7eb);
}
.fm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem;
}
.fm-cell {
    position: relative;
    background: var(--fm-bg-alt-color);
    border: 1px solid transparent;
    border-radius: var(--fm-border-radius);
    aspect-ratio: 1 / 1;
}
.fm-cell.fm-skeleton:hover {
    transform: none;
    box-shadow: none;
}
.fm-list {
    display: flex;
    flex-direction: column;
}
.fm-list-item {
    height: 50px;
    border-bottom: 1px solid var(--fm-border-color, #e5e7eb);
}
</style>
