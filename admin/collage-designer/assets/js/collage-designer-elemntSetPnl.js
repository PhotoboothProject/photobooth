// admin/collage-designer/assets/js/collage-designer-elemntSetPnl.js

document.addEventListener('DOMContentLoaded', () => {
    // Basic check to ensure main designer variables/functions are available
    // These are expected to be globally exposed by collage-designer.js
    if (typeof window.collageCanvas === 'undefined' || typeof window.drawCanvas === 'undefined' ||
        typeof window.collageElements === 'undefined' || typeof window.activeElement === 'undefined' ||
        typeof window.saveState === 'undefined' || typeof window.deleteSelectedElements === 'undefined'
    ) {
        console.error('collage-designer-elemntSetPnl.js: Dependent main designer variables/functions not found. Ensure collage-designer.js is loaded first and exposes necessary variables globally.');
        return;
    }

    //=================================================================================
    // --- Element Settings Panel Management ---
    //=================================================================================

    /**
     * Updates the element settings panel based on the current selection.
     * Deactivates the panel if no or multiple elements are selected.
     * Activates and populates the panel if exactly one element is selected.
     */
    window.updateElementSettingsPanel = function() {
        const elementSettingsPanel = document.getElementById('element_settings_panel');
        const selectedElements = window.collageElements.filter(el => el.isSelected);

        // Find all interactive elements within the panel (inputs, sliders, buttons)
        const interactiveElements = elementSettingsPanel.querySelectorAll(
            'input:not([type="checkbox"]), textarea, select, button' // Exclude checkboxes if they should always be active for "lock aspect ratio" etc.
        );
        const lockAspectRatioCheckbox = document.getElementById('lock_aspect_ratio'); // Separate handling for this checkbox

        if (selectedElements.length === 1 && window.activeElement) {
            // Activate panel
            elementSettingsPanel.classList.remove('opacity-50', 'pointer-events-none');
            interactiveElements.forEach(el => el.disabled = false);
            if (lockAspectRatioCheckbox) lockAspectRatioCheckbox.disabled = false; // Enable if it should be interactive

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
            const elementXPosition = document.getElementById('element_x_position');
            const elementXPositionSlider = document.getElementById('element_x_position_slider');
            if (elementXPosition) elementXPosition.value = xPercent.toFixed(1);
            if (elementXPositionSlider) elementXPositionSlider.value = xPercent.toFixed(1);

            // Update Y position
            const elementYPosition = document.getElementById('element_y_position');
            const elementYPositionSlider = document.getElementById('element_y_position_slider');
            if (elementYPosition) elementYPosition.value = yPercent.toFixed(1);
            if (elementYPositionSlider) elementYPositionSlider.value = yPercent.toFixed(1);

            // Update Width
            const elementWidth = document.getElementById('element_width');
            const elementWidthSlider = document.getElementById('element_width_slider');
            if (elementWidth) elementWidth.value = widthPercent.toFixed(1);
            if (elementWidthSlider) elementWidthSlider.value = widthPercent.toFixed(1);

            // Update Height
            const elementHeight = document.getElementById('element_height');
            const elementHeightSlider = document.getElementById('element_height_slider');
            if (elementHeight) elementHeight.value = heightPercent.toFixed(1);
            if (elementHeightSlider) elementHeightSlider.value = heightPercent.toFixed(1);

            // Update Rotation
            const elementRotation = document.getElementById('element_rotation');
            const elementRotationSlider = document.getElementById('element_rotation_slider');
            if (elementRotation) elementRotation.value = activeEl.rotation.toFixed(0);
            if (elementRotationSlider) elementRotationSlider.value = activeEl.rotation.toFixed(0);

            // Hide text-specific settings for now
            const textSpecificSettings = document.getElementById('text_specific_settings');
            if (textSpecificSettings) textSpecificSettings.classList.add('hidden');
            // Hide image-specific settings
            const imageSpecificSettings = document.getElementById('image_specific_settings');
            if (imageSpecificSettings) imageSpecificSettings.classList.add('hidden');
            
        } else {
            // Deactivate panel
            elementSettingsPanel.classList.add('opacity-50', 'pointer-events-none');
            interactiveElements.forEach(el => el.disabled = true);
            if (lockAspectRatioCheckbox) lockAspectRatioCheckbox.disabled = true; // Disable if it should be inactive
            
            // Clear basic info (optional, but good for clarity)
            document.getElementById('selected_element_type_display').textContent = '';
            document.getElementById('selected_element_id_display').textContent = 'ID: ';
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

                    let value = parseFloat(event.target.value);
                    if (isNaN(value)) value = 0;

                    if (prop === 'rotation') {
                        window.activeElement[prop] = value;
                    } else {
                        const canvasDimension = (dimension === 'width') ? window.collageCanvas.width : window.collageCanvas.height;
                        // Ensure minimum size for width/height
                        if ((prop === 'width' || prop === 'height') && value <= 0) value = 0.1; 
                        window.activeElement[prop] = (value / 100) * canvasDimension;
                    }
                    
                    // Update corresponding slider
                    if (sliderInput) sliderInput.value = value;

                    window.drawCanvas();
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

                    let value = parseFloat(event.target.value);
                    if (isNaN(value)) value = 0;

                    if (prop === 'rotation') {
                        window.activeElement[prop] = value;
                    } else {
                        const canvasDimension = (dimension === 'width') ? window.collageCanvas.width : window.collageCanvas.height;
                        // Ensure minimum size for width/height
                        if ((prop === 'width' || prop === 'height') && value <= 0) value = 0.1;
                        window.activeElement[prop] = (value / 100) * canvasDimension;
                    }
                    
                    // Update corresponding number input
                    if (numberInput) numberInput.value = value.toFixed(1); // Keep one decimal for X,Y,W,H
                    if (prop === 'rotation') numberInput.value = value.toFixed(0); // No decimals for rotation

                    window.drawCanvas();
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

        // Event listener for the delete button inside the panel
        const deleteElementBtn = document.getElementById('panelDeleteElementBtn'); // Use the ID we gave it
        if (deleteElementBtn) {
            deleteElementBtn.addEventListener('click', () => {
                if (window.activeElement) {
                    window.deleteSelectedElements(); // Call the existing function
                }
            });
        }
    }

    // Initialize panel event listeners once the DOM is ready
    setupPanelEventListeners();
});
