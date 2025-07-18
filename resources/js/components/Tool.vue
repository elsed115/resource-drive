<template>
    <div class="fm-wrapper" @click="closeContextMenu">
        <h1 class="font-normal text-xl md:text-xl mb-3 flex items-center">{{ titolo }}</h1>

        <!-- Actions Header -->
        <div class="flex gap-2 mb-6">
            <div class="relative h-9 w-full md:w-1/3 md:shrink-0">
                <SearchInput v-model="searchQuery" />
            </div>
            <div class="inline-flex items-center gap-2 ml-auto">
                <ActionButton @click="showCreateFolderModal = true" dusk="create-folder-button">
                    <span class="md:inline-block">Crea Cartella</span>
                    <span class="inline-block md:hidden">Crea</span>
                </ActionButton>
                <ActionButton @click="triggerFileInput" dusk="upload-file-button">
                    <span class="md:inline-block">Carica File</span>
                    <span class="inline-block md:hidden">Carica</span>
                </ActionButton>
            </div>
        </div>

        <!-- File Manager Container -->
        <div 
            class="fm-container"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
        >
            <!-- Header with Breadcrumbs and View Switcher -->
            <header class="fm-header">
                <Breadcrumbs :crumbs="breadcrumbs" @navigate="navigateTo" />
                <div class="fm-actions">
                    <PerPageSelector v-model="perPage" :options="perPageOptions" />
                    <ViewSwitcher v-model="viewMode" />
                </div>
            </header>

            <!-- Dropzone -->
            <Dropzone v-if="isDragging" />

            <!-- Main Content -->
            <div v-if="loading" class="p-4">
                <LoadingSkeleton :view-mode="viewMode" />
            </div>
            <div v-else-if="paginatedItems.length > 0">
                <component 
                    :is="currentViewComponent"
                    :items="paginatedItems"
                    :editing-item="editingItem"
                    v-model:new-item-name="newItemName"
                    @item-double-click="handleDoubleClick"
                    @item-context-menu="showContextMenu"
                    @rename-item="renameItem"
                    @cancel-editing="cancelEditing"
                    @download-item="downloadItem"
                    @start-editing="startEditing"
                    @delete-item="openDeleteModal"
                />
            </div>
            <div
  v-else
  class="flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-gray-800 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg space-y-4"
>
  <!-- Heroicon • Outline • Folder Open -->
  

  <h3 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Cartella vuota
  </h3>
  <p class="text-sm text-gray-500 dark:text-gray-400">
    Trascina qui i file o
    <button
      @click="triggerFileInput"
      class="text-sky-500 hover:underline font-medium focus:outline-none"
    >
      seleziona manualmente
    </button>
  </p>
</div>



            <!-- Pagination -->
            <Pagination v-if="pagination && pagination.total > 0" :pagination="pagination" @change-page="changePage" />
        </div>

        <!-- Hidden File Input -->
        <input type="file" @change="handleFileUpload" ref="fileInput" class="hidden" multiple>

        <!-- Context Menu -->
        <ContextMenu v-if="contextMenu.show" :menu="contextMenu" @rename="openRenameModal" @download="downloadItem" @delete="openDeleteModal" />

        <!-- Modals -->
        <CreateFolderModal :show="showCreateFolderModal" v-model="newFolderName" @close="showCreateFolderModal = false" @create="createFolder" />
        <RenameModal :show="showRenameModal" v-model="newItemName" @close="showRenameModal = false" @rename="renameItem" />
        <DeleteModal :show="showDeleteModal" :item-name="contextMenu.item?.name" @close="showDeleteModal = false" @delete="deleteItem" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { useDebounceFn } from '@vueuse/core';

// Import child components (assuming they are created in the same directory)
// Note: These components would need to be created as separate .vue files.
// This example proceeds as if they exist.
import SearchInput from './FileManager/SearchInput.vue';
import ActionButton from './FileManager/ActionButton.vue';
import Breadcrumbs from './FileManager/Breadcrumbs.vue';
import PerPageSelector from './FileManager/PerPageSelector.vue';
import ViewSwitcher from './FileManager/ViewSwitcher.vue';
import Dropzone from './FileManager/Dropzone.vue';
import LoadingSkeleton from './FileManager/LoadingSkeleton.vue';
import GridView from './FileManager/GridView.vue';
import ListView from './FileManager/ListView.vue';
import Pagination from './FileManager/Pagination.vue';
import ContextMenu from './FileManager/ContextMenu.vue';
import CreateFolderModal from './FileManager/Modals/CreateFolderModal.vue';
import RenameModal from './FileManager/Modals/RenameModal.vue';
import DeleteModal from './FileManager/Modals/DeleteModal.vue';

