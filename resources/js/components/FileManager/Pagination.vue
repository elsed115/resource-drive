<template>
    <div class="fm-pagination-nova">
        <nav class="flex justify-between items-center">
            <button
            :disabled="pagination.currentPage <= 1"
            @click="$emit('changePage', pagination.currentPage - 1)"
            class="text-xs font-bold py-3 px-4 focus:outline-none rounded-bl-lg focus:ring focus:ring-inset"
            :class="pagination.currentPage <= 1 ? 'text-gray-300 dark:text-gray-600' : 'text-primary-500 hover:text-primary-400 active:text-primary-600'"
            rel="prev"
            dusk="previous"
            >
            Precedente
            </button>
            <span class="text-xs px-4">{{ fromItem }}-{{ toItem }} di {{ pagination.total }}</span>
            <button
            :disabled="pagination.currentPage >= pagination.lastPage"
            @click="$emit('changePage', pagination.currentPage + 1)"
            class="text-xs font-bold py-3 px-4 focus:outline-none rounded-br-lg focus:ring focus:ring-inset"
            :class="pagination.currentPage >= pagination.lastPage ? 'text-gray-300 dark:text-gray-600' : 'text-primary-500 hover:text-primary-400 active:text-primary-600'"
            rel="next"
            dusk="next"
            >
            Avanti
            </button>
        </nav>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps(['pagination']);
defineEmits(['changePage']);

const fromItem = computed(() => {
    if (!props.pagination || props.pagination.total === 0) return 0;
    return (props.pagination.currentPage - 1) * props.pagination.perPage + 1;
});

const toItem = computed(() => {
    if (!props.pagination || props.pagination.total === 0) return 0;
    return Math.min(props.pagination.currentPage * props.pagination.perPage, props.pagination.total);
});
</script>

<style scoped>
.fm-pagination-nova {
    border-top: 1px solid var(--fm-border-color);
}
</style>
