<template>
    <transition name="fade-scale">
        <div class="fm-context-menu" :style="{ top: menu.y + 'px', left: menu.x + 'px' }">
            <button @click="$emit('rename')" class="fm-context-item">Rinomina</button>
            <button @click="$emit('download', menu.item)" class="fm-context-item" v-if="menu.item.type === 'file'">Download</button>
            <button @click="$emit('delete')" class="fm-context-item fm-context-item-danger">Elimina</button>
        </div>
    </transition>
</template>

<script setup>
defineProps(['menu']);
defineEmits(['rename', 'download', 'delete']);
</script>

<style scoped>
.fm-context-menu {
    position: fixed;
    z-index: 1000;
    background-color: var(--fm-bg-color);
    border-radius: var(--fm-border-radius-sm);
    box-shadow: var(--fm-shadow-xl);
    padding: 0.5rem 0;
    min-width: 180px;
    border: 1px solid var(--fm-border-color);
}
.fm-context-item {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    color: var(--fm-text-color);
    transition: all 0.2s ease;
    background: none;
    border: none;
    cursor: pointer;
}
.fm-context-item:hover {
    background-color: var(--fm-bg-alt-color);
    color: var(--fm-primary-color);
}
.fm-context-item-danger:hover {
    color: var(--fm-danger-color);
}
.fade-scale-enter-active, .fade-scale-leave-active {
    transition: all .2s ease-in-out;
}
.fade-scale-enter-from, .fade-scale-leave-to {
    opacity: 0;
    transform: scale(0.97);
}
</style>
