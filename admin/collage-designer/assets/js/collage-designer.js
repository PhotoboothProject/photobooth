// admin/collage-designer/assets/js/collage-designer.js

document.addEventListener('DOMContentLoaded', () => {
    console.log('Collage Designer JS loaded.');

    //=================================================================================
    // --- Global Variables (exposed via window for external scripts) ---
    //=================================================================================
    window.collageCanvas = document.getElementById('collageCanvas');
    window.collageCanvasWrapper = document.getElementById('collageCanvasWrapper');
    window.loadingOverlay = document.getElementById('loadingOverlay');

    window.ctx = window.collageCanvas.getContext('2d');
    window.collageElements = [];
    window.activeElement = null; // Represents the *single* element currently being interacted with (dragged, resized, rotated)


    // Global setting for aspect ratio lock during resizing
    window.globalLockAspectRatio = false; // standard locked

    // --- Global Fallback for Images (loaded from PHP) ---
    window.phpFallbackImageUrl = (initialDemoImagePaths && initialDemoImagePaths.length > 0) ? initialDemoImagePaths[0] : null; 

    // --- Global Function to fetch Demo Image URLs ---
    window.fetchDemoImageUrls = async function(count = 1) {
        try {
            const response = await fetch(`../../api/demo-images.php?count=${count}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const imageUrls = await response.json();
            if (!Array.isArray(imageUrls) || imageUrls.length === 0) {
                console.warn('fetchDemoImageUrls: Received empty or invalid image URLs from backend, using fallback.');
                return [window.phpFallbackImageUrl];
            }
            return imageUrls;
        } catch (error) {
            console.error('Failed to fetch demo images:', error);
            // Return a generic fallback URL in case of error
            return [window.phpFallbackImageUrl];
        }
    };

    //=================================================================================
    // --- Local Variables (not exposed globally) ---
    //=================================================================================
    const BASE_URL = typeof window.AppBaseUrl !== 'undefined' ? window.AppBaseUrl : './';

    let currentLayout = initialCollageLayout;

    let isDragging = false;
    let dragStartX, dragStartY;
    let elementStartX, elementStartY;

    let isResizing = false;
    let resizeHandle = null;
    let initialElementWidth, initialElementHeight;
    let initialElementX, initialElementY;

    let isRotating = false;
    let rotationStartAngle = 0;
    let initialElementRotation = 0;

    // --- Undo/Redo History ---
    let undoStack = [];
    let redoStack = [];
    const MAX_HISTORY_SIZE = 50; // Limit the history to prevent excessive memory usage

    //=================================================================================
    // --- Utility Functions for Loading Overlay ---
    //=================================================================================
    function showLoadingOverlay() {
        if (window.loadingOverlay) {
            window.loadingOverlay.style.display = 'flex';
        }
    }

    function hideLoadingOverlay() {
        if (window.loadingOverlay) {
            window.loadingOverlay.style.display = 'none';
        }
    }

    if (!window.collageCanvas || !window.collageCanvasWrapper || !initialCollageLayout || typeof initialDemoImagePaths === 'undefined') {
        console.error('Required elements or data not found for collage designer.');
        return;
    }

    if (!window.ctx) {
        console.error('Failed to get 2D rendering context for canvas.');
        return;
    }

    //=================================================================================
    // --- Configuration Constants ---
    //=================================================================================
    const BORDER_COLOR = '#000000';
    const BORDER_WIDTH = 2;
    const SELECTION_COLOR = 'rgba(0, 123, 255, 0.7)';

    // Globale basevalues for all handles
    const BASE_HANDLE_SIZE = 24;
    const HANDLE_COLOR = '#FFFFFF';
    const HANDLE_STROKE_COLOR = SELECTION_COLOR;
    const HANDLE_BORDER_WIDTH = 2;

    // RESIZE HANDLES
    const RESIZE_HANDLE_SIZE = BASE_HANDLE_SIZE;
    const RESIZE_HANDLE_COLOR = HANDLE_COLOR;
    const RESIZE_HANDLE_STROKE_COLOR = HANDLE_STROKE_COLOR;
    const RESIZE_HANDLE_BORDER_WIDTH = HANDLE_BORDER_WIDTH;

    // ROTATION HANDLE
    const ROTATION_HANDLE_SIZE = BASE_HANDLE_SIZE;
    const ROTATION_HANDLE_OFFSET = 20;
    const ROTATION_HANDLE_COLOR = HANDLE_COLOR;
    const ROTATION_HANDLE_STROKE_COLOR = HANDLE_STROKE_COLOR;
    const ROTATION_HANDLE_ICON = '\u21BA';
    const ROTATION_HANDLE_ICON_FONT_SIZE = `${ROTATION_HANDLE_SIZE * 0.7}px Arial`;
    const ROTATION_CURSOR_RELATIVE_PATH = 'assets/icons/rotate-ccw.svg';
    const ROTATION_CURSOR_URL = `url("${BASE_URL}${ROTATION_CURSOR_RELATIVE_PATH}") 12 12, auto`;

    // DELETE HANDLE
    const DELETE_HANDLE_SIZE = BASE_HANDLE_SIZE;
    const DELETE_HANDLE_OFFSET = 10; 
    const DELETE_HANDLE_COLOR = '#dc3545';
    const DELETE_HANDLE_STROKE_COLOR = '#FFFFFF';
    const DELETE_HANDLE_ICON_FONT_SIZE = `${DELETE_HANDLE_SIZE * 0.7}px Arial`;
    const DELETE_CURSOR_RELATIVE_PATH = 'assets/icons/trash.svg';
    const DELETE_CURSOR_URL = `url("${BASE_URL}${DELETE_CURSOR_RELATIVE_PATH}") 12 12, auto`;

    window.CollageElement = class CollageElement {
        constructor(id, x, y, width, height, rotation, type = 'image', data = {}) { // Added type and generic data object
            this.id = id;
            this.x = x;
            this.y = y;
            this.width = width;
            this.height = height;
            this.rotation = rotation;
            this.isSelected = false;
            this.type = type; // 'image', 'text', 'shape', etc.

            // Type-specific properties
            switch (this.type) {
                case 'image':
                    this.image = data.image || null; // HTMLImageElement
                    this.originalLayoutDataIndex = data.originalLayoutDataIndex !== undefined ? data.originalLayoutDataIndex : -1; // -1 for dynamically added images
                    this.show_frame = data.show_frame || false; // New property for image frames
                    // Potentially: aspect_ratio, original_aspect_ratio, etc. (if stored per element)
                    break;
                case 'text':
                    this.content = data.content || ''; // The actual text string
                    this.font_family = data.font_family || 'Arial';
                    this.font_color = data.font_color || '#000000';
                    this.font_size = data.font_size !== undefined ? data.font_size : 2; // Default font size (e.g., in %)
                    // Potentially: text_align, line_height, etc.
                    break;
                // Add more cases for other types if needed (e.g., 'background', 'shape')
                default:
                    console.warn(`CollageElement created with unknown type: ${type}`);
                    break;
            }
        }

        isHit(mouseX, mouseY) {
            return mouseX >= this.x && mouseX <= this.x + this.width &&
                   mouseY >= this.y && mouseY <= this.y + this.height;
        }
    }

    //=================================================================================
    // --- Utility Functions ---
    //=================================================================================

    function prepareRotatedImage(backgroundImg, degrees, targetWidth, targetHeight) {
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');
        const canvasRotationDegrees = -degrees;
        const imgWidth = backgroundImg.width;
        const imgHeight = backgroundImg.height;
        const absCos = Math.abs(Math.cos(canvasRotationDegrees * Math.PI / 180));
        const absSin = Math.abs(Math.sin(canvasRotationDegrees * Math.PI / 180));
        const rotatedBoundingWidth = imgWidth * absCos + imgHeight * absSin;
        const rotatedBoundingHeight = imgWidth * absSin + imgHeight * absCos;
        tempCanvas.width = rotatedBoundingWidth;
        tempCanvas.height = rotatedBoundingHeight;
        tempCtx.save();
        tempCtx.translate(rotatedBoundingWidth / 2, rotatedBoundingHeight / 2);
        tempCtx.rotate(canvasRotationDegrees * Math.PI / 180);
        tempCtx.drawImage(backgroundImg, -imgWidth / 2, -imgHeight / 2, imgWidth, imgHeight);
        tempCtx.restore();

        const finalCanvas = document.createElement('canvas');
        const finalCtx = finalCanvas.getContext('2d');
        finalCanvas.width = targetWidth;
        finalCanvas.height = targetHeight;
        const rotatedImgAspectRatio = tempCanvas.width / tempCanvas.height;
        const targetAspectRatio = targetWidth / targetHeight;
        let drawX, drawY, drawWidth, drawHeight;
        if (rotatedImgAspectRatio > targetAspectRatio) {
            drawWidth = targetWidth;
            drawHeight = targetWidth / rotatedImgAspectRatio;
        } else {
            drawHeight = targetHeight;
            drawWidth = targetHeight * rotatedImgAspectRatio;
        }
        drawX = (targetWidth - drawWidth) / 2;
        drawY = (targetHeight - drawHeight) / 2;
        finalCtx.drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, drawX, drawY, drawWidth, drawHeight);
        return finalCanvas;
    }

    function combineImages(backgroundImg, frontImg, width, height) {
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');

        tempCanvas.width = width;
        tempCanvas.height = height;

        // 1. draw first image (full size)
        // This must use the same scaling and cropping logic as in the `else` branch of your `drawCanvas`
        // for unrotated images, so that the image fits correctly within the frame.
        const imgAspectRatio = backgroundImg.width / backgroundImg.height;
        const boxAspectRatio = width / height; // Box is the tempCanvas
        let sx, sy, sWidth, sHeight; // Source in original image

        if (imgAspectRatio > boxAspectRatio) {
            sHeight = backgroundImg.height;
            sWidth = sHeight * boxAspectRatio;
            sx = (backgroundImg.width - sWidth) / 2;
            sy = 0;
        } else {
            sWidth = backgroundImg.width;
            sHeight = sWidth / boxAspectRatio;
            sx = 0;
            sy = (backgroundImg.height - sHeight) / 2;
        }
        tempCtx.drawImage(backgroundImg, sx, sy, sWidth, sHeight, 0, 0, width, height);

        // 2. draw second image over the first image
        if (frontImg && frontImg.complete) {
            tempCtx.drawImage(frontImg, 0, 0, width, height);
        }
        return tempCanvas;
    }

    function setupCanvasDimensions() {
        const { width, height, aspect_ratio } = currentLayout;
        if (!width || !height || !aspect_ratio) {
            console.warn('Layout missing width, height, or aspect_ratio. Using default 3:2.');
            window.collageCanvasWrapper.style.aspectRatio = `3 / 2`;
            window.collageCanvas.width = 900;
            window.collageCanvas.height = 600;
            return;
        }
        window.collageCanvas.width = parseInt(width, 10);
        window.collageCanvas.height = parseInt(height, 10);
        window.collageCanvasWrapper.style.aspectRatio = aspect_ratio.replace(':', ' / ');
    }

    window.loadedFrames = {}; // Globaler Cache for frame images

    function loadFrameImg() { 
        const frameId = 'global_frame';

        // 'config'-object is globally available, because api/settings.php is loaded via a <script>-tag.
        const framePath = config.collage.frame; 

        if (!framePath || framePath === '') {
            console.log("No global frame source defined or empty, frame will not be loaded.");
            window.loadedFrames[frameId] = null;
            return null;
        }

        // Check if the frame is already in the cache and the path is the same
        if (window.loadedFrames[frameId] && window.loadedFrames[frameId].src === framePath) {
            console.log("Global frame image already loaded and path matches, returning from cache.");
            return window.loadedFrames[frameId];
        }

        const img = new Image();
        img.crossOrigin = "anonymous";
        img.src = framePath;
        img.onload = () => {
            console.log("Global frame image loaded successfully:", img.src);
            window.loadedFrames[frameId] = img;
        };
        img.onerror = () => {
            console.error(`Failed to load global frame image: ${img.src}`);
            window.loadedFrames[frameId] = null;
        };
        
        window.loadedFrames[frameId] = img; 
        return img;
    }

    async function loadDemoImages() {
        showLoadingOverlay();
        let fetchedPaths = [];
        let loadedImagesLocal = [];

        // Determine how many images are actually needed from the new 'elements' array
        // We only count elements of type 'image'
        const numImageElements = currentLayout.elements ? currentLayout.elements.filter(el => el.type === 'image').length : 0;
        // Request at least 1 image for fallback if no image elements are present
        const numImagesNeeded = Math.max(numImageElements, 1);

        try {
            fetchedPaths = await window.fetchDemoImageUrls(numImagesNeeded);
        } catch (error) {
            console.error("Failed to fetch dynamic demo images for initial layout, using PHP fallback.", error);
            // If fetching fails, we fill with the PHP fallback
            for (let i = 0; i < numImagesNeeded; i++) {
                fetchedPaths.push(window.phpFallbackImageUrl);
            }
        }
        
        const imagePromises = fetchedPaths.map((path, index) => {
            return new Promise((resolve) => { // Removed reject, as we handle errors with fallback
                const img = new Image();
                img.crossOrigin = "anonymous"; // Important for CORS with external images
                img.onload = () => {
                    loadedImagesLocal[index] = img;
                    resolve();
                };
                img.onerror = () => {
                    console.warn(`Failed to load image: ${path}. Using fallback.`);
                    const fallbackImg = new Image();
                    fallbackImg.crossOrigin = "anonymous";
                    fallbackImg.src = window.phpFallbackImageUrl;
                    fallbackImg.onload = () => {
                        loadedImagesLocal[index] = fallbackImg;
                        resolve();
                    };
                    fallbackImg.onerror = () => { // If even the fallback fails to load
                        loadedImagesLocal[index] = null; // Or a specific "broken" image
                        console.error(`Fallback image also failed to load for path: ${path}.`);
                        resolve();
                    };
                };
                img.src = path;
            });
        });

        return Promise.all(imagePromises).then(() => loadedImagesLocal).finally(() => {
            hideLoadingOverlay();
        });
    }

    function updateCollageElements(loadedImagesArray) {
        window.collageElements = [];
        const canvasWidth = window.collageCanvas.width;
        const canvasHeight = window.collageCanvas.height;

        // Check if currentLayout has the new 'elements' array
        if (!currentLayout.elements || !Array.isArray(currentLayout.elements) || currentLayout.elements.length === 0) {
            console.warn('Current layout does not contain a valid "elements" array in the new JSON format. No elements will be loaded.');
            // If no elements in new format, ensure existing window.collageElements is empty and redraw.
            window.collageElements = [];
            return; 
        }

        let imagePlaceholderCount = 0; // To correctly map demo images to image elements

        currentLayout.elements.forEach((elementData) => {
            // Parse x, y, width, height, rotation - assuming they might still contain 'x'/'y' placeholders or be strings
            const x = eval(String(elementData.x).replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const y = eval(String(elementData.y).replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const width = eval(String(elementData.width).replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const height = eval(String(elementData.height).replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const rotation = parseFloat(elementData.rotation || 0);
            
            // Prepare data object for CollageElement constructor
            let data = {};

            switch (elementData.type) {
                case 'image':
                    // Assign a demo image to the placeholder for display in the designer
                    // We cycle through loadedImagesArray for each image element found in the JSON
                    const demoImageIndex = imagePlaceholderCount % loadedImagesArray.length;
                    const imgElement = loadedImagesArray[demoImageIndex];

                    data = {
                        image: imgElement, // Assign the loaded demo image
                        // The 'src' property from JSON might still exist for potential future specific placeholders
                        // or for the *final* image path which would be set by the backend.
                        // For now, in the designer, we always use the demo image for visualization.
                        originalLayoutDataIndex: imagePlaceholderCount, // Use this for consistent demo image assignment
                        show_frame: elementData.apply_frame || false // Using apply_frame from new JSON
                    };
                    imagePlaceholderCount++; // Increment for the next image element
                    break;

                case 'text':
                    data = {
                        content: elementData.content || '',
                        font_family: elementData.font_family || 'Arial',
                        font_color: elementData.font_color || '#000000',
                        font_size: elementData.font_size !== undefined ? parseFloat(elementData.font_size) : 2, // Ensure number
                        text_align: elementData.text_align || 'center' // Assuming text_align in new JSON
                    };
                    break;

                // Add cases for other types (e.g., 'shape', 'background') if they also exist in your JSON
                default:
                    console.warn(`Attempted to load unsupported element type from JSON: ${elementData.type}`);
                    return; // Skip unsupported elements
            }

            const element = new CollageElement(
                elementData.id || `element-${elementData.type}-${Date.now()}-${Math.floor(Math.random() * 1000)}`, // Ensure ID is present or generated
                x, y, width, height, rotation,
                elementData.type,
                data
            );
            window.collageElements.push(element);
        });
    }

     /**
     * Calculates the bounding box for all currently selected elements.
     * @returns {{x: number, y: number, width: number, height: number}|null} The bounding box or null if no elements are selected.
     */
    function getSelectionBoundingBox() {
        const selectedElements = window.collageElements.filter(el => el.isSelected);
        if (selectedElements.length === 0) {
            return null;
        }

        let minX = Infinity, minY = Infinity;
        let maxX = -Infinity, maxY = -Infinity;

        selectedElements.forEach(el => {
            minX = Math.min(minX, el.x);
            minY = Math.min(minY, el.y);
            maxX = Math.max(maxX, el.x + el.width);
            maxY = Math.max(maxY, el.y + el.height);
        });

        return {
            x: minX,
            y: minY,
            width: maxX - minX,
            height: maxY - minY
        };
    }

    /**
     * calculates the current visual scaling factor of the Canvas element.
     * This is the CSS scaling factor that influences the perceived size of the handles.
     * @returns {number} The scaling factor (e.g., 1.0 for original size, 0.5 for half size, 2.0 for double size).
     */
    function getCanvasVisualScale() {
        const rect = window.collageCanvas.getBoundingClientRect();
        // The scaling factor is the ratio of the actual HTML width to the CSS width
        // window.collageCanvas.width is the rendered width (pixels)
        // rect.width is the visual width (CSS pixels)
        // If the canvas (e.g., 900px wide) is in a div that is 450px wide, the scaling factor is 0.5.
        // If it's displayed 1800px wide, the scaling factor is 2.0.
        return rect.width / window.collageCanvas.width; 
    }

    //=================================================================================
    // --- update Buttons ---
    //=================================================================================

    /**
     * Updates the enabled/disabled state of the Undo/Redo buttons.
     */
    window.updateUndoRedoButtonStates = function() {
        const undoBtn = document.getElementById('undoBtn');
        const redoBtn = document.getElementById('redoBtn');

        if (undoBtn) undoBtn.disabled = undoStack.length <= 1; // Always need at least 1 state to undo from
        if (redoBtn) redoBtn.disabled = redoStack.length === 0;
    }

    /**
     * updates the enabled/disabled state of the Remove Button.
     */
    window.updateRemoveButtonState = function() {
        const removeBtn = document.getElementById('removeBtn');
        if (removeBtn) {
            const selectedElementsCount = window.collageElements.filter(el => el.isSelected).length;
            removeBtn.disabled = selectedElementsCount === 0; // Deaktiviert, wenn nichts ausgewählt ist
        }
    };

    /**
     * Updates the enabled/disabled state of the layering buttons.
     */
    window.updateLayerButtonStates = function() {
        const selectedElements = window.collageElements.filter(el => el.isSelected);
        const sendToBackBtn = document.getElementById('sendToBackBtn');
        const sendBackwardBtn = document.getElementById('sendBackwardBtn');
        const bringForwardBtn = document.getElementById('bringForwardBtn');
        const bringToFrontBtn = document.getElementById('bringToFrontBtn');

        if (selectedElements.length === 0) {
            // No elements selected, disable all layering buttons
            if (sendToBackBtn) sendToBackBtn.disabled = true;
            if (sendBackwardBtn) sendBackwardBtn.disabled = true;
            if (bringForwardBtn) bringForwardBtn.disabled = true;
            if (bringToFrontBtn) bringToFrontBtn.disabled = true;
            return;
        }

        // Determine min/max index of selected elements
        let minSelectedIndex = Infinity;
        let maxSelectedIndex = -Infinity;

        selectedElements.forEach(selectedEl => {
            const index = window.collageElements.indexOf(selectedEl);
            if (index !== -1) {
                minSelectedIndex = Math.min(minSelectedIndex, index);
                maxSelectedIndex = Math.max(maxSelectedIndex, index);
            }
        });

        // Check if any selected element can be moved further back
        let canSendBackward = false;
        for (let i = 0; i < minSelectedIndex; i++) {
            if (!selectedElements.includes(window.collageElements[i])) { // Is there an unselected element further back?
                canSendBackward = true;
                break;
            }
        }
        
        // Check if any selected element can be moved further forward
        let canBringForward = false;
        for (let i = maxSelectedIndex + 1; i < window.collageElements.length; i++) {
            if (!selectedElements.includes(window.collageElements[i])) { // Is there an unselected element further forward?
                canBringForward = true;
                break;
            }
        }

        // Update button states
        if (sendToBackBtn) sendToBackBtn.disabled = (minSelectedIndex === 0);
        if (sendBackwardBtn) sendBackwardBtn.disabled = !canSendBackward;
        if (bringForwardBtn) bringForwardBtn.disabled = !canBringForward;
        if (bringToFrontBtn) bringToFrontBtn.disabled = (maxSelectedIndex === window.collageElements.length - 1);
    };


    //=================================================================================
    // --- Undo/Redo Functionality ---
    //=================================================================================

    /**
     * Creates a snapshot of the current state of all collage elements.
     * Only stores properties that can change (x, y, width, height, rotation, isSelected).
     * @returns {Array<object>} A deep copy of the relevant element states.
     */
    window.createSnapshot = function() { 
        return window.collageElements.map(el => {
            const snapshotEl = { 
                id: el.id,
                x: el.x,
                y: el.y,
                width: el.width,
                height: el.height,
                rotation: el.rotation,
                isSelected: el.isSelected,
                type: el.type // Crucial: save element type
            };

            switch (el.type) {
                case 'image':
                    snapshotEl.imageSrc = el.image ? el.image.src : null;
                    snapshotEl.originalLayoutDataIndex = el.originalLayoutDataIndex;
                    snapshotEl.show_frame = el.show_frame;
                    break;
                case 'text':
                    snapshotEl.content = el.content;
                    snapshotEl.font_family = el.font_family;
                    snapshotEl.font_color = el.font_color;
                    snapshotEl.font_size = el.font_size;
                    break;
            }
            return snapshotEl;
        });
    }

    /**
     * Restores the state of collage elements from a given snapshot.
     * @param {Array<object>} snapshot The snapshot to restore.
     */
    window.restoreSnapshot = function(snapshot) { 
        // Clear current selection
        window.collageElements.forEach(el => el.isSelected = false);
        window.activeElement = null;

        // Create a new array for the elements, incorporating changes
        const newCollageElements = [];

        // 2. Update existing elements and add elements from snapshot that are new to current state
        snapshot.forEach(snapEl => {
            const currentEl = window.collageElements.find(el => el.id === snapEl.id);
            if (currentEl) {
                // Element exists, update its properties
                currentEl.x = snapEl.x;
                currentEl.y = snapEl.y;
                currentEl.width = snapEl.width;
                currentEl.height = snapEl.height;
                currentEl.rotation = snapEl.rotation;
                currentEl.isSelected = snapEl.isSelected;
                currentEl.type = snapEl.type; // Ensure type is restored

                switch (snapEl.type) {
                    case 'image':
                        if (snapEl.imageSrc !== (currentEl.image ? currentEl.image.src : null)) {
                            const newImage = new Image();
                            newImage.crossOrigin = "anonymous";
                            newImage.src = snapEl.imageSrc || window.phpFallbackImageUrl;
                            newImage.onload = window.drawCanvas;
                            newImage.onerror = () => { console.error(`Failed to load restored image: ${newImage.src}`); window.drawCanvas(); };
                            currentEl.image = newImage;
                        }
                        currentEl.originalLayoutDataIndex = snapEl.originalLayoutDataIndex;
                        currentEl.show_frame = snapEl.show_frame;
                        break;
                    case 'text':
                        currentEl.content = snapEl.content;
                        currentEl.font_family = snapEl.font_family;
                        currentEl.font_color = snapEl.font_color;
                        currentEl.font_size = snapEl.font_size;
                        break;
                }
                newCollageElements.push(currentEl);
            } else {
                // Element exists in snapshot but not in current window.collageElements, so it was "added"
                let recreatedData = {};
                let recreatedImage = null;

                switch (snapEl.type) {
                    case 'image':
                        recreatedImage = new Image();
                        recreatedImage.crossOrigin = "anonymous";
                        recreatedImage.src = snapEl.imageSrc || window.phpFallbackImageUrl;
                        recreatedImage.onload = window.drawCanvas;
                        recreatedImage.onerror = () => { console.error(`Failed to load recreated image: ${recreatedImage.src}`); window.drawCanvas(); };
                        recreatedData = {
                            image: recreatedImage,
                            originalLayoutDataIndex: snapEl.originalLayoutDataIndex !== undefined ? snapEl.originalLayoutDataIndex : -1,
                            show_frame: snapEl.show_frame
                        };
                        break;
                    case 'text':
                        recreatedData = {
                            content: snapEl.content,
                            font_family: snapEl.font_family,
                            font_color: snapEl.font_color,
                            font_size: snapEl.font_size
                        };
                        break;
                }
                
                const recreatedElement = new window.CollageElement(
                    snapEl.id,
                    snapEl.x,
                    snapEl.y,
                    snapEl.width,
                    snapEl.height,
                    snapEl.rotation,
                    snapEl.type, // Pass the type
                    recreatedData // Pass the type-specific data
                );
                recreatedElement.isSelected = snapEl.isSelected;
                newCollageElements.push(recreatedElement);
            }
        });

        // 3. Elements that are in window.collageElements but NOT in the snapshot should be removed.
        // By creating newCollageElements based only on the snapshot, this is implicitly handled.
        // We just need to replace the global array.
        window.collageElements = newCollageElements; // Replace the old array with the new one

        // 4. Update activeElement based on the restored selection
        const selected = window.collageElements.filter(el => el.isSelected);
        if (selected.length === 1) {
            window.activeElement = selected[0];
        } else if (selected.length > 1) {
            // If multiple elements were selected, the activeElement should be one of them.
            // We might try to restore the original activeElement if its ID is among the selected ones.
            if (window.activeElement && selected.some(el => el.id === window.activeElement.id)) {
                // Keep activeElement if it's still selected
            } else {
                window.activeElement = selected[0]; // Otherwise, pick the first selected
            }
        } else {
            window.activeElement = null; // No selection
        }
        window.drawCanvas(); // Initial draw of the restored state
        window.updateUndoRedoButtonStates(); // Update button states after restore
    };

    /**
     * Saves the current state to the undoStack and clears the redoStack.
     */
    window.saveState = function() {
        const currentState = window.createSnapshot();
        // Only save if the current state is different from the last state
        // This prevents saving redundant states from continuous actions like dragging.
        // For continuous actions, the state is saved ONCE at mousedown,
        // and then the final state is saved on mouseup.
        if (undoStack.length > 0) {
            const lastState = undoStack[undoStack.length - 1];
            // Simple comparison: check if stringified versions are different
            // For complex objects, a deep comparison function would be better.
            if (JSON.stringify(currentState) === JSON.stringify(lastState)) {
                return; // State hasn't changed meaningfully
            }
        }
        
        undoStack.push(currentState);
        if (undoStack.length > MAX_HISTORY_SIZE) {
            undoStack.shift(); // Remove the oldest state
        }
        redoStack = []; // Any new action clears the redo stack
        window.updateUndoRedoButtonStates();
    }

    //=================================================================================
    // --- Canvas Drawing Function ---
    //=================================================================================

    window.drawCanvas = function() {
        window.ctx.clearRect(0, 0, window.collageCanvas.width, window.collageCanvas.height);

        // calculate the current visual scaling factor to adjust handle sizes
        const visualScale = getCanvasVisualScale();
        const inverseScale = 1 / visualScale; // This is our multiplier for the canvas handle sizes

        // Calculate the effective handle sizes in canvas coordinates
        const effectiveResizeHandleSize = RESIZE_HANDLE_SIZE * inverseScale;
        const effectiveRotationHandleSize = ROTATION_HANDLE_SIZE * inverseScale;
        const effectiveDeleteHandleSize = DELETE_HANDLE_SIZE * inverseScale;

        // Pass the offset for rotation handle to ensure it always has a constant *visual* distance
        const effectiveRotationHandleOffset = ROTATION_HANDLE_OFFSET * inverseScale;
        const effectiveDeleteHandleOffset = DELETE_HANDLE_OFFSET * inverseScale;

        window.collageElements.forEach((element) => {
            const { x, y, width, height, rotation, type } = element;
            frameImage = window.loadedFrames['global_frame'];

            if (type === 'image') {
                const { image, show_frame } = element;
                if (image) {
                    let img = image;
                    if (show_frame && frameImage && frameImage.complete) {
                        // if frame active, create combined image with frame
                        img = combineImages(image, frameImage, width, height);
                    }

                    if (rotation !== 0) {
                        const preparedImageCanvas = prepareRotatedImage(img, rotation, width, height);
                        window.ctx.drawImage(preparedImageCanvas, x, y, width, height);
                    } else {
                        const imgAspectRatio = img.width / img.height;
                        const boxAspectRatio = width / height;
                        let sx, sy, sWidth, sHeight;
                        if (imgAspectRatio > boxAspectRatio) {
                            sHeight = img.height;
                            sWidth = sHeight * boxAspectRatio;
                            sx = (img.width - sWidth) / 2;
                            sy = 0;
                        } else {
                            sWidth = img.width;
                            sHeight = sWidth / boxAspectRatio;
                            sx = 0;
                            sy = (img.height - sHeight) / 2;
                        }
                        window.ctx.drawImage(img, sx, sy, sWidth, sHeight, x, y, width, height);
                    }
                } else {
                    window.ctx.fillStyle = '#CCCCCC';
                    window.ctx.fillRect(x, y, width, height);
                    window.ctx.fillStyle = '#666666';
                    window.ctx.font = `${Math.min(width, height) * 0.1}px Arial`;
                    window.ctx.textAlign = 'center';
                    window.ctx.textBaseline = 'middle';
                    window.ctx.fillText(`Image ${element.originalLayoutDataIndex + 1}`, x + width / 2, y + height / 2);
                }


            } else if (type === 'text') {
                const { content, font_family, font_color, font_size } = element;
                if (content) {
                    window.ctx.fillStyle = font_color;
                    // Font size should be calculated relative to canvas/element height
                    // font_size is a percentage (e.g., 2% of canvas height or element height)
                    const effectiveFontSizePx = (font_size / 100) * window.collageCanvas.height; // Or relative to element.height if element.height is fixed for text
                    window.ctx.font = `${effectiveFontSizePx}px ${font_family}`;
                    window.ctx.textAlign = 'center';
                    window.ctx.textBaseline = 'middle';
                    // Need to consider wrapping for long text content. For now, single line.
                    window.ctx.fillText(content, x + width / 2, y + height / 2);
                } else {
                    // Fallback for empty text content
                    window.ctx.fillStyle = '#AAAAAA';
                    window.ctx.fillRect(x, y, width, height);
                    window.ctx.fillStyle = '#FFFFFF';
                    window.ctx.font = `${Math.min(width, height) * 0.1}px Arial`;
                    window.ctx.textAlign = 'center';
                    window.ctx.textBaseline = 'middle';
                    window.ctx.fillText(`Text Placeholder`, x + width / 2, y + height / 2);
                }
            }
            window.ctx.restore(); // Restore context to original state before next element

            // Draw selection border for ALL selected elements
            if (element.isSelected) { 
                window.ctx.strokeStyle = SELECTION_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.strokeRect(x, y, width, height);
             } else {
                // Only draw default border if not selected and not a text element (text usually has no default border)
                if (type === 'image') { // Only draw border for images by default
                    window.ctx.strokeStyle = BORDER_COLOR;
                    window.ctx.lineWidth = BORDER_WIDTH;
                    window.ctx.strokeRect(x, y, width, height);
                }
            }
        });

        // --- Draw Resizing Handles for active element OR group bounding box ---
        // --- Draw Rotation Handle ONLY FOR THE ACTIVE ELEMENT ---
        const selectedElementsCount = window.collageElements.filter(el => el.isSelected).length;

        let targetForHandles = null; // Either activeElement or groupBoundingBox for resizing
        let targetX = 0, targetY = 0, targetWidth = 0, targetHeight = 0;

        if (selectedElementsCount === 1 && window.activeElement && window.activeElement.isSelected) {
            targetForHandles = window.activeElement;
            targetX = targetForHandles.x;
            targetY = targetForHandles.y;
            targetWidth = targetForHandles.width;
            targetHeight = targetForHandles.height;
        } else if (selectedElementsCount > 1) {
            targetForHandles = getSelectionBoundingBox(); // This is for resizing the group
            if (targetForHandles) {
                targetX = targetForHandles.x;
                targetY = targetForHandles.y;
                targetWidth = targetForHandles.width;
                targetHeight = targetForHandles.height;
            }
        }

        // Draw Resizing Handles
        if (targetForHandles && targetWidth > 0 && targetHeight > 0) { // Check for valid dimensions
            // Optional: Draw a dashed border around the group bounding box if multiple elements are selected
            if (selectedElementsCount > 1) {
                window.ctx.strokeStyle = SELECTION_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.setLineDash([5, 5]); // Dashed line
                window.ctx.strokeRect(targetX, targetY, targetWidth, targetHeight);
                window.ctx.setLineDash([]); // Reset to solid line
            }

            const handles = [
                { x: targetX,               y: targetY,              cursor: 'nwse-resize', name: 'top-left' },
                { x: targetX + targetWidth, y: targetY,              cursor: 'nesw-resize', name: 'top-right' },
                { x: targetX,               y: targetY + targetHeight,     cursor: 'nesw-resize', name: 'bottom-left' },
                { x: targetX + targetWidth, y: targetY + targetHeight,     cursor: 'nwse-resize', name: 'bottom-right' }
            ];
            handles.forEach(handle => {
                window.ctx.fillStyle = RESIZE_HANDLE_COLOR;
                window.ctx.strokeStyle = RESIZE_HANDLE_STROKE_COLOR;
                window.ctx.lineWidth = RESIZE_HANDLE_BORDER_WIDTH;
                window.ctx.fillRect(handle.x - effectiveResizeHandleSize / 2, handle.y - effectiveResizeHandleSize / 2, effectiveResizeHandleSize, effectiveResizeHandleSize);
                window.ctx.strokeRect(handle.x - effectiveResizeHandleSize / 2, handle.y - effectiveResizeHandleSize / 2, effectiveResizeHandleSize, effectiveResizeHandleSize);
            });
        }
        
        // --- Draw Delete Handle ONLY FOR THE ACTIVE ELEMENT (if single element selected) ---
        if (selectedElementsCount === 1 && window.activeElement && window.activeElement.isSelected) {
            
            // Position of the handle (top right of the active element)
            const deleteHandleX = window.activeElement.x + window.activeElement.width - effectiveDeleteHandleOffset;
            const deleteHandleY = window.activeElement.y + effectiveDeleteHandleOffset;

            // draw the circle for the handle
            window.ctx.beginPath();
            window.ctx.arc(deleteHandleX, deleteHandleY, effectiveDeleteHandleSize / 2, 0, Math.PI * 2);
            window.ctx.fillStyle = DELETE_HANDLE_COLOR;
            window.ctx.fill();
            window.ctx.strokeStyle = DELETE_HANDLE_STROKE_COLOR;
            window.ctx.lineWidth = HANDLE_BORDER_WIDTH;
            window.ctx.stroke();

            // draw the X-Symbol in the handle
            window.ctx.fillStyle = DELETE_HANDLE_STROKE_COLOR;
            window.ctx.font = `${effectiveDeleteHandleSize * 0.7}px Arial`;
            window.ctx.textAlign = 'center';
            window.ctx.textBaseline = 'middle';
            window.ctx.fillText('X', deleteHandleX, deleteHandleY);
        }

        // Draw Rotation Handle
        if (window.activeElement && window.activeElement.isSelected) { // Check if it's selected and active
            const rotationHandleX = window.activeElement.x + window.activeElement.width / 2;
            const rotationHandleY = window.activeElement.y - effectiveRotationHandleOffset;
            window.ctx.beginPath();
            window.ctx.arc(rotationHandleX, rotationHandleY, effectiveRotationHandleSize / 2, 0, Math.PI * 2);
            window.ctx.fillStyle = ROTATION_HANDLE_COLOR;
            window.ctx.fill();
            window.ctx.strokeStyle = ROTATION_HANDLE_STROKE_COLOR;
            window.ctx.lineWidth = HANDLE_BORDER_WIDTH;
            window.ctx.stroke();
            window.ctx.fillStyle = ROTATION_HANDLE_STROKE_COLOR;
            window.ctx.font = `${effectiveRotationHandleSize * 0.7}px Arial`;
            window.ctx.textAlign = 'center';
            window.ctx.textBaseline = 'middle';
            window.ctx.fillText(ROTATION_HANDLE_ICON, rotationHandleX, rotationHandleY);
        }
        
        window.updateRemoveButtonState();
        window.updateLayerButtonStates();
        window.updateElementSettingsPanel();
    };


    //=================================================================================
    // --- Mouse Event Handlers ---
    //=================================================================================

    function getMousePos(event) {
        const rect = window.collageCanvas.getBoundingClientRect();
        const scaleX = window.collageCanvas.width / rect.width;
        const scaleY = window.collageCanvas.height / rect.height;
        return {
            x: (event.clientX - rect.left) * scaleX,
            y: (event.clientY - rect.top) * scaleY
        };
    }

    function handleMouseDown(event) {
        const mouse = getMousePos(event);

        const prevSelectedState = window.createSnapshot(); // create Snapshot before Snapshot for possible selection changes

        // Reset interaction flags
        isResizing = false;
        isRotating = false;
        isDragging = false;

        const selectedElementsCount = window.collageElements.filter(el => el.isSelected).length;

        let currentInteractionTarget = null; // The element or bounding box currently being interacted with via handles
        let currentTargetX = 0, currentTargetY = 0, currentTargetWidth = 0, currentTargetHeight = 0;

        // calculate the effective handle sizes for hit detection
        const visualScale = getCanvasVisualScale();
        const inverseScale = 1 / visualScale;
        const effectiveResizeHandleSize = RESIZE_HANDLE_SIZE * inverseScale;
        const effectiveRotationHandleSize = ROTATION_HANDLE_SIZE * inverseScale;
        const effectiveDeleteHandleSize = DELETE_HANDLE_SIZE * inverseScale;
        const effectiveRotationHandleOffset = ROTATION_HANDLE_OFFSET * inverseScale;
        const effectiveDeleteHandleOffset = DELETE_HANDLE_OFFSET * inverseScale;

        // Determine the target for handle interaction (single active element or group bounding box)
        if (selectedElementsCount === 1 && window.activeElement && window.activeElement.isSelected) {
            currentInteractionTarget = window.activeElement;
            currentTargetX = currentInteractionTarget.x;
            currentTargetY = currentInteractionTarget.y;
            currentTargetWidth = currentInteractionTarget.width;
            currentTargetHeight = currentInteractionTarget.height;
        } else if (selectedElementsCount > 1) {
            currentInteractionTarget = getSelectionBoundingBox(); // This is for resizing the group
            if (currentInteractionTarget) {
                currentTargetX = currentInteractionTarget.x;
                currentTargetY = currentInteractionTarget.y;
                currentTargetWidth = currentInteractionTarget.width;
                currentTargetHeight = currentInteractionTarget.height;
            }
        }

        // --- Check for Rotation Handle hit FIRST ---
        // Rotation handle is ALWAYS on the activeElement, even if multiple are selected.
        if (window.activeElement && window.activeElement.isSelected) {
            const rotationHandleX = window.activeElement.x + window.activeElement.width / 2;
            const rotationHandleY = window.activeElement.y - effectiveRotationHandleOffset;
            const dist = Math.sqrt(
                Math.pow(mouse.x - rotationHandleX, 2) +
                Math.pow(mouse.y - rotationHandleY, 2)
            );

            if (dist <= effectiveRotationHandleSize / 2) {
                isRotating = true;
                window.saveState(); // Save state at start of rotation
                const elementCenterX = window.activeElement.x + window.activeElement.width / 2;
                const elementCenterY = window.activeElement.y + window.activeElement.height / 2;
                rotationStartAngle = Math.atan2(mouse.y - elementCenterY, mouse.x - elementCenterX);
                initialElementRotation = window.activeElement.rotation; // Store active element's rotation as start reference
                window.collageCanvas.style.cursor = ROTATION_CURSOR_URL;
                return; // Rotation handle clicked, don't proceed further
            }
        }

        // --- Check for Resize Handle hit SECOND ---
        // Handles are on activeElement (if single selected) or group bounding box (if multiple selected)
        if (currentInteractionTarget && currentTargetWidth > 0 && currentTargetHeight > 0) { // Check for valid dimensions to prevent errors
            const handles = [
                { x: currentTargetX,                        y: currentTargetY,                          name: 'top-left' },
                { x: currentTargetX + currentTargetWidth,   y: currentTargetY,                          name: 'top-right' },
                { x: currentTargetX,                        y: currentTargetY + currentTargetHeight,    name: 'bottom-left' },
                { x: currentTargetX + currentTargetWidth,   y: currentTargetY + currentTargetHeight,    name: 'bottom-right' }
            ];

            for (const handle of handles) {
                if (mouse.x >= handle.x - effectiveResizeHandleSize / 2 && mouse.x <= handle.x + effectiveResizeHandleSize / 2 &&
                    mouse.y >= handle.y - effectiveResizeHandleSize / 2 && mouse.y <= handle.y + effectiveResizeHandleSize / 2) {
                    
                    isResizing = true;
                    window.saveState(); // Save state at start of resizing
                    resizeHandle = handle.name;
                    dragStartX = mouse.x;
                    dragStartY = mouse.y;
                    // Store initial state for resizing relative to the group/element
                    initialElementWidth = currentTargetWidth;
                    initialElementHeight = currentTargetHeight;
                    initialElementX = currentTargetX;
                    initialElementY = currentTargetY;
                    
                    switch(resizeHandle) { 
                        case 'top-left': case 'bottom-right': window.collageCanvas.style.cursor = 'nwse-resize'; break;
                        case 'top-right': case 'bottom-left': window.collageCanvas.style.cursor = 'nesw-resize'; break;
                    }
                    return; // Handle clicked, don't proceed further
                }
            }
        }

        // --- Check for Delete Handle hit (only if single element selected) ---
        if (selectedElementsCount === 1 && window.activeElement && window.activeElement.isSelected) {
            const deleteHandleX = window.activeElement.x + window.activeElement.width - effectiveDeleteHandleOffset;
            const deleteHandleY = window.activeElement.y + effectiveDeleteHandleOffset;

            const distToDeleteHandle = Math.sqrt(
                Math.pow(mouse.x - deleteHandleX, 2) +
                Math.pow(mouse.y - deleteHandleY, 2)
            );

            if (distToDeleteHandle <= effectiveDeleteHandleSize / 2) {
                event.preventDefault(); // prevent that the click executes other interactions
                window.deleteSelectedElements(); // remove active element
                return; // Handle clicked, don't proceed further
            }
        }

        // --- Handle Clicks on Elements for Selection/Dragging (if no handles hit) ---
        let clickedOnElement = false;
        let elementClicked = null;

        // Find the topmost element clicked
        for (let i = window.collageElements.length - 1; i >= 0; i--) {
            const element = window.collageElements[i];
            if (element.isHit(mouse.x, mouse.y)) {
                elementClicked = element;
                clickedOnElement = true;
                break;
            }
        }

        const wasElementSelectedBeforeClick = clickedOnElement ? elementClicked.isSelected : false;

        if (clickedOnElement) {
            
            // If Ctrl/Cmd is pressed, toggle selection for the clicked element
            if (event.ctrlKey || event.metaKey) {
                elementClicked.isSelected = !elementClicked.isSelected;
                if (elementClicked.isSelected) {
                    window.activeElement = elementClicked; // Set active element to the one just selected
                } else if (window.activeElement === elementClicked) {
                    // If we deselected the active element, find another selected one to be active, or null
                    window.activeElement = window.collageElements.find(el => el.isSelected) || null;
                }
            } else {
                // Single selection: deselect all others, then select this one
                if (window.activeElement !== elementClicked || !elementClicked.isSelected) { 
                     window.collageElements.forEach(el => el.isSelected = false);
                }
                elementClicked.isSelected = true;
                window.activeElement = elementClicked; // The clicked element becomes the active one
            }
            
            if (wasElementSelectedBeforeClick) {
                isDragging = true;
                window.saveState(); // Save state at start of dragging
                dragStartX = mouse.x;
                dragStartY = mouse.y;

                // elementStartX/Y for dragging needs to be the initial position of the active element
                // or the top-left of the group bounding box for consistent dragging behavior.
                if (selectedElementsCount > 1) { // If multiple elements, drag the group
                    const groupBoundingBox = getSelectionBoundingBox();
                    if (groupBoundingBox) {
                        elementStartX = groupBoundingBox.x;
                        elementStartY = groupBoundingBox.y;
                    }
                } else if (window.activeElement) { // Single element drag
                    elementStartX = window.activeElement.x;
                    elementStartY = window.activeElement.y;
                }
            }
            window.collageCanvas.style.cursor = window.activeElement ? 'grab' : 'default';
        } else {
            // No element was clicked
            if (!event.ctrlKey && !event.metaKey) {
                window.collageElements.forEach(el => el.isSelected = false);
                window.activeElement = null;
            }
        }

        window.drawCanvas();
    }

    function handleMouseMove(event) {
        const mouse = getMousePos(event);

        // calculate the effective handle sizes for hit detection
        const visualScale = getCanvasVisualScale();
        const inverseScale = 1 / visualScale;
        const effectiveResizeHandleSize = RESIZE_HANDLE_SIZE * inverseScale;
        const effectiveRotationHandleSize = ROTATION_HANDLE_SIZE * inverseScale;
        const effectiveDeleteHandleSize = DELETE_HANDLE_SIZE * inverseScale; // Not used in mousemove but good to keep for consistency
        const effectiveRotationHandleOffset = ROTATION_HANDLE_OFFSET * inverseScale;
        const effectiveDeleteHandleOffset = DELETE_HANDLE_OFFSET * inverseScale; // Not used in mousemove but good to keep for consistency

        // --- Cursor hover for handles (adjust for group vs. single) ---
        let cursorChanged = false;
        if (!isDragging && !isResizing && !isRotating) { // Only change cursor if not interacting
            const selectedElementsCount = window.collageElements.filter(el => el.isSelected).length;
            let currentTargetForHover = null;
            let currentTargetX = 0, currentTargetY = 0, currentTargetWidth = 0, currentTargetHeight = 0;

            // Determine target for hover (single active element or group bounding box)
            if (selectedElementsCount === 1 && window.activeElement && window.activeElement.isSelected) {
                currentTargetForHover = window.activeElement;
                currentTargetX = currentTargetForHover.x;
                currentTargetY = currentTargetForHover.y;
                currentTargetWidth = currentTargetForHover.width;
                currentTargetHeight = currentTargetForHover.height;
            } else if (selectedElementsCount > 1) {
                currentTargetForHover = getSelectionBoundingBox();
                if (currentTargetForHover) {
                    currentTargetX = currentTargetForHover.x;
                    currentTargetY = currentTargetForHover.y;
                    currentTargetWidth = currentTargetForHover.width;
                    currentTargetHeight = currentTargetForHover.height;
                }
            }

            // Check rotation handle hover (ALWAYS on activeElement)
            if (window.activeElement && window.activeElement.isSelected) {
                const rotationHandleX = window.activeElement.x + window.activeElement.width / 2;
                const rotationHandleY = window.activeElement.y - effectiveRotationHandleOffset;
                const dist = Math.sqrt(
                    Math.pow(mouse.x - rotationHandleX, 2) +
                    Math.pow(mouse.y - rotationHandleY, 2)
                );
                if (dist <= effectiveRotationHandleSize / 2) {
                    window.collageCanvas.style.cursor = ROTATION_CURSOR_URL;
                    cursorChanged = true;
                }
            }

            // Check rotation handle hover (ALWAYS on activeElement)
            if (window.activeElement && window.activeElement.isSelected) {
                const deleteHandleX = window.activeElement.x + window.activeElement.width - effectiveDeleteHandleOffset;
                const deleteHandleY = window.activeElement.y + effectiveDeleteHandleOffset;
                const dist = Math.sqrt(
                    Math.pow(mouse.x - deleteHandleX, 2) +
                    Math.pow(mouse.y - deleteHandleY, 2)
                );
                if (dist <= effectiveDeleteHandleSize / 2) {
                    window.collageCanvas.style.cursor = DELETE_CURSOR_URL;
                    cursorChanged = true;
                }
            }

            // Check resize handles hover (on currentTargetForHover if it exists and not already hovering another handle)
            if (currentTargetForHover && !cursorChanged && currentTargetForHover.width > 0 && currentTargetForHover.height > 0) { 
                const handles = [
                    { x: currentTargetX,                        y: currentTargetY,                          cursor: 'nwse-resize', name: 'top-left' },
                    { x: currentTargetX + currentTargetWidth,   y: currentTargetY,                          cursor: 'nesw-resize', name: 'top-right' },
                    { x: currentTargetX,                        y: currentTargetY + currentTargetHeight,    cursor: 'nesw-resize', name: 'bottom-left' },
                    { x: currentTargetX + currentTargetWidth,   y: currentTargetY + currentTargetHeight,    cursor: 'nwse-resize', name: 'bottom-right' }
                ];
                for (const handle of handles) {
                    if (mouse.x >= handle.x - effectiveResizeHandleSize / 2 && mouse.x <= handle.x + effectiveResizeHandleSize / 2 &&
                        mouse.y >= handle.y - effectiveResizeHandleSize / 2 && mouse.y <= handle.y + effectiveResizeHandleSize / 2) {
                        window.collageCanvas.style.cursor = handle.cursor;
                        cursorChanged = true;
                        break;
                    }
                }
            }
        }

        if (!cursorChanged && !isDragging && !isResizing && !isRotating) {
            // Default cursor: If over a selected element (which is not active), show 'grab'. Otherwise 'default'.
            let overSelectable = false;
            for (let i = window.collageElements.length - 1; i >= 0; i--) {
                const element = window.collageElements[i];
                if (element.isHit(mouse.x, mouse.y)) {
                    overSelectable = true;
                    break;
                }
            }
            window.collageCanvas.style.cursor = overSelectable ? 'grab' : 'default';
        }

        // --- Rotation Logic (now applies to all selected elements, driven by activeElement's handle) ---
        if (isRotating && window.activeElement) {
            const selected = window.collageElements.filter(el => el.isSelected);
            if (selected.length === 0) { // Should not happen if activeElement is set and selected
                isRotating = false; 
                return;
            }

            const activeElementCenterX = window.activeElement.x + window.activeElement.width / 2;
            const activeElementCenterY = window.activeElement.y + window.activeElement.height / 2;

            const currentAngle = Math.atan2(mouse.y - activeElementCenterY, mouse.x - activeElementCenterX);
            let angleDiff = (rotationStartAngle - currentAngle) * 180 / Math.PI;

            if (event.shiftKey) {
                angleDiff = Math.round(angleDiff / 20) * 30;
            } else {
                angleDiff = Math.round(angleDiff);
            }
            
            selected.forEach(element => {
                element.rotation = (element.rotation + angleDiff % 360 + 360) % 360; 
            });

            // Update rotationStartAngle for the next mousemove step
            rotationStartAngle = currentAngle; 

            window.drawCanvas();
            return;
        }

        // --- Scaling Logic ---
        if (isResizing) {
            const selectedElements = window.collageElements.filter(el => el.isSelected);
            if (selectedElements.length === 0) {
                isResizing = false;
                return;
            }

            // initialBoundingBox: represents the initial bounding box of the target (single element or group)
            // These are the values stored in handleMouseDown in initialElementX/Y/Width/Height.
            const initialBoundingBox = { 
                x: initialElementX, y: initialElementY,
                width: initialElementWidth, height: initialElementHeight
            };

            const mouseStart = { x: dragStartX, y: dragStartY };

            const applyAspectRatioLock = event.shiftKey || window.globalLockAspectRatio;

            // anchorpoint (opposite corner of the handle)
            let anchorX, anchorY;
            switch (resizeHandle) {
                case 'top-left':     anchorX = initialBoundingBox.x + initialBoundingBox.width;  anchorY = initialBoundingBox.y + initialBoundingBox.height; break;
                case 'top-right':    anchorX = initialBoundingBox.x;                             anchorY = initialBoundingBox.y + initialBoundingBox.height; break;
                case 'bottom-left':  anchorX = initialBoundingBox.x + initialBoundingBox.width;  anchorY = initialBoundingBox.y;                             break;
                case 'bottom-right': anchorX = initialBoundingBox.x;                             anchorY = initialBoundingBox.y;                             break;
            }

            // Calculate current mouse movement since drag started
            const deltaX = mouse.x - mouseStart.x;
            const deltaY = mouse.y - mouseStart.y;

            // Adjust delta values based on the handle for correct direction
            let effectiveDeltaX = resizeHandle.includes('left') ? -deltaX : deltaX;
            let effectiveDeltaY = resizeHandle.includes('top') ? -deltaY : deltaY;

            let finalWidth, finalHeight;

            const aspectRatioToUse = applyAspectRatioLock && initialBoundingBox.height !== 0 ?
                         initialBoundingBox.width / initialBoundingBox.height :
                         null;

            if (aspectRatioToUse !== null) { // Proportional scaling
                // When AR lock is active, maintain the determined aspectRatioToUse.
                
                // Assume the dominant movement controls the dimension.
                if (Math.abs(effectiveDeltaX) > Math.abs(effectiveDeltaY)) {
                    finalWidth = initialBoundingBox.width + effectiveDeltaX;
                    finalHeight = finalWidth / aspectRatioToUse;
                } else {
                    finalHeight = initialBoundingBox.height + effectiveDeltaY;
                    finalWidth = finalHeight * aspectRatioToUse;
                }
                
            } else { // Free scaling
                finalWidth = initialBoundingBox.width + effectiveDeltaX;
                finalHeight = initialBoundingBox.height + effectiveDeltaY;
            }

            // --- Apply minimum size restriction ---
            const MIN_SIZE_PX = 100;

            if (finalWidth < MIN_SIZE_PX) {
                finalWidth = MIN_SIZE_PX;
                if (aspectRatioToUse !== null) { // Only adjust if Aspect Ratio Lock is active
                    finalHeight = MIN_SIZE_PX / aspectRatioToUse; 
                }
            }
            if (finalHeight < MIN_SIZE_PX) {
                finalHeight = MIN_SIZE_PX;
                if (aspectRatioToUse !== null) { // Only adjust if Aspect Ratio Lock is active
                    finalWidth = MIN_SIZE_PX * aspectRatioToUse; 
                }
            }

            // Calculate new top-left corner of the bounding box based on anchor point and new dimensions
            let newBoundingBoxX, newBoundingBoxY;
            switch (resizeHandle) {
                case 'top-left':
                    newBoundingBoxX = anchorX - finalWidth;
                    newBoundingBoxY = anchorY - finalHeight;
                    break;
                case 'top-right':
                    newBoundingBoxX = anchorX;
                    newBoundingBoxY = anchorY - finalHeight;
                    break;
                case 'bottom-left':
                    newBoundingBoxX = anchorX - finalWidth;
                    newBoundingBoxY = anchorY;
                    break;
                case 'bottom-right':
                    newBoundingBoxX = anchorX;
                    newBoundingBoxY = anchorY;
                    break;
            }

            // Calculate overall displacement and scaling for each selected element
            const displacementX = newBoundingBoxX - initialBoundingBox.x;
            const displacementY = newBoundingBoxY - initialBoundingBox.y;

            let scaleFactorX, scaleFactorY;
            if (aspectRatioToUse !== null) { // Proportional scaling
                // When AR lock is active, use a single scaling factor for both dimensions.
                // This ensures each element within the selection scales uniformly, retaining its own AR.
                scaleFactorX = finalWidth / initialBoundingBox.width;
                scaleFactorY = scaleFactorX; // Both dimensions scale with the SAME factor
            } else {
                // For free scaling, use potentially different scaling factors for width and height.
                scaleFactorX = finalWidth / initialBoundingBox.width;
                scaleFactorY = finalHeight / initialBoundingBox.height;
            }

            // Apply transformation on each selected element
            selectedElements.forEach(element => {
                // Position of the element relative to the BoundingBox's initial top-left corner
                const relativeXToBoundingBox = element.x - initialBoundingBox.x;
                const relativeYToBoundingBox = element.y - initialBoundingBox.y;

                // Scale relative position and dimensions
                element.x = newBoundingBoxX + relativeXToBoundingBox * scaleFactorX; 
                element.y = newBoundingBoxY + relativeYToBoundingBox * scaleFactorY; 
                
                element.width = element.width * scaleFactorX;
                element.height = element.height * scaleFactorY;
                // console.log("handleMouseMove: Element ID:", element.id, "new dimensions:", element.width, "x", element.height, "AR:", element.width / element.height);
            });

            // Update dragStartX/Y to current mouse position for continuous resizing
            dragStartX = mouse.x;
            dragStartY = mouse.y;

            window.drawCanvas();
            return;
        }

        // --- Dragging Logic (now applies to all selected elements) ---
        if (isDragging) {
            const selected = window.collageElements.filter(el => el.isSelected);
            if (selected.length === 0) {
                isDragging = false;
                return;
            }
            const dx = mouse.x - dragStartX;
            const dy = mouse.y - dragStartY;

            selected.forEach(element => {
                element.x += dx;
                element.y += dy;
            });

            // Update dragStartX/Y to current mouse position for continuous resizing
            dragStartX = mouse.x;
            dragStartY = mouse.y;

            window.drawCanvas();
            return;
        }
    }

    function handleMouseUp(event) {
        if (isDragging) {
            isDragging = false;
        }
        if (isResizing) {
            isResizing = false;
        }
        if (isRotating) {
            isRotating = false;
        }
        
        // Restore default cursor: grab if over a selected element, default otherwise.
        let overSelectable = false;
        const mouse = getMousePos(event);
        for (let i = window.collageElements.length - 1; i >= 0; i--) {
            const element = window.collageElements[i];
            if (element.isHit(mouse.x, mouse.y)) {
                overSelectable = true;
                break;
            }
        }
        window.collageCanvas.style.cursor = overSelectable ? 'grab' : 'default';
    }

    //=================================================================================
    // --- Initialization Function ---
    //=================================================================================

    async function initDesigner() {
        setupCanvasDimensions();
        const loadedImagesArray = await loadDemoImages();
        loadFrameImg();
        updateCollageElements(loadedImagesArray);
        window.drawCanvas();
    }

    //=================================================================================
    // --- Event Listeners ---
    //=================================================================================
    window.collageCanvas.addEventListener('mousedown', handleMouseDown);
    window.collageCanvas.addEventListener('mousemove', handleMouseMove);
    window.collageCanvas.addEventListener('mouseup', handleMouseUp);
    window.collageCanvas.addEventListener('mouseout', handleMouseUp); // End interaction if mouse leaves canvas

    // Undo/Redo Buttons
    document.getElementById('undoBtn').addEventListener('click', () => {
        if (undoStack.length > 1) { // Need at least the initial state and one action to undo
            const currentState = undoStack.pop(); // Remove current state from undo stack
            redoStack.push(currentState); // Push it to redo stack
            window.restoreSnapshot(undoStack[undoStack.length - 1]); // Load the previous state
        }
    });

    document.getElementById('redoBtn').addEventListener('click', () => {
        if (redoStack.length > 0) {
            const nextState = redoStack.pop(); // Get next state from redo stack
            undoStack.push(nextState); // Push it back to undo stack
            window.restoreSnapshot(nextState); // Load this state
        }
    });

    // --- Keyboard Shortcuts for Undo/Redo ---
    document.addEventListener('keydown', (event) => {
        // Check for Ctrl (Windows/Linux) or Cmd (macOS) key
        const isCtrlCmd = event.ctrlKey || event.metaKey; 

        if (isCtrlCmd) {
            if (event.key === 'z' || event.key === 'Z') {
                event.preventDefault(); // Prevent default browser undo (e.g., in text fields)
                document.getElementById('undoBtn').click(); // Simulate click on undo button
            } else if (event.key === 'y' || event.key === 'Y') {
                event.preventDefault(); // Prevent default browser redo
                document.getElementById('redoBtn').click(); // Simulate click on redo button
            }
        }
    });

    // add / remove buttons
    document.getElementById('addBtn').addEventListener('click', () => {
        // When clicking the button, add a new element
        window.addNewElement(); // Calls the function to add a new element with default parameters
    });
    // Remove Button
    document.getElementById('removeBtn').addEventListener('click', () => {
        window.deleteSelectedElements();
    });

    // --- Keyboard Shortcut for Delete ---
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Delete') {
            const selectedElementsCount = window.collageElements.filter(el => el.isSelected).length;
            if (selectedElementsCount > 0) {
                event.preventDefault(); // prevents default browser behavior (e.g., navigating back in browser)
                window.deleteSelectedElements(); // Delete the selected elements
            }
        }
    });

    // --- Layering Buttons ---
    document.getElementById('sendToBackBtn').addEventListener('click', () => {
        window.changeZOrder('back');
    });
    document.getElementById('sendBackwardBtn').addEventListener('click', () => {
        window.changeZOrder('backward');
    });
    document.getElementById('bringForwardBtn').addEventListener('click', () => {
        window.changeZOrder('forward');
    });
    document.getElementById('bringToFrontBtn').addEventListener('click', () => {
        window.changeZOrder('front');
    });

    //=================================================================================
    // --- Initialize the designer and save the very first state
    //=================================================================================
    initDesigner().then(() => {
        window.saveState(); // Save initial state after everything is loaded
        window.updateUndoRedoButtonStates(); // Update button states based on initial stack
    });
});