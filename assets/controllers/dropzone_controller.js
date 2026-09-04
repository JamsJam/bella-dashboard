import { Controller } from '@hotwired/stimulus';

const CHUNK_SIZE = 1024 * 1024;

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = [
        'dropzone', 
        'input', 
        'list', 
        'count', 
        'sendButton', 
        'clearButton', 
        'itemPrototype'
    ];
    static values = {
        itemPrototype: String,
        uploadUrl: String,
        csrfToken: String,
        redirectUrl: String,
    };

    //? ===============================
    //! ======== Lifecycle ============
    //? ===============================

    connect() {
        this.files = [];
        this.isUploading = false;
        this.lastProgress = -1;
        this.render();
    }

    disconnect() {
        this.revokePreviewUrls(this.files);
    }


    //? =========================================
    //! ======== Native File Selection ==========
    //? =========================================

    handleInputChange(event) {
        this.addFiles(Array.from(event.target.files || []));
        this.inputTarget.value = '';

    }

    openFileDialog(event) {
        if (event.target === this.inputTarget) {
            return;
        }

        event.preventDefault();

        if (this.isUploading) {
            return;
        }

        this.inputTarget.click();
    }


    //? =================================
    //! ======== Drag and Drop ==========
    //? =================================

    handleDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        this.dropzoneTarget.classList.add('is-drag-over');
    }

    handleDragLeave(event) {
        if (!this.dropzoneTarget.contains(event.relatedTarget)) {
            this.dropzoneTarget.classList.remove('is-drag-over');
        }
    }

    async handleDrop(event) {
        event.preventDefault();
        this.dropzoneTarget.classList.remove('is-drag-over');

        if (this.isUploading) {
            return;
        }

        const files = await this.extractDroppedFiles(event.dataTransfer);
        this.addFiles(files);
    }


    //? =================================
    //! ======== Queue Actions ==========
    //? =================================

    removeFile(event) {
        const fileId = event.currentTarget.dataset.fileId;
        const queuedFile = this.files.find((item) => item.id === fileId);

        if (!queuedFile || queuedFile.status === 'uploading') {
            return;
        }

        this.revokePreviewUrls([queuedFile]);
        this.files = this.files.filter((item) => item.id !== fileId);
        this.render();
    }

    clearFiles() {
        if (this.isUploading) {
            return;
        }

        this.revokePreviewUrls(this.files);
        this.files = [];
        this.render();
    }


    //? =================================
    //! ======== Upload Handling ========
    //? =================================

    async uploadFiles() {
        if (this.isUploading || this.files.length === 0) {
            return;
        }

        this.isUploading = true;
        this.lastProgress = -1;
        this.render();

        const uploadableFiles = this.files.filter((item) => item.status === 'pending' || item.status === 'error');
        const totalChunks = uploadableFiles.reduce((total, item) => total + this.getChunkCount(item.file), 0);
        let uploadedChunks = 0;

        if (totalChunks === 0) {
            this.isUploading = false;
            this.render();
            return;
        }

        this.logProgress(0);

        for (const queuedFile of uploadableFiles) {
            queuedFile.status = 'uploading';
            queuedFile.error = null;
            this.render();


            try {
                await this.uploadFile(queuedFile, () => {
                    uploadedChunks++;
                    this.logProgress(Math.round((uploadedChunks / totalChunks) * 100));
                });

                queuedFile.status = 'done';
            } catch (error) {
                queuedFile.status = 'error';
                queuedFile.error = error.message;
                console.error(`Upload failed for ${queuedFile.relativePath}`, error);
            }

            this.render();
        }

        this.isUploading = false;
        if (uploadedChunks === totalChunks) {
            this.logProgress(100);
            this.render();
            this.redirectAfterUpload();

            return;
        } else {
            console.warn('Avatar upload queue completed with errors');
        }
        this.render();
    }

    addFiles(files) {
        const pngFiles = files.filter((file) => this.isPngFile(file));
        const rejectedFiles = files.filter((file) => !this.isPngFile(file));

        rejectedFiles.forEach((file) => {
            console.warn(`Rejected non-PNG file: ${file.webkitRelativePath || file._relativePath || file.name}`);
        });

        pngFiles.forEach((file) => {
            this.files.push({
                id: this.createFileId(),
                file,
                relativePath: this.normalizeRelativePath(file.webkitRelativePath || file._relativePath || file.name),
                previewUrl: URL.createObjectURL(file),
                status: 'pending',
                error: null,
            });
        });

        this.render();
    }

    //? =================================
    //! ======== Chunk Handling =========
    //? =================================

    async uploadFile(queuedFile, onChunkUploaded) {
        const totalChunks = this.getChunkCount(queuedFile.file);

        for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
            const start = chunkIndex * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, queuedFile.file.size);
            const chunk = queuedFile.file.slice(start, end);

            await this.uploadChunk(queuedFile, chunk, chunkIndex, totalChunks);
            onChunkUploaded();
        }
    }

    async uploadChunk(queuedFile, chunk, chunkIndex, totalChunks) {
        const formData = new FormData();

        formData.append('chunk', chunk, `${queuedFile.id}.part${chunkIndex}`);
        formData.append('fileId', queuedFile.id);
        formData.append('originalName', queuedFile.file.name);
        formData.append('relativePath', queuedFile.relativePath);
        formData.append('chunkIndex', String(chunkIndex));
        formData.append('totalChunks', String(totalChunks));
        formData.append('fileSize', String(queuedFile.file.size));
        formData.append('mimeType', queuedFile.file.type || 'image/png');

        const response = await fetch(this.uploadUrlValue, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.csrfTokenValue,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.error) {
            throw new Error(data.error || `Chunk upload failed with status ${response.status}`);
        }

        return data;
    }


    //? =================================
    //! ======== List Rendering =========
    //? =================================

    render() {
        this.countTarget.textContent = String(this.files.length);
        this.sendButtonTarget.hidden = this.files.length === 0;
        this.clearButtonTarget.hidden = this.files.length === 0;
        this.sendButtonTarget.disabled = this.isUploading;
        this.clearButtonTarget.disabled = this.isUploading;
        this.dropzoneTarget.classList.toggle('is-disabled', this.isUploading);

        this.listTarget.innerHTML = this.files.map((queuedFile) => this.renderItem(queuedFile)).join('');
    }

    renderItem(queuedFile) {
        const disabled = this.isUploading || queuedFile.status === 'uploading';
        const error = queuedFile.error || '';
        return this.itemPrototypeValue
            .replaceAll('__FILE_ID__', this.escapeAttribute(queuedFile.id))
            .replaceAll('__FILE_PREVIEW__', this.escapeAttribute(queuedFile.previewUrl || ''))
            .replaceAll('__FILE_NAME__', this.escapeHtml(queuedFile.relativePath))
            .replaceAll('__FILE_META__', this.escapeHtml(`${this.formatFileSize(queuedFile.file.size)} - ${queuedFile.status}`))
            .replaceAll('__FILE_ERROR__', this.escapeHtml(error))
            .replaceAll(' data-error hidden', error ? ' data-error' : ' data-error hidden')
            .replaceAll('__DISABLED__', disabled ? 'disabled' : '');


    }


    //? =======================================
    //! ======== Dropped Folder Support =======
    //? =======================================

    async extractDroppedFiles(dataTransfer) {
        const items = Array.from(dataTransfer.items || []);

        if (items.length > 0 && typeof items[0].webkitGetAsEntry === 'function') {
            const entries = items
                .map((item) => item.webkitGetAsEntry())
                .filter(Boolean);
            const nestedFiles = await Promise.all(entries.map((entry) => this.readEntry(entry)));

            return nestedFiles.flat();
        }

        return Array.from(dataTransfer.files || []);
    }

    readEntry(entry) {
        if (entry.isFile) {
            return new Promise((resolve) => {
                entry.file((file) => {
                    const relativePath = entry.fullPath.replace(/^\/+/, '');

                    try {
                        Object.defineProperty(file, 'webkitRelativePath', {
                            value: relativePath,
                            configurable: true,
                        });
                    } catch {
                        file._relativePath = relativePath;
                    }

                    resolve([file]);
                });
            });
        }

        if (entry.isDirectory) {
            return this.readDirectory(entry);
        }

        return Promise.resolve([]);
    }

    readDirectory(directoryEntry) {
        const reader = directoryEntry.createReader();
        const entries = [];

        return new Promise((resolve, reject) => {
            const readBatch = () => {
                reader.readEntries(async (batch) => {
                    if (batch.length === 0) {
                        try {
                            const nestedFiles = await Promise.all(entries.map((entry) => this.readEntry(entry)));
                            resolve(nestedFiles.flat());
                        } catch (error) {
                            reject(error);
                        }

                        return;
                    }

                    entries.push(...batch);
                    readBatch();
                }, reject);
            };

            readBatch();
        });
    }


    //? =================================
    //! ======== Utility Methods ========
    //? =================================

    isPngFile(file) {
        return file.name.toLowerCase().endsWith('.png') || file.type === 'image/png';
    }

    normalizeRelativePath(path) {
        return path
            .replace(/\\/g, '/')
            .split('/')
            .filter((part) => part !== '' && part !== '.' && part !== '..')
            .join('/');
    }

    getChunkCount(file) {
        return Math.max(1, Math.ceil(file.size / CHUNK_SIZE));
    }

    logProgress(percent) {
        if (percent !== this.lastProgress) {
            this.lastProgress = percent;
        }
    }

    redirectAfterUpload() {
        if (!this.hasRedirectUrlValue || this.redirectUrlValue === '') {
            return;
        }

        window.location.assign(this.redirectUrlValue);
    }

    revokePreviewUrls(files) {
        files.forEach((queuedFile) => {
            if (queuedFile.previewUrl) {
                URL.revokeObjectURL(queuedFile.previewUrl);
                queuedFile.previewUrl = null;
            }
        });
    }

    createFileId() {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    }

    formatFileSize(size) {
        if (size < 1024) {
            return `${size} B`;
        }

        if (size < 1024 * 1024) {
            return `${(size / 1024).toFixed(1)} KB`;
        }

        return `${(size / 1024 / 1024).toFixed(1)} MB`;
    }

    escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    escapeAttribute(value) {
        return this.escapeHtml(value);
    }
}
