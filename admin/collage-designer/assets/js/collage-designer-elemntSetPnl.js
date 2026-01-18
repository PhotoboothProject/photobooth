// admin/collage-designer/assets/js/collage-designer-elemntSetPnl.js

document.addEventListener('DOMContentLoaded', () => {
    // Basic check to ensure main designer variables/functions are available
    // These are expected to be globally exposed by collage-designer.js
    if (typeof window.collageCanvas === 'undefined' || typeof window.drawCanvas === 'undefined' ||
        typeof window.collageElements === 'undefined' ||
        typeof window.saveState === 'undefined' || typeof window.deleteSelectedElements === 'undefined'
    ) {
        console.error('collage-designer-elemntSetPnl.js: Dependent main designer variables/functions not found. Ensure collage-designer.js is loaded first and exposes necessary variables globally.');
        return;
    }


    // --- DOM Elements (Defined once at load) ---
    const elementSettingsPanel = document.getElementById('element_settings_panel');
    const noElementSelectedMessage = document.getElementById('no_element_selected_message'); // Stellen Sie sicher, dass diese ID in Ihrem HTML existiert
    const imageSpecificSettingsPanel = document.getElementById('image_specific_settings_panel'); // KORRIGIERT!
    const textSpecificSettingsPanel = document.getElementById('text_specific_settings_panel');   // Annahme: ID für Text-Panel (falls vorhanden)
    const lockAspectRatioCheckbox = document.getElementById('lock_aspect_ratio');

    // All other panel input elements should be defined here as well, 
    // or accessed dynamically inside updateElementSettingsPanel to avoid many global const.
    // For now, let's keep them here as they are accessed in setupPanelEventListeners

    const elementXPosition = document.getElementById('element_x_position');
    const elementXPositionSlider = document.getElementById('element_x_position_slider');
    const elementYPosition = document.getElementById('element_y_position');
    const elementYPositionSlider = document.getElementById('element_y_position_slider');
    const elementWidth = document.getElementById('element_width');
    const elementWidthSlider = document.getElementById('element_width_slider');
    const elementHeight = document.getElementById('element_height');
    const elementHeightSlider = document.getElementById('element_height_slider');
    const elementRotation = document.getElementById('element_rotation');
    const elementRotationSlider = document.getElementById('element_rotation_slider');

    //=================================================================================
    // --- Element Settings Panel Management ---
    //=================================================================================

    /**
     * Clamps a percentage value between 0 and 100.
     * @param {number} value The value to clamp.
     * @returns {number} The clamped value.
     */
    function clampPercentage(value) {
        return Math.max(0, Math.min(100, value));
    }

    /**
     * Clamps a rotation value between -180 and 180 degrees.
     * @param {number} value The value to clamp.
     * @returns {number} The clamped value.
     */
    function clampRotation(value) {
        return Math.max(-180, Math.min(180, value));
    }

    /**
     * Applies a new value to an element property, respecting aspect ratio lock if active.
     * This function encapsulates the common logic for updating x, y, width, height, rotation.
     * @param {string} prop The property name (e.g., 'width', 'height', 'x', 'y', 'rotation').
     * @param {number} rawValue The raw numerical value from the input/slider.
     * @param {string} [dimension=''] The canvas dimension relevant for percentage conversion ('width' or 'height').
     */
    function applyPanelValueToElement(prop, rawValue, dimension = '') {
        if (!window.activeElement) return;

        let value = parseFloat(rawValue);
        if (isNaN(value)) value = 0;

        if (prop === 'rotation') {
            value = clampRotation(value);
            window.activeElement[prop] = value;
        } else {
            const canvasDimension = (dimension === 'width') ? window.collageCanvas.width : window.collageCanvas.height;
            value = clampPercentage(value); // Clamp percentage value (0-100)
            let newDimensionValuePx = (value / 100) * canvasDimension; // Convert to pixel value

            if ((prop === 'width' || prop === 'height') && window.globalLockAspectRatio) {
                // Use the current aspect ratio of the element for locking
                const originalAspectRatio = window.activeElement.width / window.activeElement.height;
                
                if (prop === 'width') {
                    window.activeElement.width = newDimensionValuePx;
                    window.activeElement.height = window.activeElement.width / originalAspectRatio;
                } else { // prop === 'height'
                    window.activeElement.height = newDimensionValuePx;
                    window.activeElement.width = window.activeElement.height * originalAspectRatio;
                }
                
                // Clamp both dimensions to canvas limits (pixel values)
                window.activeElement.width = Math.max(0.1 * window.collageCanvas.width / 100, Math.min(window.collageCanvas.width, window.activeElement.width));
                window.activeElement.height = Math.max(0.1 * window.collageCanvas.height / 100, Math.min(window.collageCanvas.height, window.activeElement.height));

            } else {
                // Normal behavior without aspect ratio lock
                // Ensure minimum size for width/height when not locked (value is already clamped percentage 0-100)
                if ((prop === 'width' || prop === 'height') && value <= 0) {
                    newDimensionValuePx = (0.1 / 100) * canvasDimension; // Convert 0.1% to pixel
                }
                window.activeElement[prop] = newDimensionValuePx;
            }
        }
        window.drawCanvas(); // Draw after applying value
    }

    /**
     * Updates the element settings panel based on the current selection.
     * Deactivates the panel if no or multiple elements are selected.
     * Activates and populates the panel if exactly one element is selected.
     */
    window.updateElementSettingsPanel = function() {
        const selectedElements = window.collageElements.filter(el => el.isSelected);

        // Find all interactive elements within the panel (inputs, sliders, buttons)
        const interactiveElements = elementSettingsPanel.querySelectorAll(
            'input:not([type="checkbox"]), textarea, select, button' // Exclude checkboxes if they should always be active for "lock aspect ratio" etc.
        );

        if (lockAspectRatioCheckbox) {
                lockAspectRatioCheckbox.disabled = false; // Enable if it should be interactive
                lockAspectRatioCheckbox.checked = window.globalLockAspectRatio; // Reflect global setting
            }

        if (selectedElements.length === 1 && window.activeElement) {
            // Activate panel
            elementSettingsPanel.classList.remove('opacity-50', 'pointer-events-none');
            interactiveElements.forEach(el => el.disabled = false);
            if (noElementSelectedMessage) noElementSelectedMessage.classList.add('hidden'); // Check for existence

            const activeEl = window.activeElement;

            // Update basic info
            document.getElementById('selected_element_type_display').textContent = photoboothTools.getTranslation('image');
            document.getElementById('selected_element_id_display').textContent = `ID: ${activeEl.id}`;

            const canvasWidth = window.collageCanvas.width;
            const canvasHeight = window.collageCanvas.height;

            // Convert pixel values to percentage for display
            const xPercent = (activeEl.x / canvasWidth) * 100;
            const yPercent = (activeEl.y / canvasHeight) * 100;
            const widthPercent = (activeEl.width / canvasWidth) * 100;
            const heightPercent = (activeEl.height / canvasHeight) * 100;

            // Update X position
            if (elementXPosition) elementXPosition.value = xPercent.toFixed(1);
            if (elementXPositionSlider) elementXPositionSlider.value = xPercent.toFixed(1);

            // Update Y position
            if (elementYPosition) elementYPosition.value = yPercent.toFixed(1);
            if (elementYPositionSlider) elementYPositionSlider.value = yPercent.toFixed(1);

            // Update Width
            if (elementWidth) elementWidth.value = widthPercent.toFixed(1);
            if (elementWidthSlider) elementWidthSlider.value = widthPercent.toFixed(1);

            // Update Height
            if (elementHeight) elementHeight.value = heightPercent.toFixed(1);
            if (elementHeightSlider) elementHeightSlider.value = heightPercent.toFixed(1);

            // Update Rotation
            if (elementRotation) elementRotation.value = activeEl.rotation.toFixed(0);
            if (elementRotationSlider) elementRotationSlider.value = activeEl.rotation.toFixed(0);

            // --- update specific panels and show / hide them ---
            if (activeEl.type === 'image') {
                if (window.updateImageSettingsPanel) window.updateImageSettingsPanel();
                if (imageSpecificSettingsPanel) imageSpecificSettingsPanel.classList.remove('hidden'); // Jetzt sollte es gehen!
                if (textSpecificSettingsPanel) textSpecificSettingsPanel.classList.add('hidden');
            } else if (activeEl.type === 'text') {
                if (window.updateTextSettingsPanel) window.updateTextSettingsPanel();
                if (textSpecificSettingsPanel) textSpecificSettingsPanel.classList.remove('hidden');
                if (imageSpecificSettingsPanel) imageSpecificSettingsPanel.classList.add('hidden');
            } else {
                // Typ unbekannt oder kein spezifisches Panel
                if (imageSpecificSettingsPanel) imageSpecificSettingsPanel.classList.add('hidden');
                if (textSpecificSettingsPanel) textSpecificSettingsPanel.classList.add('hidden');
            }
            
        } else {
            // none or multiple elements selected
            elementSettingsPanel.classList.add('opacity-50', 'pointer-events-none');
            interactiveElements.forEach(el => el.disabled = true);
            
            // Clear basic info
            document.getElementById('selected_element_type_display').textContent = '';
            document.getElementById('selected_element_id_display').textContent = 'ID: ';

            // hide all specific panels
            if (noElementSelectedMessage) noElementSelectedMessage.classList.remove('hidden'); // Check for existence
            if (imageSpecificSettingsPanel) imageSpecificSettingsPanel.classList.add('hidden');
            if (textSpecificSettingsPanel) textSpecificSettingsPanel.classList.add('hidden');
        }
    };

    /**
     * Sets up event listeners for the element settings panel inputs.
     */
    function setupPanelEventListeners() {
        const inputs = [
            { id: 'element_x_position', sliderId: 'element_x_position_slider', prop: 'x', dimension: 'width' },
            { id: 'element_y_position', sliderId: 'element_y_position_slider', prop: 'y', dimension: 'height' },
            { id: 'element_width', sliderId: 'element_width_slider', prop: 'width', dimension: 'width' },
            { id: 'element_height', sliderId: 'element_height_slider', prop: 'height', dimension: 'height' },
            { id: 'element_rotation', sliderId: 'element_rotation_slider', prop: 'rotation' }
        ];

        inputs.forEach(({ id, sliderId, prop, dimension }) => {
            const numberInput = document.getElementById(id);
            const sliderInput = document.getElementById(sliderId);

            let isChanging = false; // Flag to prevent redundant saveState calls during continuous slider drag

            if (numberInput) {
                numberInput.addEventListener('change', (event) => {
                    if (!window.activeElement) return;
                    window.saveState(); // Save state on change for number input

                    applyPanelValueToElement(prop, event.target.value, dimension);
                    
                    /// Update corresponding slider
                    if (sliderInput) {
                        sliderInput.value = (prop === 'rotation') ? window.activeElement[prop].toFixed(0) : 
                                            ((window.activeElement[prop] / ((dimension === 'width') ? window.collageCanvas.width : window.collageCanvas.height)) * 100).toFixed(1);
                    }

                    window.updateElementSettingsPanel(); // Re-populate to ensure consistency and handle potential rounding
                });
            }

            if (sliderInput) {
                sliderInput.addEventListener('mousedown', () => {
                    if (!window.activeElement) return;
                    window.saveState(); // Save state at the start of slider drag
                    isChanging = true;
                });

                sliderInput.addEventListener('input', (event) => {
                    if (!window.activeElement || !isChanging) return; // Only update if actively dragging

                    applyPanelValueToElement(prop, event.target.value, dimension);
                    
                    // Update corresponding number input
                    if (numberInput) {
                        numberInput.value = (prop === 'rotation') ? window.activeElement[prop].toFixed(0) :
                                            ((window.activeElement[prop] / ((dimension === 'width') ? window.collageCanvas.width : window.collageCanvas.height)) * 100).toFixed(1);
                    }
                });

                sliderInput.addEventListener('mouseup', () => {
                    if (!window.activeElement) return;
                    isChanging = false;
                    // A final draw and panel update ensures consistency after drag ends
                    window.drawCanvas(); 
                    window.updateElementSettingsPanel(); // Re-populate to fix minor floating point inaccuracies
                });
            }
        });

        // Event listener for the lock aspect ratio checkbox
        if (lockAspectRatioCheckbox) {
            lockAspectRatioCheckbox.addEventListener('change', () => {
                window.globalLockAspectRatio = lockAspectRatioCheckbox.checked;
                window.updateElementSettingsPanel();
            });
        }

        // Event listener for the delete button inside the panel
        const deleteElementBtn = document.getElementById('panelDeleteElementBtn');
        if (deleteElementBtn) {
            deleteElementBtn.addEventListener('click', () => {
                if (window.activeElement) {
                    window.deleteSelectedElements();
                }
            });
        }
    }

    // Initialize panel event listeners once the DOM is ready
    setupPanelEventListeners();
});
