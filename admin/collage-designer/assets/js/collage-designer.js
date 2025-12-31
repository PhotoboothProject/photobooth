// admin/collage-designer/assets/js/collage-designer.js

document.addEventListener('DOMContentLoaded', () => {
    console.log('Collage Designer JS loaded.');

    // --- Global Variables (exposed via window for external scripts) ---
    window.collageCanvas = document.getElementById('collageCanvas');
    window.collageCanvasWrapper = document.getElementById('collageCanvasWrapper');
    window.loadingOverlay = document.getElementById('loadingOverlay');

    window.ctx = window.collageCanvas.getContext('2d');
    window.collageElements = [];
    window.activeElement = null; // Represents the *single* element currently being interacted with (dragged, resized, rotated)

    window.textFields = [];
    window.imagePlaceholders = [];

    // --- Local Variables (not exposed globally) ---
    const BASE_URL = typeof window.AppBaseUrl !== 'undefined' ? window.AppBaseUrl : './';

    let currentLayout = initialCollageLayout;
    let demoImagePaths = initialDemoImagePaths;
    let loadedImages = [];

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

    // --- Utility Functions for Loading Overlay ---
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

    if (!window.collageCanvas || !window.collageCanvasWrapper || !initialCollageLayout || !initialDemoImagePaths) {
        console.error('Required elements or data not found for collage designer.');
        return;
    }

    if (!window.ctx) {
        console.error('Failed to get 2D rendering context for canvas.');
        return;
    }

    // --- Configuration Constants ---
    const BORDER_COLOR = '#000000';
    const BORDER_WIDTH = 2;
    const SELECTION_COLOR = 'rgba(0, 123, 255, 0.7)';

    const HANDLE_SIZE = 10;
    const HANDLE_COLOR = '#FFFFFF';
    const HANDLE_STROKE_COLOR = SELECTION_COLOR;
    const HANDLE_BORDER_WIDTH = 2;

    const ROTATION_HANDLE_SIZE = 16;
    const ROTATION_HANDLE_OFFSET = 20;
    const ROTATION_HANDLE_COLOR = '#FFFFFF';
    const ROTATION_HANDLE_STROKE_COLOR = SELECTION_COLOR;
    const ROTATION_HANDLE_ICON = '\u21BA';
    const ROTATION_HANDLE_ICON_FONT_SIZE = '12px Arial';
    const ROTATION_CURSOR_RELATIVE_PATH = 'assets/icons/rotate-cw.svg';
    const ROTATION_CURSOR_URL = `url("${BASE_URL}${ROTATION_CURSOR_RELATIVE_PATH}") 12 12, auto`;

    class CollageElement {
        constructor(id, x, y, width, height, rotation, originalLayoutDataIndex) {
            this.id = id;
            this.x = x;
            this.y = y;
            this.width = width;
            this.height = height;
            this.rotation = rotation;
            this.originalLayoutDataIndex = originalLayoutDataIndex;
            this.image = null;
            this.isSelected = false; // Tracks if element is part of a selection (multi or single)
        }

        isHit(mouseX, mouseY) {
            return mouseX >= this.x && mouseX <= this.x + this.width &&
                   mouseY >= this.y && mouseY <= this.y + this.height;
        }
    }

    function prepareRotatedImage(originalImage, degrees, targetWidth, targetHeight) {
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');
        const canvasRotationDegrees = -degrees;
        const imgWidth = originalImage.width;
        const imgHeight = originalImage.height;
        const absCos = Math.abs(Math.cos(canvasRotationDegrees * Math.PI / 180));
        const absSin = Math.abs(Math.sin(canvasRotationDegrees * Math.PI / 180));
        const rotatedBoundingWidth = imgWidth * absCos + imgHeight * absSin;
        const rotatedBoundingHeight = imgWidth * absSin + imgHeight * absCos;
        tempCanvas.width = rotatedBoundingWidth;
        tempCanvas.height = rotatedBoundingHeight;
        tempCtx.save();
        tempCtx.translate(rotatedBoundingWidth / 2, rotatedBoundingHeight / 2);
        tempCtx.rotate(canvasRotationDegrees * Math.PI / 180);
        tempCtx.drawImage(originalImage, -imgWidth / 2, -imgHeight / 2, imgWidth, imgHeight);
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

    function updateCollageElements() {
        window.collageElements = [];
        const canvasWidth = window.collageCanvas.width;
        const canvasHeight = window.collageCanvas.height;
        if (!currentLayout.layout || currentLayout.layout.length === 0) {
            return;
        }
        currentLayout.layout.forEach((boxCoords, index) => {
            const [xExpr, yExpr, widthExpr, heightExpr, rotationDegreesExpr = '0'] = boxCoords; 
            const x = eval(xExpr.replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const y = eval(yExpr.replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const width = eval(widthExpr.replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const height = eval(heightExpr.replace(/x/g, canvasWidth).replace(/y/g, canvasHeight));
            const rotation = parseFloat(rotationDegreesExpr);
            const element = new CollageElement(
                `element-${index}`,
                x, y, width, height, rotation,
                index
            );
            const demoImageIndex = index % loadedImages.length;
            element.image = loadedImages[demoImageIndex];
            window.collageElements.push(element);
        });
    }

    window.drawCanvas = function() {
        window.ctx.clearRect(0, 0, window.collageCanvas.width, window.collageCanvas.height);

        window.collageElements.forEach((element) => {
            const { x, y, width, height, rotation, image } = element;

            if (image) {
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

            // Draw selection border for ALL selected elements
            if (element.isSelected) { // <--- KORREKTUR: Selection border für alle isSelected Elemente
                window.ctx.strokeStyle = SELECTION_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.strokeRect(x, y, width, height);
            } else {
                // Only draw default border if not selected
                window.ctx.strokeStyle = BORDER_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.strokeRect(x, y, width, height);
            }

            // --- Draw Resizing Handles and Rotation Handle ONLY FOR THE ACTIVE ELEMENT ---
            if (element === window.activeElement) { // <--- KORREKTUR: Handles nur für activeElement
                const handles = [
                    { x: x,             y: y,              cursor: 'nwse-resize', name: 'top-left' },
                    { x: x + width,     y: y,              cursor: 'nesw-resize', name: 'top-right' },
                    { x: x,             y: y + height,     cursor: 'nesw-resize', name: 'bottom-left' },
                    { x: x + width,     y: y + height,     cursor: 'nwse-resize', name: 'bottom-right' }
                ];
                handles.forEach(handle => {
                    window.ctx.fillStyle = HANDLE_COLOR;
                    window.ctx.strokeStyle = HANDLE_STROKE_COLOR;
                    window.ctx.lineWidth = HANDLE_BORDER_WIDTH;
                    window.ctx.fillRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
                    window.ctx.strokeRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
                });

                const rotationHandleX = x + width / 2;
                const rotationHandleY = y - ROTATION_HANDLE_OFFSET;
                window.ctx.beginPath();
                window.ctx.arc(rotationHandleX, rotationHandleY, ROTATION_HANDLE_SIZE / 2, 0, Math.PI * 2);
                window.ctx.fillStyle = ROTATION_HANDLE_COLOR;
                window.ctx.fill();
                window.ctx.strokeStyle = ROTATION_HANDLE_STROKE_COLOR;
                window.ctx.lineWidth = HANDLE_BORDER_WIDTH;
                window.ctx.stroke();
                window.ctx.fillStyle = ROTATION_HANDLE_STROKE_COLOR;
                window.ctx.font = ROTATION_HANDLE_ICON_FONT_SIZE;
                window.ctx.textAlign = 'center';
                window.ctx.textBaseline = 'middle';
                window.ctx.fillText(ROTATION_HANDLE_ICON, rotationHandleX, rotationHandleY);
            }
        });
    };

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

        // Reset interaction flags
        isResizing = false;
        isRotating = false;
        isDragging = false;

        // Save current active element before potentially changing it
        const prevActiveElement = window.activeElement;

        // --- Check for Rotation Handle hit FIRST ---
        if (prevActiveElement && prevActiveElement.isSelected) { // Check if it's selected and active
            const rotationHandleX = prevActiveElement.x + prevActiveElement.width / 2;
            const rotationHandleY = prevActiveElement.y - ROTATION_HANDLE_OFFSET;
            const dist = Math.sqrt(
                Math.pow(mouse.x - rotationHandleX, 2) +
                Math.pow(mouse.y - rotationHandleY, 2)
            );
            if (dist <= ROTATION_HANDLE_SIZE / 2) {
                isRotating = true;
                const elementCenterX = prevActiveElement.x + prevActiveElement.width / 2;
                const elementCenterY = prevActiveElement.y + prevActiveElement.height / 2;
                rotationStartAngle = Math.atan2(mouse.y - elementCenterY, mouse.x - elementCenterX);
                initialElementRotation = prevActiveElement.rotation;
                window.collageCanvas.style.cursor = ROTATION_CURSOR_URL;
                window.drawCanvas();
                return; // Rotation handle clicked, don't proceed further
            }
        }

        // Check for handle hit SECOND (Resizing Handles)
        if (prevActiveElement && prevActiveElement.isSelected) { // Check if it's selected and active
            const handles = [
                { x: prevActiveElement.x, y: prevActiveElement.y, name: 'top-left' },
                { x: prevActiveElement.x + prevActiveElement.width, y: prevActiveElement.y, name: 'top-right' },
                { x: prevActiveElement.x, y: prevActiveElement.y + prevActiveElement.height, name: 'bottom-left' },
                { x: prevActiveElement.x + prevActiveElement.width, y: prevActiveElement.y + prevActiveElement.height, name: 'bottom-right' }
            ];

            for (const handle of handles) {
                if (mouse.x >= handle.x - HANDLE_SIZE / 2 && mouse.x <= handle.x + HANDLE_SIZE / 2 &&
                    mouse.y >= handle.y - HANDLE_SIZE / 2 && mouse.y <= handle.y + HANDLE_SIZE / 2) {
                    
                    isResizing = true;
                    resizeHandle = handle.name;
                    dragStartX = mouse.x;
                    dragStartY = mouse.y;
                    initialElementWidth = prevActiveElement.width;
                    initialElementHeight = prevActiveElement.height;
                    initialElementX = prevActiveElement.x;
                    initialElementY = prevActiveElement.y;

                    switch(resizeHandle) {
                        case 'top-left': case 'bottom-right': window.collageCanvas.style.cursor = 'nwse-resize'; break;
                        case 'top-right': case 'bottom-left': window.collageCanvas.style.cursor = 'nesw-resize'; break;
                    }
                    window.drawCanvas();
                    return; // Handle clicked, don't proceed to drag logic
                }
            }
        }

        // --- Handle Clicks on Elements for Selection/Dragging ---
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
                if (window.activeElement !== elementClicked) { // Only deselect if a different element is clicked
                    window.collageElements.forEach(el => el.isSelected = false);
                }
                elementClicked.isSelected = true;
                window.activeElement = elementClicked; // The clicked element becomes the active one
            }
            isDragging = true;
            dragStartX = mouse.x;
            dragStartY = mouse.y;
            elementStartX = window.activeElement.x;
            elementStartY = window.activeElement.y;
            window.collageCanvas.style.cursor = 'grabbing';
        } else {
            // No element was clicked
            if (!event.ctrlKey && !event.metaKey) { // If no multi-selection key, deselect all
                window.collageElements.forEach(el => el.isSelected = false);
                window.activeElement = null;
            }
        }
        window.drawCanvas(); // Redraw to reflect selection/deselection and active element handles
    }

    function handleMouseMove(event) {
        const mouse = getMousePos(event);

        // --- Cursor hover for handles ---
        let cursorChanged = false;
        if (!isDragging && !isResizing && !isRotating) { // Only change cursor if not interacting
            // Check rotation handle hover
            if (window.activeElement && window.activeElement.isSelected) { // Check active element for handles
                const rotationHandleX = window.activeElement.x + window.activeElement.width / 2;
                const rotationHandleY = window.activeElement.y - ROTATION_HANDLE_OFFSET;
                const dist = Math.sqrt(
                    Math.pow(mouse.x - rotationHandleX, 2) +
                    Math.pow(mouse.y - rotationHandleY, 2)
                );
                if (dist <= ROTATION_HANDLE_SIZE / 2) {
                    window.collageCanvas.style.cursor = ROTATION_CURSOR_URL;
                    cursorChanged = true;
                }
            }

            // Check resize handles hover
            if (window.activeElement && window.activeElement.isSelected && !cursorChanged) { // Check active element for handles
                const handles = [
                    { x: window.activeElement.x,               y: window.activeElement.y,                 cursor: 'nwse-resize', name: 'top-left' },
                    { x: window.activeElement.x + window.activeElement.width, y: window.activeElement.y,         cursor: 'nesw-resize', name: 'top-right' },
                    { x: window.activeElement.x,               y: window.activeElement.y + window.activeElement.height,  cursor: 'nesw-resize', name: 'bottom-left' },
                    { x: window.activeElement.x + window.activeElement.width, y: window.activeElement.y + window.activeElement.height, cursor: 'nwse-resize', name: 'bottom-right' }
                ];
                for (const handle of handles) {
                    if (mouse.x >= handle.x - HANDLE_SIZE / 2 && mouse.x <= handle.x + HANDLE_SIZE / 2 &&
                        mouse.y >= handle.y - HANDLE_SIZE / 2 && mouse.y <= handle.y + HANDLE_SIZE / 2) {
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

        // --- Rotation Logic ---
        if (isRotating && window.activeElement) {
            const elementCenterX = window.activeElement.x + window.activeElement.width / 2;
            const elementCenterY = window.activeElement.y + window.activeElement.height / 2;
            const currentAngle = Math.atan2(mouse.y - elementCenterY, mouse.x - elementCenterX);
            let angleDiff = (rotationStartAngle - currentAngle) * 180 / Math.PI;
            let newRotation = initialElementRotation + angleDiff;
            if (event.shiftKey) {
                newRotation = Math.round(newRotation / 15) * 15;
            }
            window.activeElement.rotation = (newRotation % 360 + 360) % 360; 
            window.drawCanvas();
            return;
        }

        // --- Scaling Logic ---
        if (isResizing && window.activeElement) {
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
                    if (event.shiftKey) { 
                        if (Math.abs(dx) > Math.abs(dy)) { newHeight = newWidth / aspectRatio; } else { newWidth = newHeight * aspectRatio; }
                    }
                    newX = initialElementX + (initialElementWidth - newWidth);
                    newY = initialElementY + (initialElementHeight - newHeight);
                    break;
                case 'top-right':
                    newWidth = initialElementWidth + dx;
                    newHeight = initialElementHeight - dy;
                    if (event.shiftKey) {
                        if (Math.abs(dx) > Math.abs(dy)) { newHeight = newWidth / aspectRatio; } else { newWidth = newHeight * aspectRatio; }
                    }
                    newY = initialElementY + (initialElementHeight - newHeight);
                    break;
                case 'bottom-left':
                    newWidth = initialElementWidth - dx;
                    newHeight = initialElementHeight + dy;
                    if (event.shiftKey) {
                        if (Math.abs(dx) > Math.abs(dy)) { newHeight = newWidth / aspectRatio; } else { newWidth = newHeight * aspectRatio; }
                    }
                    newX = initialElementX + (initialElementWidth - newWidth);
                    break;
                case 'bottom-right':
                    newWidth = initialElementWidth + dx;
                    newHeight = initialElementHeight + dy;
                    if (event.shiftKey) {
                        if (Math.abs(dx) > Math.abs(dy)) { newHeight = newWidth / aspectRatio; } else { newWidth = newHeight * aspectRatio; }
                    }
                    break;
            }

            const MIN_SIZE = 20;
            if (newWidth < MIN_SIZE) {
                newWidth = MIN_SIZE;
                if (event.shiftKey) newHeight = newWidth / aspectRatio;
                if (resizeHandle.includes('left')) newX = initialElementX + (initialElementWidth - newWidth);
            }
            if (newHeight < MIN_SIZE) {
                newHeight = MIN_SIZE;
                if (event.shiftKey) newWidth = newHeight * aspectRatio;
                if (resizeHandle.includes('top')) newY = initialElementY + (initialElementHeight - newHeight);
            }

            window.activeElement.x = newX;
            window.activeElement.y = newY;
            window.activeElement.width = newWidth;
            window.activeElement.height = newHeight;

            window.drawCanvas();
            return;
        }

        // --- Dragging Logic ---
        if (isDragging && window.activeElement) {
            const dx = mouse.x - dragStartX;
            const dy = mouse.y - dragStartY;

            window.activeElement.x = elementStartX + dx;
            window.activeElement.y = elementStartY + dy;

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
        window.drawCanvas();
    }

    async function initDesigner() {
        setupCanvasDimensions();
        await loadDemoImages();
        updateCollageElements();
        window.drawCanvas();
    }

    // --- Event Listeners ---
    window.collageCanvas.addEventListener('mousedown', handleMouseDown);
    window.collageCanvas.addEventListener('mousemove', handleMouseMove);
    window.collageCanvas.addEventListener('mouseup', handleMouseUp);
    window.collageCanvas.addEventListener('mouseout', handleMouseUp); // End interaction if mouse leaves canvas

    initDesigner();
});