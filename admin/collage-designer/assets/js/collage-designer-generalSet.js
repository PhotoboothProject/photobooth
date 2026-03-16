// admin/collage-designer/assets/js/collage-designer-generalSet.js

document.addEventListener('DOMContentLoaded', () => {
    // Basic check to ensure main designer variables/functions are available
    if (typeof window.collageCanvas === 'undefined' || typeof window.drawCanvas === 'undefined' ||
        typeof window.collageElements === 'undefined' ||
        typeof window.saveState === 'undefined' || typeof window.globalLockAspectRatio === 'undefined' ||
        typeof window.collageCanvasWrapper === 'undefined'
    ) {
        console.error('collage-designer-generalSet.js: Dependent main designer variables/functions not found. Ensure collage-designer.js is loaded first and exposes necessary variables globally.');
        return;
    }

    const canvasWidthInput = document.querySelector('input[name="final_width"]');
    const canvasHeightInput = document.querySelector('input[name="final_height"]');
    const backgroundColorInput = document.querySelector('input[name="background_color"]'); // the hidden Input, which saves the background-color
    const showframeCheckbox = document.getElementById('show_frame');
    const backgroundImage = document.getElementById('background_image');
    const backgroundImageSelectorParent = backgroundImage.closest('.adminImageSelection');
    const backgroundImagePreviewElement = backgroundImageSelectorParent.querySelector('.adminImageSelection-preview');
    const backgroundImageTextElement = backgroundImageSelectorParent.querySelector('.adminImageSelection-text');
    const frameImage = document.getElementById('frame_image');
    const frameImageSelectorParent = frameImage.closest('.adminImageSelection');
    const frameImagePreviewElement = frameImageSelectorParent.querySelector('.adminImageSelection-preview');
    const frameImageTextElement = frameImageSelectorParent.querySelector('.adminImageSelection-text');

    // Define minimum and maximum canvas dimensions
    const MIN_CANVAS_DIMENSION = 100; // e.g., 100px minimum for width and height
    const MAX_CANVAS_DIMENSION = 4000; // e.g., 4000px maximum for width and height


    // Debounced version of saveState to prevent excessive calls during rapid input changes
    const debouncedSaveState = window.debounce(window.saveState, 500);

    /**
     * Updates the settings panel input values to reflect the current state of the collage canvas.
     */
    window.updateGeneralSettingsPanel = function() {
        // Background Image
        if(window.backgroundImage){
            backgroundImage.value = window.backgroundImage;
            backgroundImageTextElement.textContent = window.backgroundImage;
            backgroundImagePreviewElement.src = '../../' + window.backgroundImage;
            backgroundImagePreviewElement.parentElement.classList.remove('hidden');
        } else {
            backgroundImage.value = '';
            backgroundImageTextElement.textContent = '';
            backgroundImagePreviewElement.parentElement.classList.add('hidden');
        }

        // global Frame Image
        if(window.globalFrameImage){
            frameImage.value = window.globalFrameImage;
            frameImageTextElement.textContent = window.globalFrameImage;
            //frameImagePreviewElement.src = '../../' + window.globalFrameImage; BUG: preview not working yet
            frameImagePreviewElement.parentElement.classList.remove('hidden');
        } else {
            frameImage.value = '';
            frameImageTextElement.textContent = '';
            frameImagePreviewElement.parentElement.classList.add('hidden');
        }

        // show frame checkbox
        showframeCheckbox.checked = window.showGlobalFrameImage;

        // Background color
        backgroundColorInput.value = window.backgroundColor;

        // Canvas dimensions
        canvasWidthInput.value = window.collageCanvas.width;
        canvasHeightInput.value = window.collageCanvas.height;

        // Update wrapper aspect ratio
        if (window.collageCanvas.width > 0 && window.collageCanvas.height > 0) {
            window.collageCanvasWrapper.style.aspectRatio = `${window.collageCanvas.width} / ${window.collageCanvas.height}`;
        }
    }    

    // Event listener for width input
    canvasWidthInput.addEventListener('input', (e) => {
        let newWidth = parseInt(e.target.value, 10);
        if (isNaN(newWidth) || newWidth <= 0) {
            return; // Ignore invalid input
        }
        // Apply min/max constraints
        newWidth = Math.max(MIN_CANVAS_DIMENSION, Math.min(MAX_CANVAS_DIMENSION, newWidth));

        if (window.globalLockAspectRatio) {
            const currentAspectRatio = window.collageCanvas.width / window.collageCanvas.height;
            const newHeight = Math.round(newWidth / currentAspectRatio); // Calculate new height based on new width and current aspect ratio
            window.collageCanvas.width = newWidth;
            window.collageCanvas.height = newHeight;
            canvasHeightInput.value = newHeight; // Update companion input field
        } else {
            window.collageCanvas.width = newWidth;
        }

        // Update wrapper aspect ratio after dimension changes
        if (window.collageCanvas.width > 0 && window.collageCanvas.height > 0) {
            window.collageCanvasWrapper.style.aspectRatio = `${window.collageCanvas.width} / ${window.collageCanvas.height}`;
        }
        
        debouncedSaveState(); // Use debouncedSaveState for numeric inputs
        window.drawCanvas(); // Re-draw canvas with new dimensions
    });

    // Event listener for height input
    canvasHeightInput.addEventListener('input', (e) => {
        let newHeight = parseInt(e.target.value, 10);
        if (isNaN(newHeight) || newHeight <= 0) {
            return; // Ignore invalid input
        }
        // Apply min/max constraints
        newHeight = Math.max(MIN_CANVAS_DIMENSION, Math.min(MAX_CANVAS_DIMENSION, newHeight));

        if (window.globalLockAspectRatio) {
            const currentAspectRatio = window.collageCanvas.width / window.collageCanvas.height;
            const newWidth = Math.round(newHeight * currentAspectRatio); // Calculate new width based on new height and current aspect ratio
            window.collageCanvas.width = newWidth;
            window.collageCanvas.height = newHeight;
            canvasWidthInput.value = newWidth; // Update companion input field
        } else {
            window.collageCanvas.height = newHeight;
        }

        // Update wrapper aspect ratio after dimension changes
        if (window.collageCanvas.width > 0 && window.collageCanvas.height > 0) {
            window.collageCanvasWrapper.style.aspectRatio = `${window.collageCanvas.width} / ${window.collageCanvas.height}`;
        }

        debouncedSaveState(); // Use debouncedSaveState for numeric inputs
        window.drawCanvas(); // Re-draw canvas with new dimensions
    });

    // Event listener for background color input
    backgroundColorInput.addEventListener('input', (e) => {
        const newColor = e.target.value;
        window.backgroundColor = newColor;
        debouncedSaveState();
        window.drawCanvas();
    });

    // Event listener for show frame checkbox
    showframeCheckbox.addEventListener('change', (e) => {
        const isChecked = e.target.checked;
        window.showGlobalFrameImage = isChecked;
        window.saveState();
        window.drawCanvas();
    });

    backgroundImage.addEventListener('change', (e) => {
        const filePath = e.target.value;
        window.backgroundImage = filePath;
        window.saveState();
        window.drawCanvas();
        updateGeneralSettingsPanel();
    });

    frameImage.addEventListener('change', (e) => {
        const filePath = e.target.value;
        window.globalFrameImage = filePath;
        window.saveState();
        window.drawCanvas();
        updateGeneralSettingsPanel();
    });

    updateGeneralSettingsPanel();
});