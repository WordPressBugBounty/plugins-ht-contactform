'use strict';

document.addEventListener('DOMContentLoaded', () => {

    const { __ } = wp?.i18n;
            
    /**
     * Get file icon based on file type
     * @param {object} file 
     * @returns {string} file icon
     */
    const getFileIcon = (file) => {
        if (file.type.startsWith('image/')) {
            return `<img src="${URL.createObjectURL(file)}" alt="${file.name}" class="ht-form-file-preview-img">`;
        }
        return file.name.split('.').pop().toLowerCase();
    };

    /**
     * File Size Format
     * @param {number} size 
     * @returns {string} formatted file size
     */
    const formatFileSize = (size) => {
        if (size < 1024) return size + ' B';
        else if (size < 1048576) return (size / 1024).toFixed(1) + ' KB';
        else return (size / 1048576).toFixed(1) + ' MB';
    };
    
    /**
     * Validate File
     * @param {object} file 
     * @param {number} size 
     * @param {string} message 
     * @returns {object} validation result
     */
    const validateFileSize = (file, size, message) => {
        if (file.size > size * 1024 * 1024) {
            return {
                valid: false,
                message: message.replace('%s', size) || __(`File is too large. Maximum size is ${formatFileSize(size * 1024 * 1024)}MB.`, 'ht-contactform')
            };
        }
        return { valid: true };
    };

    const validateFileCount = (total, count, message) => {
        if (total > count) {
            return {
                valid: false,
                message: message.replace('%s', count) || __(`File count exceeds the limit of ${count}.`, 'ht-contactform')
            };
        }
        return { valid: true };
    };

    /**
     * File Preview
     * @param {object} file 
     * @param {string} error 
     * @returns {HTMLElement} file preview element
     */
    const filePreview = (file, error = null) => {
        const li = document.createElement('li');
        li.classList.add('ht-form-file-preview');
        li.innerHTML = `
            <div class="ht-form-file-preview-thumb">
                ${getFileIcon(file)}
            </div>
            <div class="ht-form-file-preview-details">
                <span class="ht-form-file-name">${file.name}</span>
                <div class="ht-form-file-progress">
                    <div class="ht-form-file-progress-bar"></div>
                </div>
                <p class="ht-form-file-progress-text"><span class="ht-form-file-progress-text-percentage"></span><span class="ht-form-file-progress-text-size">${formatFileSize(file.size)}</span></p>
                ${error ? `<span class="ht-form-file-error-message">${error}</span>` : ''}
            </div>
        `;
        return li;
    };

    /**
     * Error Preview
     * @param {string} error 
     * @returns {HTMLElement} error preview element
     */
    const errorPreview = (error = null) => {
        const li = document.createElement('li');
        li.classList.add('ht-form-file-error-preview');
        li.innerHTML = error;
        return li;
    };

    /**
     * Remove File
     * @param {Object} file 
     */
    const removeFile = (file) => {
        const fileUpload = file.closest('.ht-form-elem-file-upload');
        const input = fileUpload.querySelector('input[type="file"]');
        const maxFileCount = input.getAttribute('data-max-file-count');
        const maxFileCountMessage = input.getAttribute('data-max-file-count-message');
        const maxFileSize = input.getAttribute('data-max-file-size');
        const maxFileSizeMessage = input.getAttribute('data-max-file-size-message');
        const allowTypesMessage = input.getAttribute('data-allow-types-message');
        const uploadLocation = input.getAttribute('data-upload-location');
        
        // State
        const state = {
            tempFiles: [],
            uploading: false
        };

        const previewList = fileUpload.closest('.ht-form-elem-content').querySelector('.ht-form-elem-file-list');
        const li = file.closest('.ht-form-file-preview');
        li.remove();
        
        const hiddenInput = fileUpload.querySelector(`input[type="hidden"][value="${file.value}"]`);
        if (hiddenInput) {
            hiddenInput.remove();
        }
    };
    
    // File Upload
    if(document.querySelectorAll('.ht-form-elem-file-upload')) {
        document.querySelectorAll('.ht-form-elem-file-upload').forEach((fileUpload) => {
            const input = fileUpload.querySelector('input[type="file"]');
            const maxFileCount = input.getAttribute('data-max-file-count');
            const maxFileCountMessage = input.getAttribute('data-max-file-count-message');
            const maxFileSize = input.getAttribute('data-max-file-size');
            const maxFileSizeMessage = input.getAttribute('data-max-file-size-message');
            const allowTypesMessage = input.getAttribute('data-allow-types-message');
            const uploadLocation = input.getAttribute('data-upload-location');
            
            // State
            const state = {
                tempFiles: [],
                uploading: false,
                uploadQueue: []
            };

            // Preview List
            const previewList = fileUpload.closest('.ht-form-elem-content').querySelector('.ht-form-elem-file-list');

            // Input Change
            input.addEventListener('change', function(e) {
                const files = Array.from(this.files);
                
                if (files.length === 0) return;
                
                // Check if adding these files would exceed the maximum file count
                const maxCount = parseInt(maxFileCount, 10) || Infinity;
                
                // Clear any existing error messages
                const existingErrors = previewList.querySelectorAll('.ht-form-file-error-preview');
                existingErrors.forEach(error => error.remove());
                
                // Process each file
                files.forEach((file, index) => {
                    const currentFileCount = state.tempFiles.length + state.uploadQueue.length;

                    // Validate file count
                    const validation = validateFileCount(currentFileCount, maxCount, maxFileCountMessage);
                    
                    if (!validation.valid) {
                        const li = errorPreview(validation.message);
                        previewList.prepend(li);
                        return;
                    }
                    
                    // Validate file size
                    const sizeValidation = validateFileSize(file, maxFileSize, maxFileSizeMessage);
                    if (!sizeValidation.valid) {
                        const li = filePreview(file, sizeValidation.message);
                        previewList.appendChild(li);
                        return;
                    }
                    
                    // Create preview and add to queue
                    const li = filePreview(file);
                    previewList.appendChild(li);
                    
                    // Add to upload queue
                    state.uploadQueue.push({
                        file,
                        listItem: li,
                        progressBar: li.querySelector('.ht-form-file-progress-bar')
                    });
                });
                
                // Start processing the queue if not already uploading
                if (!state.uploading) {
                    processQueue();
                }
            });
            
            // Process the upload queue
            const processQueue = () => {
                if (state.uploadQueue.length === 0) {
                    state.uploading = false;
                    return;
                }
                
                // Check if we've reached the maximum file count
                const maxCount = parseInt(maxFileCount, 10) || Infinity;
                const currentFileCount = state.tempFiles.length;
                
                if (currentFileCount >= maxCount) {
                    // We've reached the maximum, clear the queue and show error
                    const remainingFiles = state.uploadQueue.length;
                    state.uploadQueue = [];
                    state.uploading = false;
                    
                    // Show error message
                    const errorMessage = maxFileCountMessage?.replace('%s', maxCount) || 
                        __(`Maximum ${maxCount} file(s) allowed. ${remainingFiles} file(s) were not uploaded.`, 'ht-contactform');
                    
                    const li = errorPreview(errorMessage);
                    previewList.prepend(li);
                    return;
                }
                
                state.uploading = true;
                const nextItem = state.uploadQueue[0];
                
                // Upload the file
                uploadFile(nextItem.file, nextItem.progressBar, nextItem.listItem)
                    .then(() => {
                        // Remove from queue
                        state.uploadQueue.shift();
                        // Process next file
                        processQueue();
                    })
                    .catch(error => {
                        console.error('Error in upload queue processing:', error);
                        // Remove from queue and continue with next file
                        state.uploadQueue.shift();
                        processQueue();
                    });
            };
            
            // Upload a single file
            const uploadFile = async (file, progressBar, listItem) => {
                try {
                    console.log('Starting file upload', {
                        file: file.name,
                        size: file.size,
                        ajaxurl: ht_form.ajaxurl
                    });
                    
                    const formData = new FormData();
                    formData.append('custom_file', file);
                    formData.append('action', 'ht_form_temp_file_upload');
                    formData.append('_wpnonce', ht_form.nonce);
                    
                    const ajaxUrl = ht_form.ajaxurl || (window.ajaxurl || '/wp-admin/admin-ajax.php');
                    
                    const response = await axios.post(ajaxUrl, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        },
                        onUploadProgress: (progressEvent) => {
                            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            progressBar.style.width = percentCompleted + '%';
                            listItem.querySelector('.ht-form-file-progress-text-percentage').textContent = `${percentCompleted}% Completed of - `;
                        }
                    });
                    
                    console.log('Upload response:', response.data);
                    
                    if (response.data.success) {
                        listItem.classList.remove('uploading');
                        listItem.classList.add('uploaded');
                        
                        // Add a remove button
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'ht-form-remove-file';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.setAttribute('aria-label', 'Remove file');
                        listItem.appendChild(removeBtn);
                        
                        // Add event listener to remove button
                        removeBtn.addEventListener('click', async () => {
                            try {
                                removeBtn.disabled = true;
                                removeBtn.innerHTML = '<span class="ht-form-loading"></span>';
                                
                                listItem.remove();
                                
                                const index = state.tempFiles.indexOf(response.data.data.file_id);
                                if (index !== -1) {
                                    state.tempFiles.splice(index, 1);
                                }
                                
                                const hiddenInput = fileUpload.querySelector(`input[type="hidden"][value="${response.data.data.file_id}"]`);
                                if (hiddenInput) {
                                    hiddenInput.remove();
                                }
                            } catch (error) {
                                console.error('Error removing file:', error);
                                removeBtn.disabled = false;
                                removeBtn.innerHTML = '&times;';
                                alert('Failed to remove file. Please try again.');
                            }
                        });
                        
                        state.tempFiles.push(response.data.data.file_id);
                        
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = input.name + '[]';
                        hiddenInput.value = response.data.data.file_id;
                        fileUpload.appendChild(hiddenInput);
                    } else {
                        throw new Error(response.data.data || 'Upload failed');
                    }
                } catch (error) {
                    listItem.classList.remove('uploading');
                    listItem.classList.add('error');
                    
                    const errorMsg = document.createElement('span');
                    errorMsg.className = 'ht-form-file-error-message';
                    errorMsg.textContent = error.message || 'Upload failed';
                    listItem.querySelector('.ht-form-file-preview-details').appendChild(errorMsg);
                    console.error('Upload error:', error);
                }
                
                return Promise.resolve(); // Always resolve to continue the queue
            };

        });
    }

})