import { formatSize, formatDate } from '../utils/formatters';
import { isImage, isPdf, isWord, isExcel, isArchive, onImageError } from '../utils/filetypes';

// Props
const props = defineProps(['resourceName', 'resourceId', 'fields', 'resource']);
console.log(props);
const fileManagerField = props.resource.fields?.find(f => f.component === 'resource-file-manager');
const titolo = fileManagerField?.label || 'Gestione File';
// State
const loading = ref(true);
const allItems = ref([]);
const breadcrumbs = ref([]);
const currentPath = ref('');
const pagination = ref(null);
const perPage = ref(15);
const perPageOptions = [5, 15, 30, 50, 100];
const isDragging = ref(false);
const searchQuery = ref('');
const viewMode = ref('grid'); // 'grid' or 'list'

// Modals State
const showCreateFolderModal = ref(false);
const newFolderName = ref('');
const showRenameModal = ref(false);
const newItemName = ref('');
const showDeleteModal = ref(false);

// Context Menu State
const contextMenu = ref({ show: false, x: 0, y: 0, item: null });

// Editing State
const editingItem = ref(null);

// Template Refs
const fileInput = ref(null);

// --- Computed Properties ---

const filteredItems = computed(() => {
    if (!searchQuery.value) {
        return allItems.value;
    }
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    return allItems.value.filter(item =>
        item.name.toLowerCase().includes(lowerCaseQuery)
    );
});

const paginatedItems = computed(() => {
    // If search is active, we might not have pagination from the server for the filtered list.
    // This example assumes server-side pagination is disabled when searching,
    // or that the search re-fetches with a query. For simplicity, we'll paginate the filtered list client-side.
    // A more robust solution would debounce search and fetch from the server.
    return filteredItems.value;
});

const currentViewComponent = computed(() => (viewMode.value === 'grid' ? GridView : ListView));
 
// --- API Communication ---

const getApiParams = (extraParams = {}) => ({
    resourceName: props.resourceName,
    resourceId: props.resourceId,
    path: currentPath.value,
    ...extraParams,
});

const fetchFiles = async () => {
    loading.value = true;
    try {
        const response = await Nova.request().get('/api/rfm/list', {
            params: getApiParams({ page: pagination.value?.currentPage || 1, perPage: perPage.value }),
        });
        allItems.value = response.data.files.sort((a, b) => {
            if (a.type === b.type) return a.name.localeCompare(b.name);
            return a.type === 'folder' ? -1 : 1;
        });
        breadcrumbs.value = response.data.breadcrumbs;
        pagination.value = response.data.pagination;
    } catch (error) {
        console.error('[FileManager] Error fetching files:', error);
        Nova.error('Impossibile caricare i file.');
    } finally {
        loading.value = false;
    }
};

const debouncedFetchFiles = useDebounceFn(fetchFiles, 300);

// --- Core Actions ---

const navigateTo = (path) => {
    currentPath.value = path;
    pagination.value.currentPage = 1;
    fetchFiles();
};

const changePage = (page) => {
    if (page < 1 || page > pagination.value.lastPage) return;
    pagination.value.currentPage = page;
    fetchFiles();
};

const handleDoubleClick = async (item) => {
    if (item.type === 'folder') {
        navigateTo(item.path);
    } else {
        try {
            const res = await Nova.request().get(`/api/rfm/generate-temp-link`, {
                params: getApiParams({ path: item.path }),
            });
            window.open(res.data.url, '_blank');
        } catch (error) {
            Nova.error('Impossibile generare il link per il file.');
        }
    }
};

// --- File & Folder Operations ---

const generateUniqueName = (name, existingNames) => {
    let finalName = name;
    let i = 1;
    const dotIndex = name.lastIndexOf('.');
    const baseName = dotIndex !== -1 ? name.substring(0, dotIndex) : name;
    const ext = dotIndex !== -1 ? name.substring(dotIndex) : '';

    while (existingNames.includes(finalName)) {
        finalName = `${baseName} (${i})${ext}`;
        i++;
    }
    return finalName;
};

