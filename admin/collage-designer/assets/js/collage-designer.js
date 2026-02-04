// admin/collage-designer/assets/js/collage-designer.js

document.addEventListener('DOMContentLoaded', () => {
    //console.log('Collage Designer JS loaded.');

    //=================================================================================
    // --- Global Variables (exposed via window for external scripts) ---
    //=================================================================================
    window.collageCanvas = document.getElementById('collageCanvas');
    window.collageCanvasWrapper = document.getElementById('collageCanvasWrapper');
    window.loadingOverlay = document.getElementById('loadingOverlay');

    window.ctx = window.collageCanvas.getContext('2d');
    window.collageElements = [];
    window.activeElement = null; // Represents the *single* element currently being interacted with (dragged, resized, rotated)

    // Global settings for the collage
    window.backgroundImage = null;          // Path to the global background image (e.g., 'path/to/image.jpg')
    window.backgroundColor = '#ffffff';     // Global background color, default white
    window.showGlobalFrameImage = false;    // Whether to show the global frame image on top
    window.globalFrameImage = null;         // Path to the global frame image (e.g., 'path/to/frame.png')

    // Global caches
    window.loadedFrames = {};           // globale cache for frame images
    window.loadedFontsMap = new Map();  // Global cache for fonts
    window.imageCache = {};             // globale cache for loaded images

    // Global setting for showing element outlines
    window.globalShowElementOutlines = true; // standard enabled

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
    const DELETE_HANDLE_OFFSET = 25; 
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
                    this.src = data.src || null; // Original source path (can be null for demo images)
                    this.originalLayoutDataIndex = data.originalLayoutDataIndex !== undefined ? data.originalLayoutDataIndex : -1; // -1 for dynamically added images
                    this.show_frame = data.show_frame || false; // New property for image frames
                    // Potentially: aspect_ratio, original_aspect_ratio, etc. (if stored per element)
                    break;
                case 'text':
                    this.content = data.content || ''; // The actual text string
                    this.font_family = data.font_family || 'resources/fonts/GreatVibes-Regular.ttf';
                    this.font_color = data.font_color || '#000000';
                    this.font_size = data.font_size !== undefined ? data.font_size : 2; // Default font size (e.g., in %)
                    this.text_horizontal_align = data.text_horizontal_align || 'center';
                    this.text_vertical_align = data.text_vertical_align || 'center';
                    this.font_bold = data.font_bold || false;
                    this.font_italic = data.font_italic || false;
                    this.font_underline = data.font_underline || false;
                    break;
                // Add more cases for other types if needed (e.g., 'background', 'shape')
                default:
                    console.warn(`CollageElement created with unknown type: ${type}`);
                    break;
            }
        }

        isHit(mouseX, mouseY) {
            const {x: localX, y: localY } = getLocalMouseCoordinates(mouseX, mouseY, this);
            return isPointInRect(localX, localY, 0, 0, this.width, this.height);
        }
    }

    //=================================================================================
    // --- Utility Functions ---
    //=================================================================================
    
    /**
     * debounce function to limit the rate of function calls
     */
    window.debounce = function(func, delay) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

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

    /**
     * Loads the global frame image based on the configuration.
     * 
     * @returns {Image|null} The loaded Image object or null if not defined or failed to load.
     */
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
            //console.log("Global frame image loaded successfully:", img.src);
            window.loadedFrames[frameId] = img;
        };
        img.onerror = () => {
            console.error(`Failed to load global frame image: ${img.src}`);
            window.loadedFrames[frameId] = null;
        };
        
        window.loadedFrames[frameId] = img; 
        return img;
    }

    /**
     * Dynamically loads a font if it hasn't been loaded yet.
     * @param {string} fontPath The project-relative path to the font file (e.g., 'private/fonts/ArchivoBlack-Regular.ttf').
     * @returns {Promise<string>} A promise that resolves with the CSS font-family name once the font is loaded.
     */
    window.loadFont = async function(fontPath) {
        if (!fontPath || fontPath.trim() === '') {
            console.warn('loadFont: Empty font path provided, using default Arial.');
            return 'Arial'; // Default fallback font if no path is provided
        }

        // Derive a unique CSS font-family name from the path
        // e.g., 'private/fonts/ArchivoBlack-Regular.ttf' -> 'ArchivoBlack-Regular'
        const fontCssName = fontPath.split('/').pop().split('.')[0];

        // Check if the font is already in our loaded map
        if (window.loadedFontsMap.has(fontPath)) {
            return window.loadedFontsMap.get(fontPath);
        }


        const fontUrl = `../../${fontPath}`; // Adjust if `fontPath` needs further transformation to be a valid public URL

        // Create a new FontFace object and load it
        const fontFace = new FontFace(fontCssName, `url(${fontUrl})`);

        try {
            await fontFace.load();
            document.fonts.add(fontFace); // Add the loaded font to the document's font set
            window.loadedFontsMap.set(fontPath, fontCssName); // Store in our map
            console.log(`Font "${fontCssName}" loaded successfully from ${fontUrl}`);
            return fontCssName;
        } catch (error) {
            console.error(`Failed to load font "${fontCssName}" from ${fontUrl}:`, error);
            return 'Arial'; // Fallback to a safe font on error
        }
    };

    /**
     * Loads an image and stores it in the cache.
     *
     * @param {string} src - the path to the image.
     * @returns {Promise<Image|null>} a promise that resolves to the loaded Image object or null on error.
     */
    async function loadImageFromSrc(src) {
        if (!src || typeof src !== 'string' || src.trim() === '') {
            console.warn('loadImageFromSrc: Empty or invalid source provided.');
            return null;
        }

        src = `../../` + src; // Adjust path to be relative to the base URL

        // test if image is already in the cache
        if (imageCache[src]) {
            // If it's an Image object and successfully loaded
            if (imageCache[src].complete && imageCache[src].naturalWidth !== 0) {
                return imageCache[src];
            }
            // If it's a Promise, wait for it
            if (imageCache[src] instanceof Promise) {
                return imageCache[src];
            }
            // If null is in the cache (previous loading error), try again
            if (imageCache[src] === null) {
                delete imageCache[src]; // Remove to attempt a new load
            }
        }

        const img = new Image();
        img.crossOrigin = "anonymous";

        const loadingPromise = new Promise((resolve) => {
            img.onload = () => {
                //console.log(`Image loaded successfully: ${src}`);
                imageCache[src] = img;
                resolve(img);
            };
            img.onerror = (e) => {
                console.error(`Failed to load image from src: ${src}`, e);
                imageCache[src] = null; // mark as failed in cache
                resolve(null); // resolve Promise with null
            };
        });

        imageCache[src] = loadingPromise; // save promise to cache, to prevent multiple loads
        img.src = src;
        return loadingPromise;
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

        currentDesignName = document.getElementById('currentDesignName');
        currentDesignName.textContent = currentLayout.name || 'Unnamed Design';

        // Update genral settings based on currentLayout
        window.backgroundImage = currentLayout.background_image || null;
        window.backgroundColor = currentLayout.background_color || '#ffffff';
        window.showGlobalFrameImage = currentLayout.show_global_frame_image || false;
        window.globalFrameImage = currentLayout.global_frame_image || null;

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
                        src: elementData.src || null, // Original source from JSON (can be null)
                        originalLayoutDataIndex: imagePlaceholderCount, // Use this for consistent demo image assignment
                        show_frame: elementData.show_frame || false // Using show_frame from new JSON
                    };
                    imagePlaceholderCount++; // Increment for the next image element
                    break;

                case 'text':
                    data = {
                        content: elementData.content || '',
                        font_family: elementData.font_family || 'Arial',
                        font_color: elementData.font_color || '#000000',
                        font_size: elementData.font_size !== undefined ? parseFloat(elementData.font_size) : 2, // Ensure number
                        text_horizontal_align: elementData.text_horizontal_align || 'center',
                        text_vertical_align: elementData.text_vertical_align || 'center'
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

    /**
     * Updates the enabled/disabled state of the distribution buttons.
     */
    window.updateDistributionButtonStates = function() {
        const selectedElements = window.collageElements.filter(el => el.isSelected);
        const distributeH = document.getElementById('distributeHBtn');
        const distributeV = document.getElementById('distributeVBtn');

        if (selectedElements.length < 3) {
            // Less than 3 elements selected, disable all distribution buttons
            if (distributeH) distributeH.disabled = true;
            if (distributeV) distributeV.disabled = true;
            return;
        }
        // Enable all distribution buttons
        if (distributeH) distributeH.disabled = false;
        if (distributeV) distributeV.disabled = false;
    };

    /**
     * Update outline toggle button state.
     */
    window.updateOutlineToggleButtonState = function() {
        const outlineToggleBtn = document.getElementById('showElmntOutlineBtn');
        if (outlineToggleBtn) {
            if (window.globalShowElementOutlines) {
                outlineToggleBtn.classList.add('active');
                outlineToggleBtn.title = photoboothTools.getTranslation('Element Outlines: ON (Click to toggle)');
            } else {
                outlineToggleBtn.classList.remove('active');
                outlineToggleBtn.title = photoboothTools.getTranslation('Element Outlines: OFF (Click to toggle)');
            }
        }
    };

    /**
     * Update aspect ratio lock toggle button state.
     */
    window.updateAspectRatioLockButtonState = function() {
        const aspectRatioLockBtn = document.getElementById('lockAspectRatioBtn');
        if (aspectRatioLockBtn) {
            if (window.globalLockAspectRatio) {
                aspectRatioLockBtn.classList.add('active');
                aspectRatioLockBtn.title = photoboothTools.getTranslation('Aspect Ratio Lock: ON (Click to toggle)');
            } else {
                aspectRatioLockBtn.classList.remove('active');
                aspectRatioLockBtn.title = photoboothTools.getTranslation('Aspect Ratio Lock: OFF (Click to toggle)');
            }
        }
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
        const elementSnapshots = window.collageElements.map(el => {
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
                    snapshotEl.src = el.src || null;
                    snapshotEl.originalLayoutDataIndex = el.originalLayoutDataIndex;
                    snapshotEl.show_frame = el.show_frame;
                    break;
                case 'text':
                    snapshotEl.content = el.content;
                    snapshotEl.font_family = el.font_family;
                    snapshotEl.font_color = el.font_color;
                    snapshotEl.font_size = el.font_size;
                    snapshotEl.text_horizontal_align = el.text_horizontal_align; 
                    snapshotEl.text_vertical_align = el.text_vertical_align;
                    snapshotEl.font_bold = el.font_bold;
                    snapshotEl.font_italic = el.font_italic;
                    snapshotEl.font_underline = el.font_underline;
                    break;
            }
            return snapshotEl;
        });

        // Also snapshot global settings
        const globalSettings = {
            canvasHeight: window.collageCanvas.height,          // number
            canvasWidth: window.collageCanvas.width,            // number
            backgroundImage: window.backgroundImage,            // path
            backgroundColor: window.backgroundColor,            // solor as string
            showGlobalFrameImage: window.showGlobalFrameImage,  // boolean
            globalFrameImage: window.globalFrameImage           // path
        };

        // Der gesamte Snapshot enthält nun Elemente und globale Einstellungen
        return {
            elements: elementSnapshots,
            globalSettings: globalSettings
        };
    }

    /**
     * Restores the state of collage elements and global settings from a given snapshot.
     * @param {Array<object>} snapshot The snapshot to restore.
     */
    window.restoreSnapshot = function(snapshot) { 
        // Clear current selection
        window.collageElements.forEach(el => el.isSelected = false);
        window.activeElement = null;

        // 1. Restore global settings first
        const restoredGlobalSettings = snapshot.globalSettings;
        if (restoredGlobalSettings) {
            window.collageCanvas.height = restoredGlobalSettings.canvasHeight;
            window.collageCanvas.width = restoredGlobalSettings.canvasWidth;
            window.backgroundImage = restoredGlobalSettings.backgroundImage;
            window.backgroundColor = restoredGlobalSettings.backgroundColor;
            window.showGlobalFrameImage = restoredGlobalSettings.showGlobalFrameImage;
            window.globalFrameImage = restoredGlobalSettings.globalFrameImage;
        }

        // Create a new array for the elements, incorporating changes
        const newCollageElements = [];

        // 2. Update existing elements and add elements from snapshot that are new to current state
        snapshot.elements.forEach(snapEl => {
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
                            newImage.src = snapEl.imageSrc;
                            newImage.onload = window.drawCanvas;
                            newImage.onerror = () => { console.error(`Failed to load restored image: ${newImage.src}`); window.drawCanvas(); };
                            currentEl.image = newImage;
                        }
                        currentEl.src = snapEl.src;
                        currentEl.originalLayoutDataIndex = snapEl.originalLayoutDataIndex;
                        currentEl.show_frame = snapEl.show_frame;
                        break;
                    case 'text':
                        currentEl.content = snapEl.content;
                        currentEl.font_family = snapEl.font_family;
                        currentEl.font_color = snapEl.font_color;
                        currentEl.font_size = snapEl.font_size;
                        currentEl.text_horizontal_align = snapEl.text_horizontal_align;
                        currentEl.text_vertical_align = snapEl.text_vertical_align;
                        currentEl.font_bold = snapEl.font_bold;
                        currentEl.font_italic = snapEl.font_italic;
                        currentEl.font_underline = snapEl.font_underline;
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
                            src: snapEl.src,
                            originalLayoutDataIndex: snapEl.originalLayoutDataIndex !== undefined ? snapEl.originalLayoutDataIndex : -1,
                            show_frame: snapEl.show_frame
                        };
                        break;
                    case 'text':
                        recreatedData = {
                            content: snapEl.content,
                            font_family: snapEl.font_family,
                            font_color: snapEl.font_color,
                            font_size: snapEl.font_size,
                            text_horizontal_align: snapEl.text_horizontal_align,
                            text_vertical_align: snapEl.text_vertical_align,
                            font_bold: snapEl.font_bold,
                            font_italic: snapEl.font_italic,
                            font_underline: snapEl.font_underline
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
        window.updateGeneralSettingsPanel();  // Update settings panel to reflect restored state
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

    window.drawCanvas = async function() {
        window.ctx.clearRect(0, 0, window.collageCanvas.width, window.collageCanvas.height);

        // --- Draw background image ---
        if (window.backgroundImage && window.backgroundImage.trim() !== '') {
            const loadedBgImage = await loadImageFromSrc(window.backgroundImage); // Await the actual Image object
            if (loadedBgImage) { // Only draw if the image loaded successfully
                window.ctx.drawImage(loadedBgImage, 0, 0, loadedBgImage.width, loadedBgImage.height);
            } else {
                console.warn(`Could not draw background image from: ${window.backgroundImage}`);
            }
        } else {
            // No background image path, just use the background color
            window.ctx.fillStyle = window.backgroundColor;
            window.ctx.fillRect(0, 0, window.collageCanvas.width, window.collageCanvas.height);
        }

        const frameImage = window.loadedFrames['global_frame'];

        for (const element of window.collageElements) { 
            const { x, y, width, height, rotation, type } = element;

            window.ctx.save(); // Save the current context state
            
            if (type === 'image') {
                let image = element.image;

                // If a specific src is defined, load that image instead
                if (element.src && element.src.trim() !== '') {
                    image = await loadImageFromSrc(element.src);
                }

                if (image) {
                    if (element.show_frame && frameImage && frameImage.complete) {
                        // if frame active, create combined image with frame
                        image = combineImages(image, frameImage, width, height);
                    }

                    if (rotation !== 0) {
                        const preparedImageCanvas = prepareRotatedImage(image, rotation, width, height);
                        window.ctx.drawImage(preparedImageCanvas, x, y, width, height);
                    } else {
                        const imgAspectRatio = image.width / image.height;
                        const boxAspectRatio = width / height;
                        let sx, sy, sWidth, sHeight;
                        if (imgAspectRatio > boxAspectRatio) {
                            sHeight = image.height;
                            sWidth = sHeight * boxAspectRatio;
                            sx = (image.width - sWidth) / 2;
                            sy = 0;
                        } else {
                            sWidth = image.width;
                            sHeight = sWidth / boxAspectRatio;
                            sx = 0;
                            sy = (image.height - sHeight) / 2;
                        }
                        window.ctx.drawImage(image, sx, sy, sWidth, sHeight, x, y, width, height);
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
                const { content, font_family, font_color, font_size, text_horizontal_align, text_vertical_align, font_bold, font_italic, font_underline } = element;
                if (content) {

                    // apply rotation around center
                    const pivotX = x + width / 2;
                    const pivotY = y + height / 2;
                    if (rotation !== 0) {
                        window.ctx.translate(pivotX, pivotY);
                        window.ctx.rotate(-rotation * Math.PI / 180);
                        window.ctx.translate(-pivotX, -pivotY);
                    }

                    window.ctx.fillStyle = font_color;
                    
                    // Build the font string
                    let fontStyle = '';
                    if (font_italic) fontStyle += 'italic ';
                    let fontWeight = '';
                    if (font_bold) fontWeight += 'bold ';

                    const cssFontName = await window.loadFont(font_family);
                    const effectiveFontSizePx = (font_size / 100) * window.collageCanvas.height;
                    
                    window.ctx.font = `${fontStyle}${fontWeight}${effectiveFontSizePx}px "${cssFontName}", sans-serif`; 

                    window.ctx.textAlign = text_horizontal_align; // Directly use element.text_horizontal_align
                    window.ctx.textBaseline = 'middle'; // Center vertically in the bounding box

                    let translateX = x;
                    if (text_horizontal_align === 'left') {
                        //translateX = x
                    } else if (text_horizontal_align === 'right') {
                        translateX += width;
                    } else { // 'center'
                        translateX += width / 2;
                    }

                    let translateY = y;
                    if (text_vertical_align === 'top') {
                        translateY += effectiveFontSizePx / 2;
                    } else if (text_vertical_align === 'bottom') {
                        translateY += height - effectiveFontSizePx / 2;
                    } else { // 'center'
                        translateY += height / 2;
                    }

                    window.ctx.fillText(content, translateX, translateY);

                    // --- Underline Logic ---
                    if (font_underline) {
                        const metrics = window.ctx.measureText(content);
                        const textWidth = metrics.width;
                        // Calculate underline position. It needs to be below the text.
                        // `actualBoundingBoxDescent` gives the distance from baseline to bottom of text
                        const underlineOffset = metrics.actualBoundingBoxDescent + 2; // +2 for a small gap
                        
                        let underlineXStart;
                        if (text_horizontal_align === 'left') {
                            underlineXStart = -width / 2;
                        } else if (text_horizontal_align === 'right') {
                            underlineXStart = width / 2 - textWidth;
                        } else { // 'center'
                            underlineXStart = -textWidth / 2;
                        }

                        window.ctx.strokeStyle = font_color;
                        window.ctx.lineWidth = 2; // Adjust underline thickness as needed
                        window.ctx.beginPath();
                        window.ctx.moveTo(x + width/2 + underlineXStart, translateY + underlineOffset);
                        window.ctx.lineTo(x + width/2 + underlineXStart + textWidth, translateY + underlineOffset);
                        window.ctx.stroke();
                    }
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

            // Only draw default border if not selected
            if (window.globalShowElementOutlines && !element.isSelected) { 
                window.ctx.strokeStyle = BORDER_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.strokeRect(x, y, width, height);
            }
            if (element.isSelected) {
                window.ctx.strokeStyle = SELECTION_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.strokeRect(element.x, element.y, element.width, element.height);
            }

            window.ctx.restore(); // Restore context to original state before next element
        };

        // --- After all elements are drawn, draw the handles and selection overlay ---
        window.drawHandles();

        // --- draw global frame on top if set ---
        if (showGlobalFrameImage && window.globalFrameImage.trim() !== '') {
            const loadedFrImage = await loadImageFromSrc(window.globalFrameImage); // Await the actual Image object
            if (loadedFrImage) { // Only draw if the image loaded successfully
                window.ctx.drawImage(loadedFrImage, 0, 0, window.collageCanvas.width, window.collageCanvas.height);
            } else {
                console.warn(`Could not draw frame from: ${window.globalFrameImage}`);
            }
        }

        // --- Finally, update button states ---
        window.updateElementSettingsPanel();
        window.updateRemoveButtonState();
        window.updateLayerButtonStates();
        window.updateDistributionButtonStates();
        window.updateOutlineToggleButtonState();
    };

    //=================================================================================
    // --- Handles ---
    //=================================================================================
    window.currentHandles = {
        resize: [],         // Array of {type, cursor, handleX, handleY, handleWidth, handleHeight} (bounding box of handle itself)
        delete: null,       // {type, cursor, handleCenterX, handleCenterY, radius} (center and radius for circle test, rotation for cursor icon)
        rotate: null,       // {type, cursor, handleCenterX, handleCenterY, radius}
        selectionBox: null  // {type, x, y, width, height, rotation} (bounding box of the selection itself)
    };

    window.drawHandles = function() {
        // Clear previous handle data
        window.currentHandles = {
            resize: [],
            delete: null,
            rotate: null,
            selectionBox: null
        };

        const selectedElementsCount = window.collageElements.filter(el => el.isSelected).length;

        const visualScale = getCanvasVisualScale();
        const inverseScale = 1 / visualScale;

        const effectiveResizeHandleSize = RESIZE_HANDLE_SIZE * inverseScale;
        const effectiveRotationHandleSize = ROTATION_HANDLE_SIZE * inverseScale;
        const effectiveDeleteHandleSize = DELETE_HANDLE_SIZE * inverseScale;

        const effectiveRotationHandleOffset = ROTATION_HANDLE_OFFSET * inverseScale;
        const effectiveDeleteHandleOffset = DELETE_HANDLE_OFFSET * inverseScale;

        let targetForHandles = null; // Either activeElement or groupBoundingBox for resizing
        let targetRotation = 0; // Rotation for handles if a single element is selected

        if (selectedElementsCount === 1 && window.activeElement && window.activeElement.isSelected) {
            targetForHandles = window.activeElement;
            // The rotation for the handles depends on the type of the active element
            if (window.activeElement.type === 'text') {
                targetRotation = window.activeElement.rotation;
            } else if (window.activeElement.type === 'image') {
                // If images use `prepareRotatedImage` and the bounding box should still be axis-aligned,
                // then targetRotation remains 0 for images. 
                targetRotation = 0; 
            }
        } else if (selectedElementsCount > 1) {
            targetForHandles = getSelectionBoundingBox(); // Group bounding box is always axis-aligned, so rotation is 0
            targetRotation = 0; 
        }

        if (targetForHandles && targetForHandles.width > 0 && targetForHandles.height > 0) {
            const { x, y, width, height } = targetForHandles;

            // --- Save context for this selection/handle drawing phase ---
            window.ctx.save(); 

            // Apply transformations if a single, rotated text element is selected.
            // Group bounding box (selectedElementsCount > 1) is typically not rotated.
            // Image selection boxes are also handled as axis-aligned for now.
            if (selectedElementsCount === 1 && targetForHandles.type === 'text' && targetRotation !== 0) {
                const pivotX = x + width / 2;
                const pivotY = y + height / 2;
                window.ctx.translate(pivotX, pivotY);
                window.ctx.rotate(-targetRotation * Math.PI / 180); // Adjust sign for Canvas vs. PHP imagettftext
                window.ctx.translate(-pivotX, -pivotY);
            }

            // --- Draw selection border for the active element or group ---
            window.ctx.strokeStyle = SELECTION_COLOR;
            window.ctx.lineWidth = BORDER_WIDTH;
            if (selectedElementsCount > 1) {
                window.ctx.setLineDash([5, 5]); // Dashed line for group
            }
            window.ctx.strokeRect(x, y, width, height); // This rectangle will be rotated if context is rotated
            window.ctx.setLineDash([]); // Reset to solid line

            // Store selection box data for hit detection
            window.currentHandles.selectionBox = { x, y, width, height, rotation: targetRotation };

            // --- Draw Resizing Handles (at the corners of the rotated/unrotated bounding box) ---
            const handleOffsets = [
                { localX: 0     - effectiveResizeHandleSize / 2,    localY: 0       - effectiveResizeHandleSize / 2,    type: 'top-left',       cursor: 'nwse-resize' },
                { localX: width - effectiveResizeHandleSize / 2,    localY: 0       - effectiveResizeHandleSize / 2,    type: 'top-right',      cursor: 'nesw-resize' },
                { localX: 0     - effectiveResizeHandleSize / 2,    localY: height  - effectiveResizeHandleSize / 2,    type: 'bottom-left',    cursor: 'nesw-resize' },
                { localX: width - effectiveResizeHandleSize / 2,    localY: height  - effectiveResizeHandleSize / 2,    type: 'bottom-right',   cursor: 'nwse-resize' }
            ];
            
            handleOffsets.forEach(handleData  => {
                window.ctx.fillStyle = RESIZE_HANDLE_COLOR;
                window.ctx.strokeStyle = RESIZE_HANDLE_STROKE_COLOR;
                window.ctx.lineWidth = RESIZE_HANDLE_BORDER_WIDTH;
                window.ctx.fillRect(x + handleData.localX, y + handleData.localY, effectiveResizeHandleSize, effectiveResizeHandleSize);
                window.ctx.strokeRect(x + handleData.localX, y + handleData.localY, effectiveResizeHandleSize, effectiveResizeHandleSize);
                
                
                // Store handle data for mouse events - these are LOCAL to the targetForHandles
                window.currentHandles.resize.push({
                    type:           handleData.type,
                    cursor:         handleData.cursor,
                    handleLocalX:   handleData.localX,
                    handleLocalY:   handleData.localY,
                    handleWidth:    effectiveResizeHandleSize,
                    handleHeight:   effectiveResizeHandleSize
                });
            });

            // --- Draw Delete Handle (ONLY FOR SINGLE ACTIVE ELEMENT) ---
            if (selectedElementsCount === 1) { // Implicitly window.activeElement.isSelected is true here
                // Local coordinates for drawing (relative to targetForHandles's (x,y))
                const deleteHandleLocalCenterX = width - effectiveDeleteHandleOffset; // Positioned outside top-right
                const deleteHandleLocalCenterY = 0 + effectiveDeleteHandleOffset;
                const deleteHandleRadius = effectiveDeleteHandleSize / 2;

                window.ctx.beginPath();
                window.ctx.arc(x + deleteHandleLocalCenterX, y + deleteHandleLocalCenterY, deleteHandleRadius, 0, Math.PI * 2);
                window.ctx.fillStyle = DELETE_HANDLE_COLOR;
                window.ctx.fill();
                window.ctx.strokeStyle = DELETE_HANDLE_STROKE_COLOR;
                window.ctx.lineWidth = HANDLE_BORDER_WIDTH;
                window.ctx.stroke();
                window.ctx.fillStyle = DELETE_HANDLE_STROKE_COLOR;
                window.ctx.font = `${effectiveDeleteHandleSize * 0.7}px Arial`;
                window.ctx.textAlign = 'center';
                window.ctx.textBaseline = 'middle';
                window.ctx.fillText('X', x + deleteHandleLocalCenterX, y + deleteHandleLocalCenterY);

                // Store delete handle data for mouse events - LOCAL to targetForHandles
                window.currentHandles.delete = {
                    type: 'delete',
                    cursor: DELETE_CURSOR_URL, 
                    handleLocalX: deleteHandleLocalCenterX,
                    handleLocalY: deleteHandleLocalCenterY,
                    radius: deleteHandleRadius
                };

                // --- Draw Rotation Handle (ONLY FOR SINGLE ACTIVE ELEMENT) ---
                // Local coordinates for drawing (relative to targetForHandles's (x,y))
                const rotationHandleLocalCenterX = width / 2; // Above center
                const rotationHandleLocalCenterY = 0 - effectiveRotationHandleOffset;
                const rotationHandleRadius = effectiveRotationHandleSize / 2;

                window.ctx.beginPath();
                window.ctx.arc(x + rotationHandleLocalCenterX, y + rotationHandleLocalCenterY, rotationHandleRadius, 0, Math.PI * 2);
                window.ctx.fillStyle = ROTATION_HANDLE_COLOR;
                window.ctx.fill();
                window.ctx.strokeStyle = ROTATION_HANDLE_STROKE_COLOR;
                window.ctx.lineWidth = HANDLE_BORDER_WIDTH;
                window.ctx.stroke();
                window.ctx.fillStyle = ROTATION_HANDLE_STROKE_COLOR;
                window.ctx.font = `${effectiveRotationHandleSize * 0.7}px Arial`;
                window.ctx.textAlign = 'center';
                window.ctx.textBaseline = 'middle';
                window.ctx.fillText(ROTATION_HANDLE_ICON, x + rotationHandleLocalCenterX, y + rotationHandleLocalCenterY);

                // Store rotation handle data for mouse events - LOCAL to targetForHandles
                window.currentHandles.rotate = {
                    type: 'rotate',
                    cursor: ROTATION_CURSOR_URL, 
                    handleLocalX: rotationHandleLocalCenterX,
                    handleLocalY: rotationHandleLocalCenterY,
                    radius: rotationHandleRadius,
                };
            }
//TODO: selectedelements all blue frame
            window.ctx.restore(); // Restore context for this selection/handle drawing phase
        }
    };

    //=================================================================================
    // --- Mouse Event Handlers ---
    //=================================================================================

    // Helper function to get mouse position relative to canvas
    function getMousePos(event) {
        const rect = window.collageCanvas.getBoundingClientRect();
        const scaleX = window.collageCanvas.width / rect.width;
        const scaleY = window.collageCanvas.height / rect.height;
        return {
            x: (event.clientX - rect.left) * scaleX,
            y: (event.clientY - rect.top) * scaleY
        };
    }

    // --- Helper function for transforming mouse coordinates to element's local space ---
    function getLocalMouseCoordinates(globalX, globalY, element) {
        let effectiveRotation = element.rotation;
        if (element.type === 'image') {effectiveRotation = 0;} // Images are treated as unrotated for handle hit detection

        if (effectiveRotation === 0) {
            return  { x: globalX - element.x, y: globalY - element.y };
        }

        // Calculate element's center (pivot for rotation)
        const centerX = element.x + element.width / 2;
        const centerY = element.y + element.height / 2;

        // 1. Translate global mouse point so element's center becomes (0,0)
        const translatedX = globalX - centerX;
        const translatedY = globalY - centerY;

        // 2. Inverse rotate the translated point
        const angleRad = element.rotation * Math.PI / 180; // Negative angle for inverse rotation
        const inverselyRotatedX = translatedX * Math.cos(angleRad) - translatedY * Math.sin(angleRad);
        const inverselyRotatedY = translatedX * Math.sin(angleRad) + translatedY * Math.cos(angleRad);

        // 3. Translate the point back so element's top-left becomes (0,0) for local checks
        // This makes the transformed mouse coordinates relative to the element's top-left corner
        const localX = inverselyRotatedX + element.width / 2;
        const localY = inverselyRotatedY + element.height / 2;

        return { x: localX, y: localY };
    }

    // --- Helper function for point-in-rotated-rectangle test ---
    function isPointInRect(pointX, pointY, rectX, rectY, rectWidth, rectHeight) {
        return pointX >= rectX && pointX <= rectX + rectWidth &&
            pointY >= rectY && pointY <= rectY + rectHeight;
    }

    // --- Helper function for point-in-circle test ---
    function isPointInCircle(pointX, pointY, centerX, centerY, radius) {
        const dist = Math.sqrt(Math.pow(pointX - centerX, 2) + Math.pow(pointY - centerY, 2));
        return dist <= radius;
    }


    //---------------------------------------------------------------------------------
    function handleMouseDown(event) {
        const globalMouse = getMousePos(event);


        // Reset interaction flags
        isResizing = false;
        isRotating = false;
        isDragging = false;

        const selectedElementsCount = window.collageElements.filter(el => el.isSelected).length;

        // Check if there's an active element to interact with its handles
        if (window.activeElement) {
            // Transform global mouse coordinates into the local (unrotated) space of the active element
            const transformedMouse = getLocalMouseCoordinates(globalMouse.x, globalMouse.y, window.activeElement);

            // 1. Check for hits on handles first (they are always on top)
            // Check delete handle
            if (window.currentHandles.delete) {
                if (isPointInCircle(transformedMouse.x, transformedMouse.y, 
                                window.currentHandles.delete.handleLocalX, window.currentHandles.delete.handleLocalY,
                                window.currentHandles.delete.radius)) {
                
                console.log('Delete handle hit!');
                
                event.preventDefault(); // prevent that the click executes other interactions
                window.deleteSelectedElements(); // remove active element
                return;
                }
            }

            // Check rotate handle
            if (window.currentHandles.rotate) {
                if (isPointInCircle(transformedMouse.x, transformedMouse.y, 
                                window.currentHandles.rotate.handleLocalX, window.currentHandles.rotate.handleLocalY,
                                window.currentHandles.rotate.radius)) {
                    window.selectedHandle = { ...window.currentHandles.rotate, element: window.activeElement };

                    console.log('Rotate handle hit!');

                    isRotating = true;
                    window.saveState(); // Save state at start of rotation

                    // The rotationStartAngle needs to be calculated in the global coordinate system initially
                    // because the mouse movement is global.
                    const elementCenterX = window.activeElement.x + window.activeElement.width / 2;
                    const elementCenterY = window.activeElement.y + window.activeElement.height / 2;
                    rotationStartAngle = Math.atan2(globalMouse.y - elementCenterY, globalMouse.x - elementCenterX);
                    initialElementRotation = window.activeElement.rotation; // Store active element's rotation as start reference
                    window.collageCanvas.style.cursor = window.selectedHandle.cursor;
                    return;
                }
            }

            // Check resize handles
            let resizeXY;
            if (selectedElementsCount > 1) {
                    const boundingBox = getSelectionBoundingBox();
                    resizeXY = { x: globalMouse.x - boundingBox.x, y: globalMouse.y - boundingBox.y };
            } else { 
                resizeXY = transformedMouse;
            }

            for (const handle of window.currentHandles.resize) {
                if (isPointInRect(resizeXY.x, resizeXY.y, handle.handleLocalX, handle.handleLocalY, handle.handleWidth, handle.handleHeight)) {
                    window.selectedHandle = { ...handle, element: window.activeElement };

                    console.log('Resize handle hit:', handle.type);

                    isResizing = true;
                    window.saveState(); // Save state at start of resizing
                    resizeHandle = handle.type;
                    dragStartX = globalMouse.x;
                    dragStartY = globalMouse.y;
                    // Store initial state for resizing relative to the group/element
                    if (window.activeElement) {
                        initialElementWidth = window.activeElement.width;
                        initialElementHeight = window.activeElement.height;
                        initialElementX = window.activeElement.x;
                        initialElementY = window.activeElement.y;
                    } else { // Fallback for group resize (will be set from getSelectionBoundingBox in handleMouseMove)
                        const groupBoundingBox = getSelectionBoundingBox();
                        if (groupBoundingBox) {
                            initialElementWidth = groupBoundingBox.width;
                            initialElementHeight = groupBoundingBox.height;
                            initialElementX = groupBoundingBox.x;
                            initialElementY = groupBoundingBox.y;
                        }
                    }
                    window.collageCanvas.style.cursor = window.selectedHandle.cursor;
                    return; // Handle hit
                }
            }
        }

        // --- 2. If no handles hit, check for hits on elements themselves (for dragging/selection) ---
        let clickedOnElement = false;
        let elementClicked = null;

        // Find the topmost element clicked
        for (let i = window.collageElements.length - 1; i >= 0; i--) {
            const element = window.collageElements[i];
            if (element.isHit(globalMouse.x, globalMouse.y)) {
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
            dragStartX = globalMouse.x;
            dragStartY = globalMouse.y;

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
        window.collageCanvas.style.cursor = window.activeElement ? 'grabbing' : 'default';
        } else {
            // No element was clicked
            if (!event.ctrlKey && !event.metaKey) {
                window.collageElements.forEach(el => el.isSelected = false);
                window.activeElement = null;
            }
        }

        window.drawCanvas();
    }

    //---------------------------------------------------------------------------------
    function handleMouseMove(event) {
        const globalMouse = getMousePos(event); // Mouse coordinates relative to canvas

        // --- Cursor hover for handles (adjust for group vs. single) ---
        let cursorChanged = false;
        if (!isDragging && !isResizing && !isRotating) { // Only change cursor if no interaction is active
            const selectedElements = window.collageElements.filter(el => el.isSelected);
            const selectedElementsCount = selectedElements.length;

            // --- Determine the mouse coordinates for handle hit testing (similar to handleMouseDown) ---
            let mouseForHandleHitX, mouseForHandleHitY; // Mouse coordinates in the local space of the handle's "container"
            let handleContainerTarget = null; // Either activeElement or groupBoundingBox for reference

            if (selectedElementsCount === 1 && window.activeElement) {
                // Single element selection: Handles are relative to the activeElement.
                // Transform global mouse into the activeElement's local, unrotated space.
                const transformedMouse = getLocalMouseCoordinates(globalMouse.x, globalMouse.y, window.activeElement);
                mouseForHandleHitX = transformedMouse.x;
                mouseForHandleHitY = transformedMouse.y;
                handleContainerTarget = window.activeElement;
            } else if (selectedElementsCount > 1) {
                // Group selection: Handles are relative to the groupBoundingBox.
                // The groupBoundingBox is always axis-aligned.
                // We need mouse coordinates relative to the top-left of the groupBoundingBox.
                const groupBoundingBox = getSelectionBoundingBox();
                if (groupBoundingBox) {
                    mouseForHandleHitX = globalMouse.x - groupBoundingBox.x;
                    mouseForHandleHitY = globalMouse.y - groupBoundingBox.y;
                    handleContainerTarget = groupBoundingBox;
                }
            }

            if (handleContainerTarget) {
                // 1. Check delete handle (ONLY FOR SINGLE ACTIVE ELEMENT)
                if (selectedElementsCount === 1 && window.currentHandles.delete) {
                    if (isPointInCircle(mouseForHandleHitX, mouseForHandleHitY,
                                    window.currentHandles.delete.handleLocalX, window.currentHandles.delete.handleLocalY,
                                    window.currentHandles.delete.radius)) {
                        window.collageCanvas.style.cursor = DELETE_CURSOR_URL;
                        cursorChanged = true;
                    }
                }

                // 2. Check rotate handle (ONLY FOR SINGLE ACTIVE ELEMENT)
                if (!cursorChanged && selectedElementsCount === 1 && window.currentHandles.rotate) { // Only check if no other cursor changed
                    if (isPointInCircle(mouseForHandleHitX, mouseForHandleHitY,
                                    window.currentHandles.rotate.handleLocalX, window.currentHandles.rotate.handleLocalY,
                                    window.currentHandles.rotate.radius)) {
                        window.collageCanvas.style.cursor = ROTATION_CURSOR_URL;
                        cursorChanged = true;
                    }
                }

                // 3. Check resize handles (for single or multiple selection)
                if (!cursorChanged && window.currentHandles.resize.length > 0) { // Only check if no other cursor changed
                    for (const handle of window.currentHandles.resize) {
                        if (isPointInRect(mouseForHandleHitX, mouseForHandleHitY,
                                        handle.handleLocalX, handle.handleLocalY, handle.handleWidth, handle.handleHeight)) {
                            window.collageCanvas.style.cursor = handle.cursor;
                            cursorChanged = true;
                            break;
                        }
                    }
                }
            } // End if (handleContainerTarget)

            // 4. Check if hovering over the selection box itself (for dragging)
            if (!cursorChanged) {
                let overSelectedElement = false;
                for (let i = window.collageElements.length - 1; i >= 0; i--) { // Iterate over selected elements
                    const element = window.collageElements[i];
                    if (element.isHit(globalMouse.x, globalMouse.y)) { // isHit handles the internal transformation
                        overSelectedElement = true;
                        break;
                    }
                }
                if (overSelectedElement) {
                    window.collageCanvas.style.cursor = 'grab';
                    cursorChanged = true;
                }
            }

        } // End if (!isDragging && !isResizing && !isRotating)

        // If no specific cursor was set by handle or selection hover, revert to default.
        if (!cursorChanged && !isDragging && !isResizing && !isRotating) {
            window.collageCanvas.style.cursor = 'default';
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

            const currentAngle = Math.atan2(globalMouse.y - activeElementCenterY, globalMouse.x - activeElementCenterX);
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
            const deltaX = globalMouse.x - mouseStart.x;
            const deltaY = globalMouse.y - mouseStart.y;

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
            dragStartX = globalMouse.x;
            dragStartY = globalMouse.y;

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
            const dx = globalMouse.x - dragStartX;
            const dy = globalMouse.y - dragStartY;

            selected.forEach(element => {
                element.x += dx;
                element.y += dy;
            });

            // Update dragStartX/Y to current mouse position for continuous resizing
            dragStartX = globalMouse.x;
            dragStartY = globalMouse.y;

            window.drawCanvas();
            return;
        }
    }

    //---------------------------------------------------------------------------------
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



    //=================================================================================
    // --- Initialize the designer and save the very first state
    //=================================================================================
    initDesigner().then(() => {
        window.saveState(); // Save initial state after everything is loaded
        window.updateUndoRedoButtonStates(); // Update button states based on initial stack
    });
});