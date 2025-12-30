// admin/collage-designer/assets/collage-designer.js

document.addEventListener('DOMContentLoaded', () => {
    console.log('Collage Designer JS loaded.');

    const collageCanvas = document.getElementById('collageCanvas');
    const collageCanvasWrapper = document.getElementById('collageCanvasWrapper');
    const loadingOverlay = document.getElementById('loadingOverlay');

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

    let currentLayout = initialCollageLayout;
    let demoImagePaths = initialDemoImagePaths;
    let loadedImages = []; // Cache for loaded demo images

    // --- Interactive Elements Management ---
    let collageElements = []; // Array to hold instances of CollageElement
    let activeElement = null; // The currently selected/dragged element
    let isDragging = false;
    let dragStartX, dragStartY; // Mouse position where drag started
    let elementStartX, elementStartY; // Element position when drag started

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
                drawCollage(); // Redraw to show selection border
                return; // Only select one element
            }
        }
        activeElement = null; // Deselect if no element was hit
        drawCollage(); // Redraw to remove selection border
    }

    /**
     * Event handler for mouse move.
     * @param {MouseEvent} event
     */
    function handleMouseMove(event) {
        if (!isDragging || !activeElement) return;

        const mouse = getMousePos(event);
        const dx = mouse.x - dragStartX;
        const dy = mouse.y - dragStartY;

        activeElement.x = elementStartX + dx;
        activeElement.y = elementStartY + dy;

        // Immediately redraw for smooth dragging feedback
        drawCollage();
    }

    /**
     * Event handler for mouse up.
     * @param {MouseEvent} event
     */
    function handleMouseUp(event) {
        if (isDragging) {
            isDragging = false;
            collageCanvas.style.cursor = 'grab'; // Restore cursor
            // TODO: Here we would update the currentLayout.layout array with the new element position
            // and trigger an API call if we were using server-side rendering.
            // For now, the internal `activeElement.x` and `activeElement.y` are updated.
        }
        activeElement = null; // Deselect after drag is finished
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