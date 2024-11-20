import { validFiles } from './uploadBox.js';
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const tableBody = document.getElementById('fileStatusTable').querySelector('tbody');
    const uploadNewFileBtn = document.getElementById('uploadNewFileBtn');
    //console.log(uploadUrl, skipUrl);

    // Reload page function
    if (uploadNewFileBtn) {
        uploadNewFileBtn.addEventListener('click', function () {
            window.location.reload();
        });
    } else {
        console.error('Upload New File button not found.');
    }

    if (!form) {
        console.error('Form element not found in the DOM.');
        return;
    }

    let statusIntervals = {};  // For polling intervals

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const uploadButton = document.getElementById('uploadButton');
        uploadButton.disabled = true;

        // Ensure at least one valid file is selected
        const fileInput = document.getElementById('file-input');

        //console.log("File input length:", fileInput.files.length);
        //console.log("Valid files length:", validFiles ? validFiles.length : "validFiles is undefined");
        if (fileInput.files.length === 0) { // Check file exist for upload
            alert("Please select at least one file before submitting.");
            uploadButton.disabled = false;
            return;
        }

        if (!validFiles || validFiles.length === 0) {  // Check if there are any valid files for upload
            alert("Please select at least one valid file to upload.");
            uploadButton.disabled = false;
            return;
        }

        validFiles.forEach(file => {
            const filename = file.name;

            console.log('File:', file.name);
            console.log('Uploaded file type:', file.type);

            const newRow = document.createElement('tr');
            const taskIdCell = document.createElement('td');
            taskIdCell.className = 'border border-gray-300 px-4 py-2';
            taskIdCell.innerText = 'Pending...';
            newRow.appendChild(taskIdCell);

            const fileNameCell = document.createElement('td');
            fileNameCell.className = 'border border-gray-300 px-4 py-2';
            fileNameCell.innerText = filename;
            newRow.appendChild(fileNameCell);

            const statusCell = document.createElement('td');
            statusCell.className = 'border border-gray-300 px-4 py-2';
            statusCell.innerText = 'Uploading...';
            newRow.appendChild(statusCell);

            const uploadTimeCell = document.createElement('td');
            uploadTimeCell.className = 'border border-gray-300 px-4 py-2';
            const uploadTime = new Date().toLocaleTimeString();
            uploadTimeCell.innerText = uploadTime;
            newRow.appendChild(uploadTimeCell);

            const resultCell = document.createElement('td');
            resultCell.className = 'border border-gray-300 px-4 py-2';
            resultCell.innerText = 'Pending...';
            newRow.appendChild(resultCell);

            tableBody.appendChild(newRow);

            const formData = new FormData();
            formData.append('file', file);

            // Retrieve CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/upload-to-sorb', true);

            // Set CSRF token in request header
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        console.log('Upload response:', response);

                        if (response.status === 'queued' && response.task_id) {
                            const taskId = response.task_id;
                            console.log('Task ID:', taskId);

                            // Update cells to indicate the task has been queued and is now pending processing
                            statusCell.innerText = 'Queued for processing...';
                            taskIdCell.innerText = taskId;
                            resultCell.innerText = 'Pending...';

                            // Begin polling the status immediately
                            pollStatus(taskId, statusCell, resultCell, filename, newRow);
                        } else if (response.status === 'error') {
                            statusCell.innerText = 'Error: ' + response.message;
                        } else {
                            statusCell.innerText = 'Unexpected response from server.';
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        statusCell.innerText = 'Error parsing server response.';
                    }
                } else {
                    statusCell.innerText = 'Error uploading file. Please try again.';
                }
            };



            xhr.onerror = function () {
                statusCell.innerText = 'Error during file upload.';
            };

            // Send the form data including the file
            xhr.send(formData);
        });

    });


    let tasksRunning = {}; // object to track ongoing tasks with their task IDs

    function pollStatus(taskId, statusCell, resultCell, filename, newRow) {
        console.log('Polling status for task ID:', taskId);
        const uploadButton = document.getElementById('uploadButton');

        // Track tasks that are running
        tasksRunning[taskId] = true;

        // Update beforeunload event to prevent users from leaving with ongoing tasks
        function updateBeforeUnloadEvent() {
            window.onbeforeunload = Object.values(tasksRunning).some(running => running)
                ? () => 'You have ongoing tasks. Are you sure you want to leave this page?'
                : null;
        }

        // Polling function to check the task status
        function checkStatus() {
            console.log('Checking status for task ID:', taskId);
            const url = `/get-file-task-status?task_id=${encodeURIComponent(taskId)}`;

            const xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Response data:', response);

                    if (response.status === 'error') {
                        statusCell.innerText = 'Error: ' + response.message;
                        clearInterval(statusInterval); // Stop polling on error
                    } else if (response.processing_status === 'complete') {
                        // Processing is complete
                        statusCell.innerText = 'Processing Complete';
                        resultCell.innerText = 'Report Ready';
                        tasksRunning[taskId] = false;
                        updateBeforeUnloadEvent();
                        clearInterval(statusInterval); // Stop polling once the report is ready

                        // Call checkFullReport to fetch and display the final report
                        fetchReportData(taskId, resultCell, filename, newRow);
                        //checkFullReport(taskId, resultCell, filename, newRow);
                        uploadButton.disabled = false;
                    } else {
                        statusCell.innerText = 'Processing...';
                    }
                } else {
                    statusCell.innerText = 'Error fetching status: ' + xhr.status;
                }
            };
            xhr.onerror = function () {
                statusCell.innerText = 'Error during the request.';
            };
            xhr.send();
        }

        // Disable the upload button during polling
        uploadButton.disabled = true;

        // Start polling every 10 seconds
        const statusInterval = setInterval(checkStatus, 10000);
        updateBeforeUnloadEvent();
    }

    function fetchReportData(taskId, resultCell, filename, newRow) {
        const url = `/file-task-report/${taskId}`;
        fetch(url)
            .then(response => response.json())
            .then(report => {
                if (!report.error) {
                    displayReport(report, resultCell, filename, newRow);
                } else {
                    console.error(report.error);
                    resultCell.innerText = 'Report data not found.';
                }
            })
            .catch(error => {
                console.error('Error fetching report data:', error);
                resultCell.innerText = 'Error retrieving report data.';
            });
    }


    function checkFullReport(taskId, resultCell, filename, newRow) {
        // First request to get the full report
        //const url = `/check-status?task_id=${encodeURIComponent(taskId)}`;
        const url = `/get-full-report?task_id=${encodeURIComponent(taskId)}&filename=${encodeURIComponent(filename)}`;
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        //xhr.open('GET', `get_fullReport.php?task_id=${taskId}&filename=${filename}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);

                // Handle JavaScript detection
                const jsExists = response.js_exists;
                const jsCount = response.js_count;

                if (jsExists) {
                    resultCell.innerText = `JavaScript Detected (Instances: ${jsCount})`;
                    resultCell.style.color = 'red'; // Highlight if JavaScript is found
                    alert(`Warning! ${jsCount} JavaScript code(s) detected in ${filename}. Review the file before proceeding.`);
                } else {
                    resultCell.innerText = 'No JavaScript Detected';
                }

                // Handle malscore
                if (response.malscore > 6.5) {
                    resultCell.innerText = 'High Malscore - File Deleted';
                } else {
                    if (!jsExists) {
                        resultCell.innerText += ' - File Safe'; // Append if no JS detected and malscore is okay
                    }
                }

                // Call displayReport to handle file moving or deletion
                displayReport(response, resultCell, filename, newRow);

                // Second request to get additional report (including URLs)
                getAdditionalReport(taskId, filename);

            } else {
                resultCell.innerText = 'Error fetching full report.';
            }
        };
        xhr.onerror = function() {
            resultCell.innerText = 'Error during full report request.';
        };
        xhr.send();
    }

    function getAdditionalReport(taskId, filename) {
        // Second request to get report with URLs
        const url = `/get-additional-report?task_id=${encodeURIComponent(taskId)}&filename=${encodeURIComponent(filename)}`;

        const xhrReport = new XMLHttpRequest();
        xhrReport.open('GET', url, true);
        xhrReport.onload = function() {
            if (xhrReport.status === 200) {
                const reportResponse = JSON.parse(xhrReport.responseText);
                const urls = reportResponse.urls || [];

                // Log URLs in the console
                if (urls.length > 0) {
                    console.log("URLs detected:", urls);
                    // You can also process and display the URLs as needed
                } else {
                    console.log("No URLs detected");
                }
            } else {
                console.log('Error fetching report with URLs. Status:', xhrReport.status);
            }
        };
        xhrReport.onerror = function() {
            console.log('Error during request to get report.');
        };
        xhrReport.send();
    }

    function displayReport(report, resultCell, filename, newRow) {
        const malscore = report.malscore ?? 0;
        const malfamily = report.malfamily || 'None';
        const jsCount = report.js_count || 0;

        console.log("Filename:", filename);
        console.log("Malscore:", malscore);
        console.log("MalFamily:", malfamily);
        console.log("JS Count:", jsCount);

        // Parse and log URLs if they are available
        const urls = report.urls ? JSON.parse(report.urls) : [];
        if (urls.length > 0) {
            console.log("URLs detected:", urls);
        } else {
            console.log("No URLs detected");
        }

        const url = `${moveFileUrl}?task_id=${report.task_id}&malscore=${malscore}&js_count=${jsCount}&filename=${encodeURIComponent(report.filename)}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                // Display appropriate message in resultCell based on conditions
                if (malscore < 6.5 && jsCount === 0) {
                    resultCell.innerText = data.message || 'File moved to final folder - File is safe';
                    addActionButtons(newRow, filename); // add actions button to column
                } else if (malscore >= 6.5) {
                    resultCell.style.color = 'red';
                    resultCell.innerText = 'File deleted due to high malscore.';
                } else if (jsCount > 0) {
                    resultCell.style.color = 'red';
                    resultCell.innerText = 'File deleted due to JavaScript detection.';
                } else {
                    resultCell.innerText = 'No action needed for this file.';
                }
            })
            .catch(error => {
                resultCell.innerText = 'Error processing file: ' + error;
            });
    }



    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function addActionButtons(newRow, filename) {
        const actionCell = document.createElement('td');
        actionCell.className = 'border border-gray-300 px-4 py-2';

        const uploadBtn = document.createElement('button');
        uploadBtn.className = 'bg-green-500 text-white px-2 py-1 rounded-lg mr-2';
        uploadBtn.innerText = 'Upload';
        uploadBtn.addEventListener('click', function () {
            const row = this.closest('tr');
            fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ filename })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message); // Shows 'File upload to database has been queued.' if successful
                if (data.status === 'queued') {
                    row.remove();
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });


        const skipBtn = document.createElement('button');
        skipBtn.className = 'bg-red-500 text-white px-2 py-1 rounded-lg';
        skipBtn.innerText = 'Skip';
        skipBtn.addEventListener('click', function () {
            const row = this.closest('tr');
            fetch(skipUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ filename })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message); // Shows 'File skip and delete has been queued.' if successful
                if (data.status === 'queued') {
                    row.remove(); // Remove the row if the job has been queued successfully
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });


        actionCell.appendChild(uploadBtn);
        actionCell.appendChild(skipBtn);
        newRow.appendChild(actionCell);
    }

});
