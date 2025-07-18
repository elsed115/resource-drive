export function formatSize(bytes) {
    if (bytes === 0 || !bytes) return '-';
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + ['B', 'KB', 'MB', 'GB'][i];
}

export function formatDate(timestamp) {
    if (!timestamp) return '-';
    return new Date(timestamp * 1000).toLocaleString();
}
