<template>
    <div
        v-if="show"
        class="modal fixed inset-0 z-[60] px-3 md:px-0 py-3 md:py-6 overflow-x-hidden overflow-y-auto"
        style="background-color: rgba(0, 0, 0, 0.5);"
        role="dialog"
        @click.self="$emit('close')"
    >
            <div class="relative mx-auto z-20 max-w-2xl text-left">
            <form @submit.prevent="handleCreate" autocomplete="off" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="space-y-6">
                    <h3 class="uppercase tracking-wide font-bold text-xs border-b border-gray-100 dark:border-gray-700 py-4 px-8">Crea Nuova Cartella</h3>
                    <div class="px-8">
                        <label for="folderName" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nome Cartella</label>
                        <input
                            type="text"
                            id="folderName"
                            v-model="folderName"
                            class="mt-1 block w-full form-control form-input form-input-bordered"
                            required
                            ref="folderNameInput"
                        />
                    </div>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 px-6 py-3 flex" style="margin-top:30px">
                    <div class="flex items-center ml-auto">
                        <button type="button" @click="$emit('close')" class="ml-auto mr-3 appearance-none bg-transparent font-bold text-gray-400 hover:text-gray-300 active:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 dark:active:text-gray-600 dark:hover:bg-gray-800 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3">
                            Annulla
                        </button>
                        <button type="submit" class="border text-left appearance-none cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 relative disabled:cursor-not-allowed inline-flex items-center justify-center shadow h-9 px-3 bg-primary-500 border-primary-500 hover:[&:not(:disabled)]:bg-primary-400 hover:[&:not(:disabled)]:border-primary-400 text-white dark:text-gray-900">
                            Crea Cartella
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick, computed } from 'vue';

const props = defineProps(['show', 'modelValue']);
const emit = defineEmits(['close', 'create', 'update:modelValue']);

const folderNameInput = ref(null);

watch(() => props.show, (newVal) => {
    if (newVal) {
        nextTick(() => {
            folderNameInput.value?.focus();
        });
    }
});

const folderName = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const handleCreate = () => {
    emit('create');
};
</script>
