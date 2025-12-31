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

    // --- Undo/Redo History ---
    let undoStack = [];
    let redoStack = [];
    const MAX_HISTORY_SIZE = 50; // Limit the history to prevent excessive memory usage

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

    // --- Undo/Redo Functions ---

    /**
     * Creates a snapshot of the current state of all collage elements.
     * Only stores properties that can change (x, y, width, height, rotation, isSelected).
     * @returns {Array<object>} A deep copy of the relevant element states.
     */
    function createSnapshot() {
        return window.collageElements.map(el => ({
            id: el.id, // Keep ID for matching
            x: el.x,
            y: el.y,
            width: el.width,
            height: el.height,
            rotation: el.rotation,
            isSelected: el.isSelected,
            // image and originalLayoutDataIndex do not change, no need to store
        }));
    }

    /**
     * Restores the state of collage elements from a given snapshot.
     * @param {Array<object>} snapshot The snapshot to restore.
     */
    function restoreSnapshot(snapshot) {
        // Clear current selection
        window.collageElements.forEach(el => el.isSelected = false);
        window.activeElement = null;

        snapshot.forEach(snapEl => {
            const currentEl = window.collageElements.find(el => el.id === snapEl.id);
            if (currentEl) {
                currentEl.x = snapEl.x;
                currentEl.y = snapEl.y;
                currentEl.width = snapEl.width;
                currentEl.height = snapEl.height;
                currentEl.rotation = snapEl.rotation;
                currentEl.isSelected = snapEl.isSelected; // Restore selection state too
                if (snapEl.isSelected) { // If an element was selected in the snapshot, make it active if it's the only one
                    if (snapshot.filter(s => s.isSelected).length === 1) {
                         window.activeElement = currentEl;
                    } else if (window.activeElement && window.activeElement.id === currentEl.id) {
                        // If multiple selected, try to restore the active element
                        window.activeElement = currentEl;
                    }
                }
            }
        });
    }

    /**
     * Saves the current state to the undoStack and clears the redoStack.
     */
    window.saveState = function() {
        const currentState = createSnapshot();
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

    /**
     * Updates the enabled/disabled state of the Undo/Redo buttons.
     */
    window.updateUndoRedoButtonStates = function() {
        const undoBtn = document.getElementById('undoBtn');
        const redoBtn = document.getElementById('redoBtn');

        if (undoBtn) undoBtn.disabled = undoStack.length <= 1; // Always need at least 1 state to undo from
        if (redoBtn) redoBtn.disabled = redoStack.length === 0;
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
            if (element.isSelected) { 
                window.ctx.strokeStyle = SELECTION_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.strokeRect(x, y, width, height);
            } else {
                // Only draw default border if not selected
                window.ctx.strokeStyle = BORDER_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.strokeRect(x, y, width, height);
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
                window.ctx.fillStyle = HANDLE_COLOR;
                window.ctx.strokeStyle = HANDLE_STROKE_COLOR;
                window.ctx.lineWidth = HANDLE_BORDER_WIDTH;
                window.ctx.fillRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
                window.ctx.strokeRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
            });
        }
        
        // Draw Rotation Handle ONLY FOR THE ACTIVE ELEMENT
        if (window.activeElement && window.activeElement.isSelected) { // Check if it's selected and active
            const rotationHandleX = window.activeElement.x + window.activeElement.width / 2;
            const rotationHandleY = window.activeElement.y - ROTATION_HANDLE_OFFSET;
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

        const prevSelectedState = createSnapshot(); // create Snapshot before Snapshot for possible selection changes

        // Reset interaction flags
        isResizing = false;
        isRotating = false;
        isDragging = false;

        const selectedElementsCount = window.collageElements.filter(el => el.isSelected).length;

        let currentInteractionTarget = null; // The object or bounding box currently being interacted with via handles
        let currentTargetX = 0, currentTargetY = 0, currentTargetWidth = 0, currentTargetHeight = 0;

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
            const rotationHandleY = window.activeElement.y - ROTATION_HANDLE_OFFSET;
            const dist = Math.sqrt(
                Math.pow(mouse.x - rotationHandleX, 2) +
                Math.pow(mouse.y - rotationHandleY, 2)
            );

            if (dist <= ROTATION_HANDLE_SIZE / 2) {
                isRotating = true;
                const elementCenterX = window.activeElement.x + window.activeElement.width / 2;
                const elementCenterY = window.activeElement.y + window.activeElement.height / 2;
                rotationStartAngle = Math.atan2(mouse.y - elementCenterY, mouse.x - elementCenterX);
                initialElementRotation = window.activeElement.rotation; // Store active element's rotation as start reference
                window.collageCanvas.style.cursor = ROTATION_CURSOR_URL;
                window.drawCanvas();
                return; // Rotation handle clicked, don't proceed further
            }
        }

        // --- Check for Resize Handle hit SECOND ---
        // Handles are on activeElement (if single selected) or group bounding box (if multiple selected)
        if (currentInteractionTarget && currentTargetWidth > 0 && currentTargetHeight > 0) { // Check for valid dimensions to prevent errors
            const handles = [
                { x: currentTargetX,               y: currentTargetY,                 name: 'top-left' },
                { x: currentTargetX + currentTargetWidth, y: currentTargetY,                 name: 'top-right' },
                { x: currentTargetX,               y: currentTargetY + currentTargetHeight,  name: 'bottom-left' },
                { x: currentTargetX + currentTargetWidth, y: currentTargetY + currentTargetHeight, name: 'bottom-right' }
            ];

            for (const handle of handles) {
                if (mouse.x >= handle.x - HANDLE_SIZE / 2 && mouse.x <= handle.x + HANDLE_SIZE / 2 &&
                    mouse.y >= handle.y - HANDLE_SIZE / 2 && mouse.y <= handle.y + HANDLE_SIZE / 2) {
                    
                    isResizing = true;
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
                    window.drawCanvas();
                    return; // Handle clicked, don't proceed to drag logic
                }
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
            
            isDragging = true;
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
            window.collageCanvas.style.cursor = 'grabbing';
        } else {
            // No element was clicked
            if (!event.ctrlKey && !event.metaKey) {
                window.collageElements.forEach(el => el.isSelected = false);
                window.activeElement = null;
            }
        }

        // Save state if there was any change in selection
        if (isRotating || isResizing || isDragging || JSON.stringify(prevSelectedState) !== JSON.stringify(createSnapshot())) {
         window.saveState(); 
        }
        window.drawCanvas();
    }

    function handleMouseMove(event) {
        const mouse = getMousePos(event);

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

            // Check resize handles hover (on currentTargetForHover if it exists and not already hovering rotation handle)
            if (currentTargetForHover && !cursorChanged && currentTargetForHover.width > 0 && currentTargetForHover.height > 0) { 
                const handles = [
                    { x: currentTargetX,               y: currentTargetY,                 cursor: 'nwse-resize', name: 'top-left' },
                    { x: currentTargetX + currentTargetWidth, y: currentTargetY,         cursor: 'nesw-resize', name: 'top-right' },
                    { x: currentTargetX,               y: currentTargetY + currentTargetHeight,  cursor: 'nesw-resize', name: 'bottom-left' },
                    { x: currentTargetX + currentTargetWidth, y: currentTargetY + currentTargetHeight, cursor: 'nwse-resize', name: 'bottom-right' }
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
            }
            
            selected.forEach(element => {
                element.rotation = (element.rotation + angleDiff % 360 + 360) % 360; 
            });

            // Update rotationStartAngle for the next mousemove step
            rotationStartAngle = currentAngle; 

            window.drawCanvas();
            return;
        }

        // --- Scaling Logic (UNIFIED for single and group, with correct Shift behavior) ---
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

            const mouseCurrent = mouse; // current mouse position
            const mouseStart = { x: dragStartX, y: dragStartY }; // mouse position at the start of resize

            const initialAspectRatio = initialBoundingBox.width / initialBoundingBox.height;

            // anchorpoint (opposite corner of the handle)
            let anchorX, anchorY;
            switch (resizeHandle) {
                case 'top-left':     anchorX = initialBoundingBox.x + initialBoundingBox.width;  anchorY = initialBoundingBox.y + initialBoundingBox.height; break;
                case 'top-right':    anchorX = initialBoundingBox.x;                             anchorY = initialBoundingBox.y + initialBoundingBox.height; break;
                case 'bottom-left':  anchorX = initialBoundingBox.x + initialBoundingBox.width;  anchorY = initialBoundingBox.y;                             break;
                case 'bottom-right': anchorX = initialBoundingBox.x;                             anchorY = initialBoundingBox.y;                             break;
            }

            let finalWidth, finalHeight, finalX, finalY;

            //if (event.shiftKey) { //not working properly for unproportional resize yet
                // scale proportionally from the anchor point, based on the dominant axis.

                // Current distance of the mouse from the anchor point
                const currentRelX = mouseCurrent.x - anchorX;
                const currentRelY = mouseCurrent.y - anchorY;

                // Original distance of the mouse from the anchor point (as reference for Delta)
                const startRelX = mouseStart.x - anchorX;
                const startRelY = mouseStart.y - anchorY;

                // Calculation of the "Delta" change in X and Y relative to the anchor point
                // This reflects the movement of the handle
                const deltaMovementX = currentRelX - startRelX;
                const deltaMovementY = currentRelY - startRelY;

                // Calculate the new width/height, if it were scaled proportionally from the start
                // We use initialBoundingBox to maintain the original aspect ratio
                let potentialNewWidth = initialBoundingBox.width + (resizeHandle.includes('left') ? -deltaMovementX : deltaMovementX);
                let potentialNewHeight = initialBoundingBox.height + (resizeHandle.includes('top') ? -deltaMovementY : deltaMovementY);

                // Determine the effective scaling factor based on the dominant axis
                // (the axis, which was scaled proportionally the furthest)
                let scaleFactorFromWidth = potentialNewWidth / initialBoundingBox.width;
                let scaleFactorFromHeight = potentialNewHeight / initialBoundingBox.height;

                let effectiveScaleFactor;
                if (Math.abs(scaleFactorFromWidth) > Math.abs(scaleFactorFromHeight)) {
                    effectiveScaleFactor = scaleFactorFromWidth;
                } else {
                    effectiveScaleFactor = scaleFactorFromHeight;
                }

                // Apply the effective scaling factor to the original dimensions
                finalWidth = initialBoundingBox.width * effectiveScaleFactor;
                finalHeight = initialBoundingBox.height * effectiveScaleFactor;

                // Calculate the final position based on anchor point and new proportional dimensions
                finalX = anchorX;
                finalY = anchorY;

                if (resizeHandle.includes('left')) finalX = anchorX - finalWidth;
                if (resizeHandle.includes('top')) finalY = anchorY - finalHeight;

            /*} else {
                // no shift: scale unproportional.
                // The dx/dy values are the total mouse movement since the start of the click.
                // finalWidth/Height are directly calculated from initialBoundingBox + dx/dy.
                finalWidth = initialBoundingBox.width + (resizeHandle.includes('left') ? -dx : dx);
                finalHeight = initialBoundingBox.height + (resizeHandle.includes('top') ? -dy : dy);

                // finalX/Y are directly calculated from initialBoundingBox + dx/dy.
                finalX = initialBoundingBox.x;
                finalY = initialBoundingBox.y;

                if (resizeHandle.includes('left')) finalX = initialBoundingBox.x + dx;
                if (resizeHandle.includes('top')) finalY = initialBoundingBox.y + dy;
            }*/

            // --- apply minimum size restriction ---
            const MIN_SIZE = 20;

            if (finalWidth < MIN_SIZE) {
                finalWidth = MIN_SIZE;
                if (event.shiftKey) finalHeight = MIN_SIZE / initialAspectRatio; // while shift: height proportional adjust
            }
            if (finalHeight < MIN_SIZE) {
                finalHeight = MIN_SIZE;
                if (event.shiftKey) finalWidth = MIN_SIZE * initialAspectRatio; // while shift: width proportional adjust
            }

            // position after resizing, to avoid jumps
            if (resizeHandle.includes('left') && finalWidth === MIN_SIZE && initialBoundingBox.width > MIN_SIZE) {
                finalX = anchorX - MIN_SIZE;
            }
            if (resizeHandle.includes('top') && finalHeight === MIN_SIZE && initialBoundingBox.height > MIN_SIZE) {
                finalY = anchorY - MIN_SIZE;
            }

            // to avoid errors, if finalWidth/Height become 0 (shouldn't happen due to MIN_SIZE)
            if (finalWidth === 0) finalWidth = 1;
            if (finalHeight === 0) finalHeight = 1;


            // scale factors from initial bounding box to final bounding box
            const scaleX = finalWidth / initialBoundingBox.width;
            const scaleY = finalHeight / initialBoundingBox.height;

            // apply transformation on each selected element
            selectedElements.forEach(element => {
                // position of the element relative to the anchor point of the initial bounding box
                // This is crucial for the "sticky" behavior
                const relativeXToAnchor = element.x - anchorX;
                const relativeYToAnchor = element.y - anchorY;

                // Scale relative position
                const newRelativeXToAnchor = relativeXToAnchor * scaleX;
                const newRelativeYToAnchor = relativeYToAnchor * scaleY;

                // new Position and Dimensions of the element
                element.x = anchorX + newRelativeXToAnchor;
                element.y = anchorY + newRelativeYToAnchor;
                element.width = element.width * scaleX;
                element.height = element.height * scaleY;
            });

            // dragStartX/Y for continuous resizing update
            dragStartX = mouseCurrent.x;
            dragStartY = mouseCurrent.y;

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

            // dragStartX/Y for continuous dragging without accumulation update
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

    // Undo/Redo Buttons
    document.getElementById('undoBtn').addEventListener('click', () => {
        if (undoStack.length > 1) { // Need at least the initial state and one action to undo
            const currentState = undoStack.pop(); // Remove current state from undo stack
            redoStack.push(currentState); // Push it to redo stack
            restoreSnapshot(undoStack[undoStack.length - 1]); // Load the previous state
            window.drawCanvas();
            updateUndoRedoButtonStates();
        }
    });

    document.getElementById('redoBtn').addEventListener('click', () => {
        if (redoStack.length > 0) {
            const nextState = redoStack.pop(); // Get next state from redo stack
            undoStack.push(nextState); // Push it back to undo stack
            restoreSnapshot(nextState); // Load this state
            window.drawCanvas();
            window.updateUndoRedoButtonStates();
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

    // Initialize the designer and save the very first state
    initDesigner().then(() => {
        window.saveState(); // Save initial state after everything is loaded
        window.updateUndoRedoButtonStates(); // Update button states based on initial stack
    });
});