const createFolder = async () => {
    if (!newFolderName.value) return Nova.error('Il nome della cartella non può essere vuoto.');
    
    const existingFolderNames = allItems.value.filter(i => i.type === 'folder').map(i => i.name);
    const finalName = generateUniqueName(newFolderName.value, existingFolderNames);

    loading.value = true;
    showCreateFolderModal.value = false;
    try {
        await Nova.request().post('/api/rfm/create-folder', getApiParams({ folderName: finalName }));
        Nova.success('Cartella creata con successo!');
        newFolderName.value = '';
        await fetchFiles();
    } catch (error) {
        Nova.error('Errore durante la creazione della cartella.');
    } finally {
        loading.value = false;
    }
};

const renameItem = async (itemToRename = null) => {
    // Usa l'item passato o quello in editing/context menu
    const item = itemToRename || editingItem.value || contextMenu.value.item;
    if (!newItemName.value) return Nova.error('Il nuovo nome non può essere vuoto.');
    if (!item) return;

    // Ottieni i nomi esistenti dello stesso tipo (escludendo quello che stai rinominando)
    const existingNames = allItems.value
        .filter(i => i.type === item.type && i.path !== item.path)
        .map(i => i.name);

    // Usa la funzione generateUniqueName per evitare duplicati
    const finalName = generateUniqueName(newItemName.value, existingNames);

    loading.value = true;
    showRenameModal.value = false;
    editingItem.value = null;

    try {
        await Nova.request().post('/api/rfm/rename', {
            path: item.path,
            newName: finalName,
            resourceName: props.resourceName,
            resourceId: props.resourceId,
        });
        Nova.success('Elemento rinominato con successo!');
        await fetchFiles();
    } catch (error) {
        Nova.error('Errore durante la rinomina.');
    } finally {
        loading.value = false;
    }
};

const deleteItem = async (itemToDelete = null) => {
    // Usa l'item passato o quello dal context menu
    const item = itemToDelete || contextMenu.value.item;
    if (!item) return;

    loading.value = true;
    showDeleteModal.value = false;
    try {
        await Nova.request().post('/api/rfm/delete', {
            path: item.path,
            type: item.type,
            resourceName: props.resourceName,
            resourceId: props.resourceId,
        });
        Nova.success('Elemento eliminato con successo!');
        await fetchFiles();
    } catch (error) {
        Nova.error('Errore durante l\'eliminazione.');
    } finally {
        loading.value = false;
    }
};

const downloadItem = (itemToDownload) => {
    const item = itemToDownload || contextMenu.value.item;
    if (!item || item.type !== 'file') return;
    
    const params = new URLSearchParams(getApiParams({ path: item.path }));
    const url = `/api/rfm/download?${params.toString()}`;
    
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', item.name);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    closeContextMenu();
};


// --- Upload Logic ---

const triggerFileInput = () => fileInput.value.click();
const handleFileUpload = (event) => uploadFiles(event.target.files);
const handleDrop = (event) => {
    isDragging.value = false;
    uploadFiles(event.dataTransfer.files);
};

const uploadFiles = (files) => {
    if (!files.length) return;
    loading.value = true;

    const existingFileNames = allItems.value.filter(item => item.type === 'file').map(item => item.name);

    const uploadPromises = Array.from(files).map(file => {
        const finalName = generateUniqueName(file.name, existingFileNames);
        const fileToUpload = finalName !== file.name ? new File([file], finalName, { type: file.type }) : file;

        const formData = new FormData();
        formData.append('file', fileToUpload);
        Object.entries(getApiParams()).forEach(([key, value]) => formData.append(key, value));
        
        return Nova.request().post('/api/rfm/upload', formData);
    });

    Promise.all(uploadPromises)
        .then(() => {
            Nova.success('File caricati con successo!');
            fetchFiles();
        })
        .catch(error => {
            console.error('[FileManager] Upload error:', error);
            Nova.error('Errore durante il caricamento di uno o più file.');
            loading.value = false;
        });
};

// --- UI Interaction ---

