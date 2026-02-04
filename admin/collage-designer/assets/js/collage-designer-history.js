// admin/collage-designer/assets/js/collage-designer-history.js

document.addEventListener('DOMContentLoaded', () => {
    //=================================================================================
    // --- History Functions ---
    //=================================================================================

    // --- Undo/Redo History ---
    let undoStack = [];
    let redoStack = [];
    const MAX_HISTORY_SIZE = 50; // Limit the history to prevent excessive memory usage

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
    // --- Event Listeners ---
    //=================================================================================
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
});