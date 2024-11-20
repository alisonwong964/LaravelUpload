document.addEventListener('DOMContentLoaded', () => {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('file-input');

    if (dropArea && fileInput) {
        dropArea.addEventListener('click', () => {
            fileInput.click();
        });

        dropArea.addEventListener('dragover', (event) => {
            event.preventDefault();
            dropArea.classList.add('hover');
        });

        dropArea.addEventListener('dragleave', () => {
            dropArea.classList.remove('hover');
        });

        dropArea.addEventListener('drop', (event) => {
            event.preventDefault();
            dropArea.classList.remove('hover');

            const files = event.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files; // This updates the file input element with dropped files
                handleFiles(files); // Handle file preview
                checkFiles(files); // Start checking process for upload box
            }
        });

        fileInput.addEventListener('change', (event) => {
            const files = event.target.files;
            handleFiles(files); // Handle file preview
            checkFiles(files); // Start checking process for upload box
        });
    }
});


function handleFiles(files) {
    const previewContainer = document.getElementById('preview-container');
    previewContainer.innerHTML = ''; // Clear previous previews

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const previewDiv = document.createElement('div');
        previewDiv.classList.add('file-preview');
        previewDiv.id = `file-preview-${i}`;

        let icon;

        if (file.type.startsWith('image/')) {
            icon = document.createElement('img');
            icon.src = URL.createObjectURL(file);
            icon.alt = 'Image Preview';
        } else if (file.type === 'application/pdf') {
            icon = document.createElement('i');
            icon.className = 'fas fa-file-pdf'; //PDF icon
        } else if (file.type === 'text/plain') {
            icon = document.createElement('i');
            icon.className = 'fas fa-file-alt'; //TXT icon
        } else if (file.type === 'application/vnd.ms-excel' || file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            icon = document.createElement('i');
            icon.className = 'fas fa-file-excel'; //XLSX icon
        } else if (file.type === 'application/msword' || file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            icon = document.createElement('i');
            icon.className = 'fas fa-file-word'; //DOC icon
        } else {
            icon = document.createElement('i');
            icon.className = 'fas fa-file'; // Generic file icon
        }

        // Add filename text
        const filenameText = document.createElement('div');
        filenameText.classList.add('filename-text');
        filenameText.innerText = file.name.length > 15 ? file.name.slice(0, 15) + '...' : file.name; // Show truncated filename

        previewDiv.appendChild(icon);
        previewDiv.appendChild(filenameText); // Append filename to preview
        previewContainer.appendChild(previewDiv);
    }
}

//Double ext check
function hasMultipleExtensions(fileName) {
    const validExtensions = ['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg', 'xlsm', 'txt'];
    const dangerousExtensions = ['php', 'js']; //Dangerous extensions

    // Split the filename into parts
    const fileParts = fileName.split(".");

    // Check if the filename has at least one period
    if (fileParts.length < 2) {
        console.log("No periods allowed in filename, please try again.");
        return true;
    } else {
        // Get the last part as the extension
        const extension = fileParts.pop().toLowerCase();

        // Check for dangerous extensions in any part of the filename
        const hasDangerousExtension = fileParts.some(part => dangerousExtensions.includes(part.toLowerCase()));

        if (hasDangerousExtension) {
            console.log("Attempted attack detected. Dangerous extensions in filename are not allowed.");
            return true; // Indicates invalid due to attack detection
        }

        // Check if the last part is a valid extension
        if (validExtensions.includes(extension)) {
            // Ensure only one valid extension exists
            if (fileParts.length > 0) {
                // Check if there are any additional valid extensions in remaining parts
                const hasInvalidExtensions = fileParts.some(part => validExtensions.includes(part.toLowerCase()));

                if (hasInvalidExtensions) {
                    console.log("Files with multiple extensions are not allowed. Please upload files with only one extension.");
                    return true;
                }
            }

            console.log("File is valid for upload.");
            return false;
        } else {
            console.log("Invalid file extension, please try again.");
            return true;
        }
    }
}

