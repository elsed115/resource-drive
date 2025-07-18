<template>
    <div class="relative h-9 w-full">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="24" class="inline-block absolute ml-2 text-gray-400" role="presentation" style="top: 4px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
            type="search"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            placeholder="Cerca..."
            class="appearance-none bg-white dark:bg-gray-800 shadow rounded-full h-8 w-full pl-10 focus:bg-white focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 dark:focus:bg-gray-800"
            spellcheck="false"
            aria-label="Search"
            dusk="search-input"
        />
    </div>
</template>

<script setup>
defineProps(['modelValue']);
defineEmits(['update:modelValue']);
</script>
