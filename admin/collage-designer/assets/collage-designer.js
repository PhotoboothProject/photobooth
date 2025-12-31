// admin/collage-designer/assets/collage-designer.js

document.addEventListener('DOMContentLoaded', () => {
    console.log('Collage Designer JS loaded.');

    const collageCanvas = document.getElementById('collageCanvas');
    const collageCanvasWrapper = document.getElementById('collageCanvasWrapper');
    const loadingOverlay = document.getElementById('loadingOverlay');

    const BASE_URL = typeof window.AppBaseUrl !== 'undefined' ? window.AppBaseUrl : './';

    // --- Utility Functions for Loading Overlay ---
    function showLoadingOverlay() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex'; // Use flex to center content
        }
    }

    function hideLoadingOverlay() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }
    // End of Utility Functions for Loading Overlay ---

    if (!collageCanvas || !collageCanvasWrapper || !initialCollageLayout || !initialDemoImagePaths) {
        console.error('Required elements or data not found for collage designer.');
        return;
    }

    const ctx = collageCanvas.getContext('2d');
    if (!ctx) {
        console.error('Failed to get 2D rendering context for canvas.');
        return;
    }

    // --- Configuration Constants ---
    const BORDER_COLOR = '#000000'; // Color for drawing layout box borders
    const BORDER_WIDTH = 2;         // Width of layout box borders
    const SELECTION_COLOR = 'rgba(0, 123, 255, 0.7)'; // Color for selected element border

    // Configuration for Resizing Handles ---
    const HANDLE_SIZE = 10;         // Size of the square handles
    const HANDLE_COLOR = '#FFFFFF'; // Color of the handle fill
    const HANDLE_STROKE_COLOR = SELECTION_COLOR; // Border color of the handle
    const HANDLE_BORDER_WIDTH = 2;  // Border width of the handle
    // End: Configuration for Resizing Handles ---

    // Configuration for Rotation Handle ---
    const ROTATION_HANDLE_SIZE = 16; // Size of the rotation handle (e.g., diameter of a circle)
    const ROTATION_HANDLE_OFFSET = 20; // Distance from the top center of the element box
    const ROTATION_HANDLE_COLOR = '#FFFFFF';
    const ROTATION_HANDLE_STROKE_COLOR = SELECTION_COLOR;
    const ROTATION_HANDLE_ICON = '\u21BA'; // Unicode for a counter-clockwise arrow (↺) or use another icon
    const ROTATION_HANDLE_ICON_FONT_SIZE = '12px Arial';
    const ROTATION_CURSOR_RELATIVE_PATH = 'assets/icons/rotate-cw.svg';
    const ROTATION_CURSOR_URL = `url("${BASE_URL}${ROTATION_CURSOR_RELATIVE_PATH}") 12 12, auto`;
    // End: Configuration for Rotation Handle ---

    let currentLayout = initialCollageLayout;
    let demoImagePaths = initialDemoImagePaths;
    let loadedImages = []; // Cache for loaded demo images

    // --- Interactive Elements Management ---
    let collageElements = []; // Array to hold instances of CollageElement
    let activeElement = null; // The currently selected/dragged element
    let isDragging = false;
    let dragStartX, dragStartY; // Mouse position where drag started
    let elementStartX, elementStartY; // Element position when drag started

    // Variables for Resizing ---
    let isResizing = false;
    let resizeHandle = null; // Stores which handle is being dragged ('top-left', 'bottom-right' etc.)
    let initialElementWidth, initialElementHeight; // Original dimensions when resizing started
    let initialElementX, initialElementY; // Original position when resizing started
    // End: Variables for Resizing ---

    // Variables for Rotation ---
    let isRotating = false;
    let rotationStartAngle = 0;     // Angle from element center to mouse start when rotation began
    let initialElementRotation = 0; // Original rotation of the element when rotation began
    // End: Variables for Rotation ---

    /**
     * Represents an interactive element (e.g., a picture box) on the collage canvas.
     */
    class CollageElement {
        constructor(id, x, y, width, height, rotation, originalLayoutDataIndex) {
            this.id = id;
            this.x = x;
            this.y = y;
            this.width = width;
            this.height = height;
            this.rotation = rotation; // in degrees
            this.originalLayoutDataIndex = originalLayoutDataIndex; // Reference to its original position in layout array
            this.image = null; // Reference to the loaded image for this element
        }

        /**
         * Checks if a point (mouseX, mouseY) is inside this element.
         * The hit test refers to the UNROTATED bounding box,
         * because the interaction is with the fixed "slot" in the layout.
         * @param {number} mouseX
         * @param {number} mouseY
         * @returns {boolean}
         */
        isHit(mouseX, mouseY) {
            // Hit test against the unrotated bounding box of the element.
            return mouseX >= this.x && mouseX <= this.x + this.width &&
                   mouseY >= this.y && mouseY <= this.y + this.height;
        }
    }

    /**
     * Helper function to rotate an image and then fit it into a target size (object-fit: contain).
     * This simulates the backend's behavior of rotating the image *before* placing it into a fixed box.
     * @param {Image} originalImage The original loaded image.
     * @param {number} degrees Rotation angle in degrees.
     * @param {number} targetWidth Desired width of the final (rotated & contained) image.
     * @param {number} targetHeight Desired height of the final (rotated & contained) image.
     * @returns {HTMLCanvasElement} A new canvas element containing the rotated and contained image.
     */
    function prepareRotatedImage(originalImage, degrees, targetWidth, targetHeight) {
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');

        // GD's imagerotate usually rotates counter-clockwise for positive degrees.
        // Canvas's ctx.rotate rotates clockwise for positive radians.
        // To achieve the same visual as GD's typical counter-clockwise rotation with positive values,
        // we negate the degrees for Canvas's clockwise rotation.
        const canvasRotationDegrees = -degrees; 

        // Calculate dimensions of the temporary canvas needed to hold the *full* rotated image without cropping.
        // This is the largest bounding box that would contain the rotated image.
        const imgWidth = originalImage.width;
        const imgHeight = originalImage.height;

        const absCos = Math.abs(Math.cos(canvasRotationDegrees * Math.PI / 180));
        const absSin = Math.abs(Math.sin(canvasRotationDegrees * Math.PI / 180));
        
        const rotatedBoundingWidth = imgWidth * absCos + imgHeight * absSin;
        const rotatedBoundingHeight = imgWidth * absSin + imgHeight * absCos;
        
        tempCanvas.width = rotatedBoundingWidth;
        tempCanvas.height = rotatedBoundingHeight;

        // Translate and rotate context to draw the image correctly centered on tempCanvas
        tempCtx.save();
        tempCtx.translate(rotatedBoundingWidth / 2, rotatedBoundingHeight / 2); // Move origin to center of bounding box
        tempCtx.rotate(canvasRotationDegrees * Math.PI / 180);                  // Rotate around this center
        tempCtx.drawImage(originalImage, -imgWidth / 2, -imgHeight / 2, imgWidth, imgHeight); // Draw image centered at new origin
        tempCtx.restore();

        // Now, tempCanvas contains the fully rotated image (possibly with empty space if not 90/180/270 degree rotation).
        // We now need to scale this rotated image from tempCanvas to fit targetWidth/targetHeight using object-fit: contain.
        const finalCanvas = document.createElement('canvas');
        const finalCtx = finalCanvas.getContext('2d');
        finalCanvas.width = targetWidth;
        finalCanvas.height = targetHeight;

        // --- NEW LOGIC: object-fit: contain ---
        // Scale the rotated image so it fits COMPLETELY within the target box,
        // without cropping any part of it. This might create empty space within the target box.

        const rotatedImgAspectRatio = tempCanvas.width / tempCanvas.height; // Actual aspect ratio of the rotated image on tempCanvas
        const targetAspectRatio = targetWidth / targetHeight;

        let drawX, drawY, drawWidth, drawHeight; // Target position and size on finalCanvas

        if (rotatedImgAspectRatio > targetAspectRatio) {
            // Rotated image is wider relative to its height than the target box.
            // Scale based on the width of the target box.
            drawWidth = targetWidth;
            drawHeight = targetWidth / rotatedImgAspectRatio;
        } else {
            // Rotated image is taller relative to its width than the target box (or has the same aspect ratio).
            // Scale based on the height of the target box.
            drawHeight = targetHeight;
            drawWidth = targetHeight * rotatedImgAspectRatio;
        }

        // Center the scaled image within the target box (finalCanvas)
        drawX = (targetWidth - drawWidth) / 2;
        drawY = (targetHeight - drawHeight) / 2;

        // Draw the complete rotated image (from tempCanvas) into the calculated size and position on finalCanvas
        finalCtx.drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, drawX, drawY, drawWidth, drawHeight);
        // --- END NEW LOGIC ---
        
        return finalCanvas;
    }

    /**
     * Sets the canvas dimensions and wrapper aspect ratio based on the layout data.
     */
    function setupCanvasDimensions() {
        const { width, height, aspect_ratio } = currentLayout;

        if (!width || !height || !aspect_ratio) {
            console.warn('Layout missing width, height, or aspect_ratio. Using default 3:2.');
            // Default values for robustness
            collageCanvasWrapper.style.aspectRatio = `3 / 2`;
            collageCanvas.width = 900;
            collageCanvas.height = 600;
            return;
        }

        collageCanvas.width = parseInt(width, 10);
        collageCanvas.height = parseInt(height, 10);
        collageCanvasWrapper.style.aspectRatio = aspect_ratio.replace(':', ' / ');
    }

    /**
     * Loads demo images into an array for drawing.
     * @returns {Promise<void>} A promise that resolves when all images are loaded.
     */
    function loadDemoImages() {
        showLoadingOverlay();
        const imagePromises = demoImagePaths.map((path, index) => {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    loadedImages[index] = img;
                    resolve();
                };
                img.onerror = () => {
                    console.warn(`Failed to load image: ${path}. Using placeholder.`);
                    loadedImages[index] = null;
                    resolve();
                };
                img.src = path;
            });
        });

        return Promise.all(imagePromises).finally(() => {
            hideLoadingOverlay();
        });
    }

    /**
     * Updates the collageElements array based on the currentLayout.
     * This creates our interactive "handles" for each picture box.
     */
    function updateCollageElements() {
        collageElements = []; // Clear existing elements
        const canvasWidth = collageCanvas.width;
        const canvasHeight = collageCanvas.height;

        if (!currentLayout.layout || currentLayout.layout.length === 0) {
            return;
        }

        currentLayout.layout.forEach((boxCoords, index) => {
            // Layout data structure is [xExpr, yExpr, widthExpr, heightExpr, rotationDegreesExpr]
            // We ensure rotationDegreesExpr exists before accessing
            const [xExpr, yExpr, widthExpr, heightExpr, rotationDegreesExpr = '0'] = boxCoords; 

            // Evaluate expressions to get pixel values
            const x = eval(xExpr.replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const y = eval(yExpr.replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const width = eval(widthExpr.replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const height = eval(heightExpr.replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const rotation = parseFloat(rotationDegreesExpr); // Parse rotation from layout

            const element = new CollageElement(
                `element-${index}`, // Unique ID for the element
                x, y, width, height, rotation,
                index // Store original index in layout array
            );
            // Assign a demo image to the element for drawing
            const demoImageIndex = index % loadedImages.length;
            element.image = loadedImages[demoImageIndex];

            collageElements.push(element);
        });
    }

    /**
     * Draws the collage layout and fills picture boxes with demo images.
     * Also draws selection border for active element.
     */
    function drawCollage() {
        ctx.clearRect(0, 0, collageCanvas.width, collageCanvas.height); // Clear canvas

        collageElements.forEach((element) => {
            const { x, y, width, height, rotation, image } = element;

            // --- LOGIC FOR IMAGE RENDERING ---
            if (image) {
                if (rotation !== 0) {
                    // If rotation is defined in the layout (not 0),
                    // we prepare the rotated and scaled image.
                    // prepareRotatedImage returns an HTMLCanvasElement,
                    // which can then be drawn just like an image.
                    const preparedImageCanvas = prepareRotatedImage(image, rotation, width, height);
                    ctx.drawImage(preparedImageCanvas, x, y, width, height);
                } else {
                    // No rotation, so just draw the original image with object-fit: cover logic.
                    // (Note: This is still 'cover' for unrotated images. If you also want 'contain' for unrotated,
                    // this block would need adjustment or a call to a more general prepare function.)
                    const imgAspectRatio = image.width / image.height;
                    const boxAspectRatio = width / height;

                    let sx, sy, sWidth, sHeight; // Source rectangle on the original image

                    if (imgAspectRatio > boxAspectRatio) {
                        // Image is wider than the box, crop sides
                        sHeight = image.height;
                        sWidth = sHeight * boxAspectRatio;
                        sx = (image.width - sWidth) / 2;
                        sy = 0;
                    } else {
                        // Image is taller than the box, crop top/bottom
                        sWidth = image.width;
                        sHeight = sWidth / boxAspectRatio;
                        sx = 0;
                        sy = (image.height - sHeight) / 2;
                    }
                    // Draw the cropped original image into the static (x,y,width,height) box
                    ctx.drawImage(image, sx, sy, sWidth, sHeight, x, y, width, height);
                }
            } else {
                // Draw placeholder if no image is available
                ctx.fillStyle = '#CCCCCC';
                ctx.fillRect(x, y, width, height);

                ctx.fillStyle = '#666666';
                ctx.font = `${Math.min(width, height) * 0.1}px Arial`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(`Image ${element.originalLayoutDataIndex + 1}`, x + width / 2, y + height / 2);
            }

            // The selection border is ALWAYS drawn around the UNROTATED BOX,
            // as interaction is with the "slot" in the layout.
            ctx.strokeStyle = (element === activeElement) ? SELECTION_COLOR : BORDER_COLOR;
            ctx.lineWidth = BORDER_WIDTH;
            ctx.strokeRect(x, y, width, height);

             // --- Draw Resizing Handles if element is active ---
            if (element === activeElement) {
                // Define handle positions (corners)
                const handles = [
                    { x: x,             y: y,              cursor: 'nwse-resize', name: 'top-left' },
                    { x: x + width,     y: y,              cursor: 'nesw-resize', name: 'top-right' },
                    { x: x,             y: y + height,     cursor: 'nesw-resize', name: 'bottom-left' },
                    { x: x + width,     y: y + height,     cursor: 'nwse-resize', name: 'bottom-right' }
                ];

                handles.forEach(handle => {
                    ctx.fillStyle = HANDLE_COLOR;
                    ctx.strokeStyle = HANDLE_STROKE_COLOR;
                    ctx.lineWidth = HANDLE_BORDER_WIDTH;
                    // Draw a square handle centered at the calculated position
                    ctx.fillRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
                    ctx.strokeRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
                });

                 // --- Draw Rotation Handle ---
                const rotationHandleX = x + width / 2; // Center top of the box
                const rotationHandleY = y - ROTATION_HANDLE_OFFSET; // Offset upwards

                ctx.beginPath();
                ctx.arc(rotationHandleX, rotationHandleY, ROTATION_HANDLE_SIZE / 2, 0, Math.PI * 2); // Circle
                ctx.fillStyle = ROTATION_HANDLE_COLOR;
                ctx.fill();
                ctx.strokeStyle = ROTATION_HANDLE_STROKE_COLOR;
                ctx.lineWidth = HANDLE_BORDER_WIDTH;
                ctx.stroke();

                // Draw the rotation icon (optional, using unicode character)
                ctx.fillStyle = ROTATION_HANDLE_STROKE_COLOR;
                ctx.font = ROTATION_HANDLE_ICON_FONT_SIZE;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(ROTATION_HANDLE_ICON, rotationHandleX, rotationHandleY);
                // --- END: Draw Rotation Handle ---
            }
            // --- END: Draw Resizing Handles ---
            // --- END LOGIC ---
        });
    }

    /**
     * Helper to get mouse coordinates relative to the canvas.
     * @param {MouseEvent} event
     * @returns {{x: number, y: number}}
     */
    function getMousePos(event) {
        const rect = collageCanvas.getBoundingClientRect();
        // Scale mouse coordinates to canvas coordinates (canvas resolution vs. display size)
        const scaleX = collageCanvas.width / rect.width;
        const scaleY = collageCanvas.height / rect.height;
        return {
            x: (event.clientX - rect.left) * scaleX,
            y: (event.clientY - rect.top) * scaleY
        };
    }

    /**
     * Event handler for mouse down.
     * @param {MouseEvent} event
     */
    function handleMouseDown(event) {
        const mouse = getMousePos(event);

         // --- Check for Rotation Handle hit FIRST ---
        if (activeElement) {
            const rotationHandleX = activeElement.x + activeElement.width / 2;
            const rotationHandleY = activeElement.y - ROTATION_HANDLE_OFFSET;

            // Calculate distance from mouse to center of rotation handle
            const dist = Math.sqrt(
                Math.pow(mouse.x - rotationHandleX, 2) +
                Math.pow(mouse.y - rotationHandleY, 2)
            );

            if (dist <= ROTATION_HANDLE_SIZE / 2) { // Mouse hit the rotation handle
                isRotating = true;
                // Calculate the angle from the element's center to the mouse position
                const elementCenterX = activeElement.x + activeElement.width / 2;
                const elementCenterY = activeElement.y + activeElement.height / 2;
                rotationStartAngle = Math.atan2(mouse.y - elementCenterY, mouse.x - elementCenterX);
                initialElementRotation = activeElement.rotation;
                collageCanvas.style.cursor = ROTATION_CURSOR_URL; // Or a specific rotate cursor
                drawCollage();
                return; // Rotation handle clicked, don't proceed further
            }
        }
        // --- END: Check for Rotation Handle hit ---

        // Check for handle hit FIRST, if an element is active
        if (activeElement) {
            const handles = [
                { x: activeElement.x,               y: activeElement.y,                 name: 'top-left' },
                { x: activeElement.x + activeElement.width, y: activeElement.y,         name: 'top-right' },
                { x: activeElement.x,               y: activeElement.y + activeElement.height,  name: 'bottom-left' },
                { x: activeElement.x + activeElement.width, y: activeElement.y + activeElement.height, name: 'bottom-right' }
            ];

            for (const handle of handles) {
                // Check if mouse is within the handle's clickable area
                if (mouse.x >= handle.x - HANDLE_SIZE / 2 && mouse.x <= handle.x + HANDLE_SIZE / 2 &&
                    mouse.y >= handle.y - HANDLE_SIZE / 2 && mouse.y <= handle.y + HANDLE_SIZE / 2) {
                    
                    isResizing = true;
                    resizeHandle = handle.name;
                    dragStartX = mouse.x;
                    dragStartY = mouse.y;
                    initialElementWidth = activeElement.width;
                    initialElementHeight = activeElement.height;
                    initialElementX = activeElement.x;
                    initialElementY = activeElement.y;
                    
                    // Set cursor based on handle
                    switch(resizeHandle) {
                        case 'top-left':
                        case 'bottom-right':
                            collageCanvas.style.cursor = 'nwse-resize';
                            break;
                        case 'top-right':
                        case 'bottom-left':
                            collageCanvas.style.cursor = 'nesw-resize';
                            break;
                    }
                    drawCollage(); // Redraw to potentially update cursor visually if needed (though browser does this)
                    return; // Handle clicked, don't proceed to drag logic
                }
            }
        }

        // If no handle was clicked, proceed with element dragging logic
        // Iterate elements in reverse order to select topmost
        for (let i = collageElements.length - 1; i >= 0; i--) {
            const element = collageElements[i];
            if (element.isHit(mouse.x, mouse.y)) {
                activeElement = element;
                isDragging = true;
                dragStartX = mouse.x;
                dragStartY = mouse.y;
                elementStartX = element.x;
                elementStartY = element.y;
                collageCanvas.style.cursor = 'grabbing'; // Change cursor
                drawCollage(); // Redraw to show selection border and handles
                return; // Only select one element
            }
        }
        activeElement = null; // Deselect if no element was hit
        drawCollage(); // Redraw to remove selection border and handles
    }

    /**
     * Event handler for mouse move.
     * @param {MouseEvent} event
     */
    function handleMouseMove(event) {
        const mouse = getMousePos(event);

        // --- Cursor hover for handles ---
        // --- Cursor hover for Rotation Handle ---
        let cursorChanged = false;
        if (activeElement && !isDragging && !isResizing && !isRotating) {
            const rotationHandleX = activeElement.x + activeElement.width / 2;
            const rotationHandleY = activeElement.y - ROTATION_HANDLE_OFFSET;
            const dist = Math.sqrt(
                Math.pow(mouse.x - rotationHandleX, 2) +
                Math.pow(mouse.y - rotationHandleY, 2)
            );
            if (dist <= ROTATION_HANDLE_SIZE / 2) {
                collageCanvas.style.cursor = ROTATION_CURSOR_URL; // Or specific 'ew-resize' / 'grabbing' for rotation
                cursorChanged = true;
            }
        }
        // --- Cursor hover for scale Handle ---
        if (activeElement && !isDragging && !isResizing) { // Only change cursor if not dragging/resizing
            const handles = [
                { x: activeElement.x,               y: activeElement.y,                 cursor: 'nwse-resize', name: 'top-left' },
                { x: activeElement.x + activeElement.width, y: activeElement.y,         cursor: 'nesw-resize', name: 'top-right' },
                { x: activeElement.x,               y: activeElement.y + activeElement.height,  cursor: 'nesw-resize', name: 'bottom-left' },
                { x: activeElement.x + activeElement.width, y: activeElement.y + activeElement.height, cursor: 'nwse-resize', name: 'bottom-right' }
            ];
            for (const handle of handles) {
                if (mouse.x >= handle.x - HANDLE_SIZE / 2 && mouse.x <= handle.x + HANDLE_SIZE / 2 &&
                    mouse.y >= handle.y - HANDLE_SIZE / 2 && mouse.y <= handle.y + HANDLE_SIZE / 2) {
                    collageCanvas.style.cursor = handle.cursor;
                    cursorChanged = true;
                    break;
                }
            }
        }
        if (!cursorChanged && !isDragging && !isResizing && !isRotating) {
            // Restore default cursor if not over a handle and not dragging/resizing
            collageCanvas.style.cursor = 'grab'; // Default cursor for draggable elements
        }
        // --- END: Cursor hover for handles ---

        // --- Rotation Logic ---
        if (isRotating && activeElement) {
            const elementCenterX = activeElement.x + activeElement.width / 2;
            const elementCenterY = activeElement.y + activeElement.height / 2;

            // Calculate current angle from element center to mouse position
            const currentAngle = Math.atan2(mouse.y - elementCenterY, mouse.x - elementCenterX);

            // Calculate the difference in angle since rotation started
            let angleDiff = (rotationStartAngle - currentAngle) * 180 / Math.PI; // Convert to degrees

            // Apply rotation (snap to 15-degree increments if Shift is pressed)
            let newRotation = initialElementRotation + angleDiff;
            if (event.shiftKey) {
                newRotation = Math.round(newRotation / 15) * 15;
            }
            // Normalize rotation to be within 0-360 degrees if desired, or allow negative
            activeElement.rotation = (newRotation % 360 + 360) % 360; 

            drawCollage();
            return; // Rotation is active, don't proceed to resizing/dragging
        }
        // --- END: Rotation Logic ---

        // --- Scaling Logic ---
        if (isResizing && activeElement) {
            const dx = mouse.x - dragStartX;
            const dy = mouse.y - dragStartY;

            let newWidth = initialElementWidth;
            let newHeight = initialElementHeight;
            let newX = initialElementX;
            let newY = initialElementY;

            const aspectRatio = initialElementWidth / initialElementHeight;

            switch (resizeHandle) {
                case 'top-left':
                    newWidth = initialElementWidth - dx;
                    newHeight = initialElementHeight - dy;
                    if (event.shiftKey) { // Maintain aspect ratio
                        if (Math.abs(dx) > Math.abs(dy)) { // Adjust based on larger movement
                            newHeight = newWidth / aspectRatio;
                        } else {
                            newWidth = newHeight * aspectRatio;
                        }
                    }
                    newX = initialElementX + (initialElementWidth - newWidth);
                    newY = initialElementY + (initialElementHeight - newHeight);
                    break;
                case 'top-right':
                    newWidth = initialElementWidth + dx;
                    newHeight = initialElementHeight - dy;
                    if (event.shiftKey) { // Maintain aspect ratio
                        if (Math.abs(dx) > Math.abs(dy)) {
                            newHeight = newWidth / aspectRatio;
                        } else {
                            newWidth = newHeight * aspectRatio;
                        }
                    }
                    newY = initialElementY + (initialElementHeight - newHeight);
                    break;
                case 'bottom-left':
                    newWidth = initialElementWidth - dx;
                    newHeight = initialElementHeight + dy;
                    if (event.shiftKey) { // Maintain aspect ratio
                        if (Math.abs(dx) > Math.abs(dy)) {
                            newHeight = newWidth / aspectRatio;
                        } else {
                            newWidth = newHeight * aspectRatio;
                        }
                    }
                    newX = initialElementX + (initialElementWidth - newWidth);
                    break;
                case 'bottom-right':
                    newWidth = initialElementWidth + dx;
                    newHeight = initialElementHeight + dy;
                    if (event.shiftKey) { // Maintain aspect ratio
                        if (Math.abs(dx) > Math.abs(dy)) {
                            newHeight = newWidth / aspectRatio;
                        } else {
                            newWidth = newHeight * aspectRatio;
                        }
                    }
                    break;
            }

            // Apply minimum size constraint
            const MIN_SIZE = 20; // Example minimum size
            if (newWidth < MIN_SIZE) {
                newWidth = MIN_SIZE;
                if (event.shiftKey) newHeight = newWidth / aspectRatio;
                // Recalculate X if resizing from left
                if (resizeHandle.includes('left')) newX = initialElementX + (initialElementWidth - newWidth);
            }
            if (newHeight < MIN_SIZE) {
                newHeight = MIN_SIZE;
                if (event.shiftKey) newWidth = newHeight * aspectRatio;
                // Recalculate Y if resizing from top
                if (resizeHandle.includes('top')) newY = initialElementY + (initialElementHeight - newHeight);
            }
            
            activeElement.x = newX;
            activeElement.y = newY;
            activeElement.width = newWidth;
            activeElement.height = newHeight;

            drawCollage();
            return; // Resizing, so don't proceed to drag logic
        }
        // --- END: Scaling Logic ---

        // --- Dragging Logic ---
        if (isDragging && activeElement) {
            const dx = mouse.x - dragStartX;
            const dy = mouse.y - dragStartY;

            activeElement.x = elementStartX + dx;
            activeElement.y = elementStartY + dy;

            drawCollage();
            return; // Dragging, so done
        }
        // --- END: Dragging Logic ---

        // If neither resizing nor dragging, just update cursor based on hover
        // (This part is handled by the new cursor hover block at the beginning)
    }

    /**
     * Event handler for mouse up.
     * @param {MouseEvent} event
     */
    function handleMouseUp(event) {
        if (isDragging) {
            isDragging = false;
            // TODO: Update the currentLayout.layout array with the new element position
            // (Only if changes should persist, for now internal `activeElement.x/y` are updated).
        }
        if (isResizing) {
            isResizing = false;
            // TODO: Update the currentLayout.layout array with the new element size/position
            // (Only if changes should persist, for now internal `activeElement.width/height/x/y` are updated).
        }
        if (isRotating) {
            isRotating = false;
            // TODO: Update currentLayout.layout for rotation
        }
        
        // Restore default cursor, but first check if still over an element that could be grabbed
        if (activeElement) {
            collageCanvas.style.cursor = 'grab'; // Restore grab cursor if an element is active
        } else {
            collageCanvas.style.cursor = 'default'; // Or default if nothing is active
        }
        // No need to deselect activeElement here, as it might still be selected for further interaction
        // If you want to deselect after every drag/resize, move 'activeElement = null;' here.
        drawCollage(); // Final redraw
    }

    /**
     * Initializes the designer.
     */
    async function initDesigner() {
        setupCanvasDimensions();
        await loadDemoImages();
        updateCollageElements(); // Initialize interactive elements
        drawCollage(); // Initial draw
    }

    // --- Event Listeners ---
    collageCanvas.addEventListener('mousedown', handleMouseDown);
    collageCanvas.addEventListener('mousemove', handleMouseMove);
    collageCanvas.addEventListener('mouseup', handleMouseUp);
    collageCanvas.addEventListener('mouseout', handleMouseUp); // End drag if mouse leaves canvas

    // Initial setup
    initDesigner();
});