export let validFiles = [];
function checkFiles(files) {
    const maxFileSize = 3; // Set max file size to 3 MB
    const acceptedFileTypes = ['.pdf', '.doc', '.docx', '.jpg', '.png', '.jpeg', '.xlsm', '.txt'];

    // Clear previous previews in case of new files being dropped
    const previewContainer = document.getElementById('preview-container');
    previewContainer.innerHTML = '';

    // Clear the validFiles array
    validFiles.length = 0;

    // Clear any previous error messages
    clearErrorMessages();

    // Iterate over the files and perform validation
    Array.from(files).forEach((file, index) => {
        let hasError = false;
        const fileName = file.name;

        // Extract the extension by getting the substring after the last dot
        if (hasMultipleExtensions(fileName)) {
            displayError('Files with multiple extensions are not allowed. Please upload files with only one extension.');
            hasError = true;
        }

        // Check file size
        const fileSizeMB = file.size / (1024 * 1024);
        if (fileSizeMB > maxFileSize) {
            displayError('File size exceeds 3 MB limit.');
            hasError = true;
        }

        // Check file type
        const fileExtension = fileName.substring(fileName.lastIndexOf('.')).toLowerCase();
        if (!acceptedFileTypes.includes(fileExtension)) {
            displayError('File type is not allowed.');
            hasError = true;
        }

        // If there is an error, skip further processing for this file
        if (hasError) {
            return;
        }

        // If the file passes all validations, add it to the validFiles array
        validFiles.push(file);

        // Create a file preview
        const filePreview = document.createElement('div');
        filePreview.id = `file-preview-${index}`;
        filePreview.classList.add('file-preview');
        let icon;
        if (file.type.startsWith('image/')) {
            icon = document.createElement('img');
            icon.src = URL.createObjectURL(file);
            icon.alt = 'Image Preview';
        } else if (file.type === 'application/pdf') {
            icon = document.createElement('i');
            icon.className = 'fas fa-file-pdf'; //PDF icon
        } else if (file.type === 'text/plain') {
            icon = document.createElement('i');
            icon.className = 'fas fa-file-alt'; //TXT icon
        } else if (file.type === 'application/vnd.ms-excel' || file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            icon = document.createElement('i');
            icon.className = 'fas fa-file-excel'; //XLSX icon
        } else if (file.type === 'application/msword' || file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            icon = document.createElement('i');
            icon.className = 'fas fa-file-word'; //DOC icon
        } else {
            icon = document.createElement('i');
            icon.className = 'fas fa-file'; // Generic file icon
        }

        // Add filename text
        const filenameText = document.createElement('div');
        filenameText.classList.add('filename-text');
        filenameText.innerText = file.name.length > 10 ? file.name.slice(0, 10) + '...' : file.name; // Show truncated filename

        // Add remove button
        const removeButton = document.createElement('button');
        removeButton.classList.add('remove-btn');
        removeButton.innerHTML = 'Remove';
        removeButton.addEventListener('click', (event) => {
            event.stopPropagation();
            filePreview.remove(); // Remove the file preview when button is clicked
            validFiles = validFiles.filter(f => f !== file); // Remove the file from the validFiles array
            resetFileInput();
        });

        // Add the success tick symbol
        const successOverlay = document.createElement('span');
        successOverlay.classList.add('status-overlay', 'success');
        successOverlay.innerHTML = '✓';

        // Append elements to the file preview
        filePreview.appendChild(icon);
        filePreview.appendChild(filenameText);
        filePreview.appendChild(successOverlay);
        filePreview.appendChild(removeButton);

        // Add preview to the container
        previewContainer.appendChild(filePreview);
    });

    resetFileInput();
}

function resetFileInput() {
    const fileInput = document.getElementById('file-input');

    // Clear the file input
    fileInput.value = '';

    // Create a new DataTransfer object to hold valid files
    const dataTransfer = new DataTransfer();
    validFiles.forEach(file => {
        dataTransfer.items.add(file);
    });

    // Assign the DataTransfer object back to the file input
    fileInput.files = dataTransfer.files;
}

function clearErrorMessages() {
    const errorContainer = document.getElementById('error-messages');
    errorContainer.innerHTML = ''; // Clear previous error messages
}

function displayError(errorMessage) {
    const errorContainer = document.getElementById('error-messages');

    // Make sure the error container is visible
    errorContainer.style.display = 'block';

    // Create a new error message element
    const errorText = document.createElement('div');
    errorText.classList.add('error-message');
    errorText.textContent = errorMessage;

    errorContainer.appendChild(errorText);

    //Set timeout for error message
    setTimeout(() => {
        errorText.remove(); // Remove the specific error message
        if (errorContainer.children.length === 0) {
            errorContainer.style.display = 'none';
        }
    }, 3000);
}

function addClickPreview() {
    const previews = document.querySelectorAll('.file-preview');
    previews.forEach(preview => {
        preview.addEventListener('click', function () {
            console.log('File preview clicked!');
        });
    });
}

document.addEventListener('DOMContentLoaded', addClickPreview);