const showContextMenu = (event, item) => {
    contextMenu.value = { show: true, x: event.clientX, y: event.clientY, item };
};

const closeContextMenu = () => {
    if (contextMenu.value.show) {
        contextMenu.value.show = false;
    }
};

const openRenameModal = (itemToRename) => {
    const item = itemToRename || contextMenu.value.item;
    newItemName.value = item.name;
    showRenameModal.value = true;
    closeContextMenu();
};

const openDeleteModal = (itemToDelete) => {
    contextMenu.value.item = itemToDelete || contextMenu.value.item;
    showDeleteModal.value = true;
    closeContextMenu();
};

const startEditing = (item) => {
    newItemName.value = item.name;
    editingItem.value = item;
};

const cancelEditing = () => {
    editingItem.value = null;
    newItemName.value = '';
};

// --- Watchers & Lifecycle ---

watch(perPage, () => {
    pagination.value.currentPage = 1;
    fetchFiles();
});

watch(searchQuery, () => {
    // For a pure client-side search, no fetch is needed.
    // For server-side, you would call `debouncedFetchFiles` here.
});

onMounted(() => {
    fetchFiles();
});

</script>

<style scoped>
/* All the previous styles from the original component should be kept here */
/* They are well-structured and use CSS variables, so no major change is needed. */
/* For brevity, they are not repeated in this response. */
/* ... (paste original <style scoped> content here) ... */
    :root {
    --primary-500: #3b82f6;
    --primary-400: #60a5fa;
    --primary-600: #2563eb;
    }

    .fm-wrapper {
      --fm-font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
      --fm-border-radius: 0.75rem; /* .xl */
      --fm-border-radius-sm: 0.5rem; /* .lg */
      
      /* Colori Primari (es. Blu di Tailwind) */
      --fm-primary-color: #3b82f6;
      --fm-primary-color-hover: #2563eb;
      
      /* Colori Testo e Sfondo (Light Mode) */
      --fm-text-color: rgb(100,116,139);
      --fm-text-color-dim: #6b7285;
      --fm-bg-color: #ffffff;
      --fm-bg-alt-color: #f9fafb;
      --fm-border-color: #e5e7eb;
      --fm-border-color-focus: #93c5fd;
      
      /* Colore Pericolo */
      --fm-danger-color: #ef4444;
      --fm-danger-color-hover: #dc2626;

      /* Ombre */
      --fm-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --fm-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
      --fm-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
      --fm-shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
  }

  /*
    Se la classe .dark è applicata al body o html, questo selettore
    applicherà correttamente le variabili dark al wrapper del componente.
  */
  .dark .fm-wrapper {
      /* Colori Primari (Dark Mode) */
      --fm-primary-color: #60a5fa;
      --fm-primary-color-hover: #3b82f6;

      /* Colori Testo e Sfondo (Dark Mode) */
      --fm-text-color: rgb(148,163,184);
      --fm-text-color-dim: #9ca3af;
      --fm-bg-color: rgb(30, 42, 59);
      --fm-bg-alt-color: #1f2937;
      --fm-border-color: #374151;
      --fm-border-color-focus: #3b82f6;
  }

  /* ---------- WRAPPER ---------- */
  .fm-wrapper {
    font-family: var(--fm-font-family);
    border-radius: var(--fm-border-radius);

  }

  /* ---------- CONTAINER ---------- */
  .fm-container {
    background-color: var(--fm-bg-color);
    padding: 0;
    border-radius: var(--fm-border-radius);
    box-shadow: var(--fm-shadow-lg);
    overflow: hidden; /* Ensures children with rounded corners look right */
  }
  .dark .fm-container {
      background: var(--fm-bg-color);
      backdrop-filter: saturate(180%) blur(20px);
  }

  /* ---------- HEADER ---------- */
  .fm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    border-bottom: 1px solid var(--fm-border-color);
    padding: 0.75rem 1.25rem;
  }
  
  .fm-actions { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }

  /* ---------- TRANSITIONS ---------- */
  .fade-scale-enter-active, .fade-scale-leave-active { transition: all .2s ease-in-out; }
  .fade-scale-enter-from, .fade-scale-leave-to { opacity: 0; transform: scale(0.97); }

  /* ---------- INPUT HIDDEN ---------- */
  .hidden { display: none; }
</style>

