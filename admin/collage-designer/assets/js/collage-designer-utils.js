// admin/collage-designer/assets/js/collage-designer-utils.js

//=================================================================================
// --- Utility Functions ---
//=================================================================================
    
    /**
     * debounce function to limit the rate of function calls
     * 
     * @param {Function} func The function to debounce.
     * @param {number} delay The debounce delay in milliseconds.
     * @returns {Function} The debounced function.
     */
    window.debounce = function(func, delay) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    //=================================================================================
    // --- Element Management Functions ---
    //=================================================================================

    /**
     * Adds a new placeholder element to the canvas.
     * 
     * @param {number} x Optional x-coordinate. Defaults to center of canvas if not provided.
     * @param {number} y Optional y-coordinate. Defaults to center of canvas if not provided.
     * @param {number} width Optional width. Defaults to a standard size.
     * @param {number} height Optional height. Defaults to a standard size.
     * @param {number} rotation Optional rotation. Defaults to 0.
     * @param {string} type Type of the element ('image', 'text', etc.). Defaults to 'image'.
     * @param {object} data Additional data specific to the element type.
     * @returns {CollageElement} The newly created element.
     */
    window.addNewElement = async function(x, y, width, height, rotation = 0, type = 'image', data = {}) { // Added type and data
        window.saveState(); 

        const canvasWidth = window.collageCanvas.width;
        const canvasHeight = window.collageCanvas.height;

        const defaultImageWidth = canvasWidth / 2;
        const defaultImageHeight = canvasHeight / 2;
        const defaultTextWidth = canvasWidth / 4;
        const defaultTextHeight = canvasHeight / 8;
        
        const offset = window.collageElements.filter(el => el.originalLayoutDataIndex === -1).length * 10; 

        let finalX = x !== undefined ? x : (canvasWidth / 2) - ((type === 'text' ? defaultTextWidth : defaultImageWidth) / 2) + offset;
        let finalY = y !== undefined ? y : (canvasHeight / 2) - ((type === 'text' ? defaultTextHeight : defaultImageHeight) / 2) + offset;

        const newId = `element-${type}-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        
        // Type-specific data preparation
        let elementData = { ...data }; // Copy any passed data

        switch (type) {
            case 'image':
                let imageUrl = null;
                try {
                    const fetchedUrls = await window.fetchDemoImageUrls(1);
                    imageUrl = fetchedUrls[0];
                } catch (error) {
                    console.error('Could not fetch demo image for new element, using fallback.', error);
                    imageUrl = window.phpFallbackImageUrl;
                }
                const img = new Image();
                img.crossOrigin = "anonymous";
                img.src = imageUrl;
                elementData.image = img;
                elementData.originalLayoutDataIndex = -1; // Indicates it's an added element
                elementData.show_frame = false; // Default for new image element
                break;
            case 'text':
                elementData.content = elementData.content || photoboothTools.getTranslation('new_text_element'); // New translation key
                elementData.font_family = elementData.font_family || 'resources/fonts/GreatVibes-Regular.ttf'; // Default or from global settings
                elementData.font_color = elementData.font_color || '#000000';
                elementData.font_size = elementData.font_size !== undefined ? elementData.font_size : 5; // Default font size
                elementData.font_bold = elementData.font_bold || false;
                elementData.font_italic = elementData.font_italic || false;
                elementData.font_underline = elementData.font_underline || false;
                elementData.originalLayoutDataIndex = -1; // Indicates it's an added element
                break;
            // Add more cases for other types as needed
        }

        const newElement = new CollageElement(
            newId,
            finalX,
            finalY,
            width !== undefined ? width : (type === 'text' ? defaultTextWidth : defaultImageWidth),
            height !== undefined ? height : (type === 'text' ? defaultTextHeight : defaultImageHeight),
            rotation,
            type, // Pass the element type
            elementData // Pass the prepared data object
        );

        window.collageElements.push(newElement);

        window.collageElements.forEach(el => el.isSelected = false);
        newElement.isSelected = true;
        window.activeElement = newElement;

        window.drawCanvas();
        
        // For images, we need to redraw after loading
        if (type === 'image' && newElement.image) {
            newElement.image.onload = () => {
                window.drawCanvas();
                window.saveState();
            };
            newElement.image.onerror = () => {
                console.error(`Failed to load image for element ${newId}: ${newElement.image.src}`);
                window.drawCanvas();
                window.saveState();
            };
        } else {
            window.saveState(); // Save state immediately for text/other elements
        }
        
        return newElement;
    };

    /**
     * removes all selected Collage-Elementes from the canvas
     */
    window.deleteSelectedElements = function() {
        const selectedElements = window.collageElements.filter(el => el.isSelected);
        if (selectedElements.length === 0) {
            return; // nothing to remove
        }

        window.saveState(); 

        // removes selected elements from the main array
        window.collageElements = window.collageElements.filter(el => !el.isSelected);

        // resets activeElement, if it was deleted
        if (window.activeElement && !window.collageElements.includes(window.activeElement)) {
            window.activeElement = null;
        }

        window.drawCanvas();
        window.updateUndoRedoButtonStates();
    };

    /**
     * Gets currently selected elements from all known element arrays
     * 
     * @returns {Array<CollageElement>} An array of selected elements
     */
    window.getSelectedElements = function() {
        const selected = [];
        // Add elements from window.collageElements (your image boxes)
        window.collageElements.forEach(el => {
            if (el.isSelected) selected.push(el);
        });
        return selected;
    }

    /**
     * function to update the collage elements based on the current layout data.
     * @param {Array<Image>} loadedImagesArray 
     * @returns 
     */
    window.updateCollageElements = function(loadedImagesArray) {
        window.collageElements = [];
        const canvasWidth = window.collageCanvas.width;
        const canvasHeight = window.collageCanvas.height;

        // Check if currentLayout has the new 'elements' array
        if (!window.currentLayout.elements || !Array.isArray(window.currentLayout.elements) || window.currentLayout.elements.length === 0) {
            console.warn('Current layout does not contain a valid "elements" array in the new JSON format. No elements will be loaded.');
            // If no elements in new format, ensure existing window.collageElements is empty and redraw.
            window.collageElements = [];
            return; 
        }

        let imagePlaceholderCount = 0; // To correctly map demo images to image elements

        // Update genral settings based on currentLayout
        window.backgroundImage = window.currentLayout.background_image || null;
        window.backgroundColor = window.currentLayout.background_color || '#ffffff';
        window.showGlobalFrameImage = window.currentLayout.show_global_frame_image || false;
        window.globalFrameImage = window.currentLayout.global_frame_image || null;

        window.currentLayout.elements.forEach((elementData) => {
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
     * 
     * @returns {{x: number, y: number, width: number, height: number}|null} The bounding box or null if no elements are selected.
     */
    window.getSelectionBoundingBox = function() {
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


    //=================================================================================
    // --- Img functions ---
    //=================================================================================

    /**
     * Global Function to fetch Demo Image URLs
     * 
     * @param {int} count 
     * @returns imgURLs Array of image URLs
     */
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

    /**
     * Prepares a rotated image on a temporary canvas and scales/crops it to fit target dimensions
     * 
     * @param {img} backgroundImg 
     * @param {int} degrees 
     * @param {int} targetWidth 
     * @param {int} targetHeight 
     * @returns 
     */
    window.prepareRotatedImage = function(backgroundImg, degrees, targetWidth, targetHeight) {
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

    /**
     * Function to combine two images into one canvas.
     * @param {img} backgroundImg 
     * @param {img} frontImg 
     * @param {int} width 
     * @param {int} height 
     * @returns 
     */
    window.combineImages = function(backgroundImg, frontImg, width, height) {
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

    window.setupCanvasDimensions = function() {
        const { width, height, aspect_ratio } = window.currentLayout;
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
    window.loadFrameImg = function() { 
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
    window.loadImageFromSrc = async function(src) {
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

    /**
     * Loads demo images based on the current layout's image placeholders.
     * 
     * @returns {Promise<Array<Image>>} A promise that resolves to an array of loaded Image objects.
     */
    window.loadDemoImages = async function() {
        window.showLoadingOverlay();
        let fetchedPaths = [];
        let loadedImagesLocal = [];

        // Determine how many images are actually needed from the new 'elements' array
        // We only count elements of type 'image'
        const numImageElements = window.currentLayout.elements ? window.currentLayout.elements.filter(el => el.type === 'image').length : 0;
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
           window.hideLoadingOverlay();
        });
    }


    /**
     * calculates the current visual scaling factor of the Canvas element.
     * This is the CSS scaling factor that influences the perceived size of the handles.
     * @returns {number} The scaling factor (e.g., 1.0 for original size, 0.5 for half size, 2.0 for double size).
     */
    window.getCanvasVisualScale = function() {
        const rect = window.collageCanvas.getBoundingClientRect();
        // The scaling factor is the ratio of the actual HTML width to the CSS width
        // window.collageCanvas.width is the rendered width (pixels)
        // rect.width is the visual width (CSS pixels)
        // If the canvas (e.g., 900px wide) is in a div that is 450px wide, the scaling factor is 0.5.
        // If it's displayed 1800px wide, the scaling factor is 2.0.
        return rect.width / window.collageCanvas.width; 
    }

    //=================================================================================
    // --- Utility Functions for Loading Overlay ---
    //=================================================================================
    window.showLoadingOverlay = function() {
        if (window.loadingOverlay) {
            window.loadingOverlay.style.display = 'flex';
        }
    }

    window.hideLoadingOverlay = function() {
        if (window.loadingOverlay) {
            window.loadingOverlay.style.display = 'none';
        }
    }