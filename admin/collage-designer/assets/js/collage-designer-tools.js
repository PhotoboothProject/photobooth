// admin/collage-designer/assets/js/collage-designer-tools.js

document.addEventListener('DOMContentLoaded', () => {
    // Check if main designer variables/functions are available
    if (typeof window.collageCanvas === 'undefined' || typeof window.drawCanvas === 'undefined' ||
        typeof window.collageElements === 'undefined' || typeof window.activeElement === 'undefined' ||
        typeof window.CollageElement === 'undefined' || typeof window.createSnapshot === 'undefined' ||
        typeof window.restoreSnapshot === 'undefined' || typeof window.saveState === 'undefined' ||
        typeof window.phpFallbackImageUrl === 'undefined' || typeof window.fetchDemoImageUrls === 'undefined'
    ) {
        console.error('collage-designer-tools.js: Dependent main designer variables/functions not found. Ensure collage-designer.js is loaded first and exposes necessary variables globally.');
        return;
    }

    //=================================================================================
    // --- Helper Functions ---
    //=================================================================================

    /**
     * Gets currently selected elements from all known element arrays.
     * @returns {Array<CollageElement>} An array of selected elements.
     */
    function getSelectedElements() {
        const selected = [];
        // Add elements from window.collageElements (your image boxes)
        if (window.collageElements) {
            window.collageElements.forEach(el => {
                if (el.isSelected) selected.push(el);
            });
        }
        // Add elements from other global arrays if they exist and are selected
        if (window.textFields) { // Assuming textFields have an isSelected property
            window.textFields.forEach(tf => {
                if (tf.isSelected) selected.push(tf);
            });
        }
        if (window.imagePlaceholders) { // Assuming imagePlaceholders (if different from collageElements) have an isSelected property
            window.imagePlaceholders.forEach(ip => {
                if (ip.isSelected) selected.push(ip);
            });
        }
        return selected;
    }

    /**
     * Gets the canvas dimensions.
     * @returns {{width: number, height: number}}
     */
    function getCanvasDimensions() {
        return {
            width: window.collageCanvas.width,
            height: window.collageCanvas.height
        };
    }

    //=================================================================================
    // --- Element Management Functions ---
    //=================================================================================

    /**
     * Adds a new placeholder element to the canvas.
     * @param {number} x Optional x-coordinate. Defaults to center of canvas if not provided.
     * @param {number} y Optional y-coordinate. Defaults to center of canvas if not provided.
     * @param {number} width Optional width. Defaults to a standard size.
     * @param {number} height Optional height. Defaults to a standard size.
     * @param {number} rotation Optional rotation. Defaults to 0.
     * @param {string} id Optional ID. If not provided, a new unique ID is generated.
     * @returns {CollageElement} The newly created element.
     */
    window.addNewElement = async function(x, y, width, height, rotation = 0, id) {
        // Save current state BEFORE adding the element for Undo
        window.saveState(); 

        const canvasWidth = window.collageCanvas.width;
        const canvasHeight = window.collageCanvas.height;

        const defaultWidth = canvasWidth / 2;
        const defaultHeight = canvasHeight / 2;
        // small Offset, so new Elements are stacked when multiple are added
        const offset = window.collageElements.filter(el => el.originalLayoutDataIndex === -1).length * 10; 

        // Calculate default position: centered, but slightly offset if id is generated (for stacking duplicates)
        let finalX = x !== undefined ? x : (canvasWidth / 2) - (defaultWidth / 2) + offset;
        let finalY = y !== undefined ? y : (canvasHeight / 2) - (defaultHeight / 2) + offset;

        // Generate unique ID if not provided
        const newId = id || `element-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        
         let imageUrl = null;
        try {
            // call global function from collage-designer.js
            const fetchedUrls = await window.fetchDemoImageUrls(1);
            imageUrl = fetchedUrls[0];
        } catch (error) {
            console.error('Could not fetch demo image for new element, using fallback.', error);
            imageUrl = window.phpFallbackImageUrl; // Globalen Fallback nutzen
        }

        const img = new Image();
        img.crossOrigin = "anonymous";
        img.src = imageUrl;

        // Create new CollageElement instance
        const newElement = new CollageElement(
            newId,
            finalX,
            finalY,
            width !== undefined ? width : defaultWidth,
            height !== undefined ? height : defaultHeight,
            rotation,
            -1, // -1 indicates it's not from originalLayoutData (a placeholder)
            img
        );

        // Add it to our global collection
        window.collageElements.push(newElement);

        // Deselect all other elements and select the new one
        window.collageElements.forEach(el => el.isSelected = false);
        newElement.isSelected = true;
        window.activeElement = newElement;

        // Redraw canvas to show the new element
        window.drawCanvas();
        
        // When the image loads, redraw the canvas to display it and save the final state
        img.onload = () => {
            window.drawCanvas(); // Redraw once the image is ready
            window.saveState(); // Save state again after image has loaded to reflect its presence
        };
        img.onerror = () => {
            console.error(`Failed to load image for element ${newId}: ${imageUrl}`);
            window.drawCanvas(); // Redraw to show text placeholder if image failed
            window.saveState(); // Save state even if image failed to load
        };
        window.saveState(); // Save state after adding the element
        
        return newElement;
    };

    /**
     * removes all selected Collage-Elemente.
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

    //=================================================================================
    // --- Alignment Functions ---
    //=================================================================================

    // General alignment logic function to avoid repetition
    function applyAlignment(property, targetValueFn, useActiveElementAsReference = false) {
        const canvas = getCanvasDimensions();
        let elementsToAlign = getSelectedElements();

        if (elementsToAlign.length === 0) {
            console.log(`No elements selected for ${property} alignment.`);
            return;
        }

        let referenceValue;
        if (elementsToAlign.length === 1 || !useActiveElementAsReference) {
            // Single element or multi-selection without specific active element reference: align to canvas
            referenceValue = targetValueFn(canvas, elementsToAlign);
        } else {
            // Multiple elements selected AND activeElement exists: align to activeElement
            if (!window.activeElement || !window.activeElement.isSelected) {
                console.log('Multiple elements selected, but no valid active element to use as reference for alignment.');
                return;
            }
            referenceValue = targetValueFn(window.activeElement, elementsToAlign);
        }
        
        // Apply alignment
        elementsToAlign.forEach(element => {
            // This assumes `property` is 'x' or 'y' and `width` or `height` are available on element.
            // Adjust `element[property] = ...` based on specific alignment logic.
            // For example, for 'alignLeft', `element.x = referenceValue`.
            // For 'alignCenterH', `element.x = referenceValue - element.width / 2`.
            // The `targetValueFn` should return the target X or Y coordinate based on the reference.
            element[property] = referenceValue - (property === 'x' ? element.width / 2 : element.height / 2); // Default center adjustment
            if (property === 'x' && elementsToAlign.length > 1 && !useActiveElementAsReference) {
                // If aligning multiple elements to the leftmost of the group, referenceValue is the leftmost X.
                // Each element's X should then be set to this reference X.
                element[property] = referenceValue; 
            }
            if (property === 'y' && elementsToAlign.length > 1 && !useActiveElementAsReference) {
                // Similar for vertical alignment to topmost of the group.
                element[property] = referenceValue;
            }
        });

        window.drawCanvas(); // Redraw after changing position
    }

    // --- Horizontal Alignment ---

    // Align selected elements to the left edge (Canvas / Leftmost of Group / Active Element's left)
    document.getElementById('alignLeftBtn').addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const elementsToAlign = getSelectedElements();
        if (elementsToAlign.length === 0) {
            console.log('No elements selected for left alignment.');
            return;
        }

        let targetX;
        if (elementsToAlign.length === 1) {
            // Align single element to canvas left
            targetX = 0;
        } else {
            // Multiple elements: align to active element's left, or leftmost of group
            if (window.activeElement && window.activeElement.isSelected) {
                // Align to active element's left edge
                targetX = window.activeElement.x;
            } else {
                // Align to the leftmost edge among selected elements
                targetX = Math.min(...elementsToAlign.map(el => el.x));
            }
        }
        elementsToAlign.forEach(element => {
            element.x = targetX;
        });
        window.drawCanvas();
    });

    // Align selected elements to the horizontal center (Canvas / Active Element's center)
    document.getElementById('alignCenterHBtn').addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const elementsToAlign = getSelectedElements();
        if (elementsToAlign.length === 0) {
            console.log('No elements selected for horizontal center alignment.');
            return;
        }

        let targetCenterX;
        if (elementsToAlign.length === 1) {
            // Align single element to canvas horizontal center
            targetCenterX = getCanvasDimensions().width / 2;
        } else {
            // Multiple elements: align to active element's horizontal center
            if (window.activeElement && window.activeElement.isSelected) {
                targetCenterX = window.activeElement.x + window.activeElement.width / 2;
            } else {
                // Fallback: If no active element, align to the center of the bounding box of selected elements
                const minX = Math.min(...elementsToAlign.map(el => el.x));
                const maxX = Math.max(...elementsToAlign.map(el => el.x + el.width));
                targetCenterX = minX + (maxX - minX) / 2;
            }
        }
        elementsToAlign.forEach(element => {
            element.x = targetCenterX - element.width / 2;
        });
        window.drawCanvas();
    });

    // Align selected elements to the right edge (Canvas / Rightmost of Group / Active Element's right)
    document.getElementById('alignRightBtn').addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const elementsToAlign = getSelectedElements();
        if (elementsToAlign.length === 0) {
            console.log('No elements selected for right alignment.');
            return;
        }

        let targetRightX;
        if (elementsToAlign.length === 1) {
            // Align single element to canvas right
            targetRightX = getCanvasDimensions().width;
        } else {
            // Multiple elements: align to active element's right, or rightmost of group
            if (window.activeElement && window.activeElement.isSelected) {
                targetRightX = window.activeElement.x + window.activeElement.width;
            } else {
                // Align to the rightmost edge among selected elements
                targetRightX = Math.max(...elementsToAlign.map(el => el.x + el.width));
            }
        }
        elementsToAlign.forEach(element => {
            element.x = targetRightX - element.width;
        });
        window.drawCanvas();
    });

    // --- Vertical Alignment ---

    // Align selected elements to the top edge (Canvas / Topmost of Group / Active Element's top)
    document.getElementById('alignTopBtn').addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const elementsToAlign = getSelectedElements();
        if (elementsToAlign.length === 0) {
            console.log('No elements selected for top alignment.');
            return;
        }

        let targetY;
        if (elementsToAlign.length === 1) {
            // Align single element to canvas top
            targetY = 0;
        } else {
            // Multiple elements: align to active element's top, or topmost of group
            if (window.activeElement && window.activeElement.isSelected) {
                targetY = window.activeElement.y;
            } else {
                // Align to the topmost edge among selected elements
                targetY = Math.min(...elementsToAlign.map(el => el.y));
            }
        }
        elementsToAlign.forEach(element => {
            element.y = targetY;
        });
        window.drawCanvas();
    });

    // Align selected elements to the vertical middle (Canvas / Active Element's middle)
    document.getElementById('alignMiddleVBtn').addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const elementsToAlign = getSelectedElements();
        if (elementsToAlign.length === 0) {
            console.log('No elements selected for vertical middle alignment.');
            return;
        }

        let targetCenterY;
        if (elementsToAlign.length === 1) {
            // Align single element to canvas vertical middle
            targetCenterY = getCanvasDimensions().height / 2;
        } else {
            // Multiple elements: align to active element's vertical middle
            if (window.activeElement && window.activeElement.isSelected) {
                targetCenterY = window.activeElement.y + window.activeElement.height / 2;
            } else {
                // Fallback: If no active element, align to the center of the bounding box of selected elements
                const minY = Math.min(...elementsToAlign.map(el => el.y));
                const maxY = Math.max(...elementsToAlign.map(el => el.y + el.height));
                targetCenterY = minY + (maxY - minY) / 2;
            }
        }
        elementsToAlign.forEach(element => {
            element.y = targetCenterY - element.height / 2;
        });
        window.drawCanvas();
    });

    // Align selected elements to the bottom edge (Canvas / Bottommost of Group / Active Element's bottom)
    document.getElementById('alignBottomBtn').addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const elementsToAlign = getSelectedElements();
        if (elementsToAlign.length === 0) {
            console.log('No elements selected for bottom alignment.');
            return;
        }

        let targetBottomY;
        if (elementsToAlign.length === 1) {
            // Align single element to canvas bottom
            targetBottomY = getCanvasDimensions().height;
        } else {
            // Multiple elements: align to active element's bottom, or bottommost of group
            if (window.activeElement && window.activeElement.isSelected) {
                targetBottomY = window.activeElement.y + window.activeElement.height;
            } else {
                // Align to the bottommost edge among selected elements
                targetBottomY = Math.max(...elementsToAlign.map(el => el.y + el.height));
            }
        }
        elementsToAlign.forEach(element => {
            element.y = targetBottomY - element.height;
        });
        window.drawCanvas();
    });

    //=================================================================================
    // --- Distribution Functions ---
    //=================================================================================

    // Distribute selected elements horizontally
    document.getElementById('distributeHBtn').addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        let elementsToDistribute = getSelectedElements();

        if (elementsToDistribute.length < 3) {
            console.log('Select at least 3 elements for horizontal distribution.');
            return;
        }

        // Sort elements by their x-coordinate to ensure proper distribution order
        elementsToDistribute.sort((a, b) => a.x - b.x);

        const firstElement = elementsToDistribute[0];
        const lastElement = elementsToDistribute[elementsToDistribute.length - 1];

        // Determine the total width of all elements combined
        const totalElementsWidth = elementsToDistribute.reduce((sum, el) => sum + el.width, 0);

        // Determine the total available space between the first and last element's outer edges
        const availableSpace = (lastElement.x + lastElement.width) - firstElement.x;

        // Calculate the space to be distributed between elements
        // This is the total space minus the space occupied by the elements themselves
        const spaceBetweenElements = availableSpace - totalElementsWidth;

        // Calculate the actual gap size that needs to be inserted between each element
        // There are (n-1) gaps for n elements
        const numGaps = elementsToDistribute.length - 1;
        if (numGaps <= 0) { // Should not happen with length < 3 check, but for safety
             window.drawCanvas();
             return;
        }
        const uniformGap = spaceBetweenElements / numGaps;

        // Apply new positions
        let currentX = firstElement.x; // Start from the first element's x-position
        elementsToDistribute.forEach((element, index) => {
            if (index === 0) {
                // First element stays at its sorted position (x)
                element.x = firstElement.x;
            } else {
                // Position subsequent elements based on the previous element's width and the uniform gap
                currentX += elementsToDistribute[index - 1].width + uniformGap;
                element.x = currentX;
            }
        });

        window.drawCanvas();
    });

    // Distribute selected elements vertically
    document.getElementById('distributeVBtn').addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        let elementsToDistribute = getSelectedElements();

        if (elementsToDistribute.length < 3) {
            console.log('Select at least 3 elements for vertical distribution.');
            return;
        }

        // Sort elements by their y-coordinate
        elementsToDistribute.sort((a, b) => a.y - b.y);

        const firstElement = elementsToDistribute[0];
        const lastElement = elementsToDistribute[elementsToDistribute.length - 1];

        // Determine the total height of all elements combined
        const totalElementsHeight = elementsToDistribute.reduce((sum, el) => sum + el.height, 0);

        // Determine the total available space between the first and last element's outer edges
        const availableSpace = (lastElement.y + lastElement.height) - firstElement.y;

        // Calculate the space to be distributed between elements
        const spaceBetweenElements = availableSpace - totalElementsHeight;

        // Calculate the actual gap size
        const numGaps = elementsToDistribute.length - 1;
        if (numGaps <= 0) {
             window.drawCanvas();
             return;
        }
        const uniformGap = spaceBetweenElements / numGaps;

        // Apply new positions
        let currentY = firstElement.y; // Start from the first element's y-position
        elementsToDistribute.forEach((element, index) => {
            if (index === 0) {
                // First element stays at its sorted position (y)
                element.y = firstElement.y;
            } else {
                // Position subsequent elements based on the previous element's height and the uniform gap
                currentY += elementsToDistribute[index - 1].height + uniformGap;
                element.y = currentY;
            }
        });

        window.drawCanvas();
    });

    //=================================================================================
    // --- z-order functions ---
    //=================================================================================

     /**
     * Moves selected elements within the window.collageElements array to change their Z-order.
     *
     * @param {string} direction 'front', 'back', 'forward', 'backward'
     */
    window.changeZOrder = function(direction) {
        const selectedElements = window.collageElements.filter(el => el.isSelected);
        if (selectedElements.length === 0) {
            return; // Nothing selected to move
        }

        window.saveState(); // Save state before modifying Z-order

        // Create a copy of the current elements array
        let currentElements = [...window.collageElements];

        // Filter out selected elements from their current positions
        const nonSelectedElements = currentElements.filter(el => !selectedElements.includes(el));

        if (direction === 'front') {
            // All selected elements to the very end of the array
            window.collageElements = [...nonSelectedElements, ...selectedElements];
        } else if (direction === 'back') {
            // All selected elements to the very beginning of the array
            window.collageElements = [...selectedElements, ...nonSelectedElements];
        } else if (direction === 'forward') {
            // Move selected elements one position forward (towards the end)
            // Iterate from the back to allow moving elements without affecting earlier indices in the same loop
            for (let i = window.collageElements.length - 1; i >= 0; i--) {
                const element = window.collageElements[i];
                if (selectedElements.includes(element)) {
                    const currentIndex = i;
                    // If the element is not the last one in the array
                    // AND the element directly after it is NOT also selected (to preserve internal order)
                    if (currentIndex < window.collageElements.length - 1 && !selectedElements.includes(window.collageElements[currentIndex + 1])) {
                        // Swap with the element directly after it
                        [window.collageElements[currentIndex], window.collageElements[currentIndex + 1]] = 
                        [window.collageElements[currentIndex + 1], window.collageElements[currentIndex]];
                    }
                }
            }
        } else if (direction === 'backward') {
            // Move selected elements one position backward (towards the beginning)
            // Iterate from the front to allow moving elements without affecting later indices in the same loop
            for (let i = 0; i < window.collageElements.length; i++) {
                const element = window.collageElements[i];
                if (selectedElements.includes(element)) {
                    const currentIndex = i;
                    // If the element is not the first one in the array
                    // AND the element directly before it is NOT also selected (to preserve internal order)
                    if (currentIndex > 0 && !selectedElements.includes(window.collageElements[currentIndex - 1])) {
                        // Swap with the element directly before it
                        [window.collageElements[currentIndex], window.collageElements[currentIndex - 1]] = 
                        [window.collageElements[currentIndex - 1], window.collageElements[currentIndex]];
                    }
                }
            }
        }

        window.drawCanvas(); // Redraw canvas to reflect new Z-order
        window.updateLayerButtonStates(); // Update button states
    };
});
