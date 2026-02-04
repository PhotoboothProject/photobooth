// admin/collage-designer/assets/js/collage-designer-mouseEvents.js

document.addEventListener('DOMContentLoaded', () => {
    //=================================================================================
    // --- Local State Variables for Mouse Events ---
    //=================================================================================
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
    window.getLocalMouseCoordinates = function(globalX, globalY, element) {
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
    window.isPointInRect = function(pointX, pointY, rectX, rectY, rectWidth, rectHeight) {
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
                    const boundingBox = window.getSelectionBoundingBox();
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
                    } else { // Fallback for group resize (will be set from window.getSelectionBoundingBox in handleMouseMove)
                        const groupBoundingBox = window.getSelectionBoundingBox();
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
                const groupBoundingBox = window.getSelectionBoundingBox();
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
                const groupBoundingBox = window.getSelectionBoundingBox();
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
                        window.collageCanvas.style.cursor = window.DELETE_CURSOR_URL;
                        cursorChanged = true;
                    }
                }

                // 2. Check rotate handle (ONLY FOR SINGLE ACTIVE ELEMENT)
                if (!cursorChanged && selectedElementsCount === 1 && window.currentHandles.rotate) { // Only check if no other cursor changed
                    if (isPointInCircle(mouseForHandleHitX, mouseForHandleHitY,
                                    window.currentHandles.rotate.handleLocalX, window.currentHandles.rotate.handleLocalY,
                                    window.currentHandles.rotate.radius)) {
                        window.collageCanvas.style.cursor = window.ROTATION_CURSOR_URL;
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
    // --- Event Listeners ---
    //=================================================================================
    window.collageCanvas.addEventListener('mousedown', handleMouseDown);
    window.collageCanvas.addEventListener('mousemove', handleMouseMove);
    window.collageCanvas.addEventListener('mouseup', handleMouseUp);
    window.collageCanvas.addEventListener('mouseout', handleMouseUp); // End interaction if mouse leaves canvas
});