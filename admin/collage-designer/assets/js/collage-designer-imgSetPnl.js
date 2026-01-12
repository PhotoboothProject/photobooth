// admin/collage-designer/assets/js/collage-designer-imgSetPnl.js

document.addEventListener('DOMContentLoaded', () => {
    // Basic check to ensure main designer variables/functions are available
    if (typeof window.collageCanvas === 'undefined' || typeof window.drawCanvas === 'undefined' ||
        typeof window.collageElements === 'undefined' || typeof window.activeElement === 'undefined' ||
        typeof window.saveState === 'undefined' || typeof window.globalLockAspectRatio === 'undefined' // Added globalLockAspectRatio
    ) {
        console.error('collage-designer-imgSetPnl.js: Dependent main designer variables/functions not found. Ensure collage-designer.js is loaded first and exposes necessary variables globally.');
        return;
    }

    const imageSettingsPanel = document.getElementById('image_specific_settings_panel');
    const aspectRatioPresetSelect = document.getElementById('image_aspect_ratio_preset');
    const customAspectRatioInputsDiv = document.getElementById('custom_aspect_ratio_inputs');
    const customRatioXSlider = document.getElementById('custom_ratio_x_slider');
    const customRatioXInput = document.getElementById('custom_ratio_x');
    const customRatioYSlider = document.getElementById('custom_ratio_y_slider');
    const customRatioYInput = document.getElementById('custom_ratio_y');
    const applyAspectRatioBtn = document.getElementById('apply_aspect_ratio_btn');
    const showFrameCheckbox = document.getElementById('picture_show_frame_current');

    /**
     * Updates the image-specific settings panel based on the currently active element.
     * This function is expected to be called by the main updateElementSettingsPanel.
     */
    window.updateImageSettingsPanel = function() {
        console.log('type:', window.activeElement.type);
        if (!window.activeElement || window.activeElement.type !== 'image') {
            imageSettingsPanel.classList.add('hidden');
            return;
        }

        imageSettingsPanel.classList.remove('hidden');

        // Update ID display (optional, but good for context)
        document.getElementById('selected_image_element_id_display').textContent = `ID: ${window.activeElement.id}`;

        // --- Update Aspect Ratio settings ---
        const currentAspectRatio = (window.activeElement.width / window.activeElement.height).toFixed(2); // Calculate current AR
        let presetFound = false;
        // Set preset dropdown to current AR if it matches a preset, or to 'custom'
        for (const option of aspectRatioPresetSelect.options) {
            if (option.value !== 'original' && option.value !== 'custom') { // Skip 'original' and 'custom' for direct match
                const [ratioW, ratioH] = option.value.split(':').map(Number);
                if (Math.abs((ratioW / ratioH).toFixed(2) - currentAspectRatio) < 0.01) { // Fuzzy match for float comparison
                    aspectRatioPresetSelect.value = option.value;
                    presetFound = true;
                    break;
                }
            }
        }
        if (!presetFound) {
            // If no preset matches, set to custom and populate custom inputs with current AR
            aspectRatioPresetSelect.value = 'custom';
            customRatioXInput.value = window.activeElement.width.toFixed(0); // Use pixel values as a starting point
            customRatioYInput.value = window.activeElement.height.toFixed(0);
            customRatioXSlider.value = window.activeElement.width.toFixed(0);
            customRatioYSlider.value = window.activeElement.height.toFixed(0);
        } else {
            // If a preset matches, ensure custom inputs are hidden and cleared (or set to default custom 16:9)
            customRatioXInput.value = 16;
            customRatioYInput.value = 9;
            customRatioXSlider.value = 16;
            customRatioYSlider.value = 9;
        }
        // Show/hide custom inputs based on initial preset selection
        customAspectRatioInputsDiv.classList.toggle('hidden', aspectRatioPresetSelect.value !== 'custom');


        // --- Update Frame checkbox ---
        if (showFrameCheckbox) {
            showFrameCheckbox.checked = window.activeElement.show_frame === true;
        }

        // Draw canvas to reflect any changes if necessary (e.g., initial frame state)
        window.drawCanvas();
    };

    /**
     * Event listeners for the Aspect Ratio section.
     */
    function setupAspectRatioEventListeners() {
        // Preset select changed
        aspectRatioPresetSelect.addEventListener('change', () => {
            if (!window.activeElement) return;

            const selectedValue = aspectRatioPresetSelect.value;
            customAspectRatioInputsDiv.classList.toggle('hidden', selectedValue !== 'custom');

            if (selectedValue === 'original') {
                // Restore original aspect ratio logic
                // For simplicity, we assume original dimensions are stored or can be calculated
                // If not stored, apply current dimensions as original
                const imgElement = window.activeElement.img; // Assuming 'img' property holds the actual HTMLImageElement
                if (imgElement && imgElement.naturalWidth && imgElement.naturalHeight) {
                    const originalRatio = imgElement.naturalWidth / imgElement.naturalHeight;
                    // Apply to current element maintaining current width or height
                    const newHeight = window.activeElement.width / originalRatio;
                    window.activeElement.height = newHeight;
                } else {
                    // Fallback if naturalWidth/Height not available, use current ratio as "original"
                    // Or keep current dimensions if no explicit change is needed for "original"
                }
                window.globalLockAspectRatio = true; // Lock aspect ratio after applying original
                window.drawCanvas();
                window.saveState();
                window.updateElementSettingsPanel(); // Update general panel to reflect height change
            } else if (selectedValue !== 'custom') {
                // Apply preset aspect ratio
                const [ratioW, ratioH] = selectedValue.split(':').map(Number);
                if (!isNaN(ratioW) && !isNaN(ratioH) && ratioH !== 0) {
                    const newAspectRatio = ratioW / ratioH;
                    // Adjust height based on current width to maintain new aspect ratio
                    const newHeight = window.activeElement.width / newAspectRatio;
                    window.activeElement.height = newHeight;
                    window.globalLockAspectRatio = true; // Lock aspect ratio after applying preset
                    window.drawCanvas();
                    window.saveState();
                    window.updateElementSettingsPanel(); // Update general panel to reflect height change
                }
            }
        });

        // Custom ratio sliders and inputs synchronization
        [
            { slider: customRatioXSlider, input: customRatioXInput },
            { slider: customRatioYSlider, input: customRatioYInput }
        ].forEach(({ slider, input }) => {
            slider.addEventListener('input', () => {
                input.value = slider.value;
            });
            input.addEventListener('input', () => {
                // Ensure input value is within slider's min/max
                let val = parseInt(input.value);
                if (isNaN(val) || val < slider.min) val = parseInt(slider.min);
                if (val > slider.max) val = parseInt(slider.max);
                input.value = val;
                slider.value = val;
            });
        });

        // Apply Custom Aspect Ratio Button
        applyAspectRatioBtn.addEventListener('click', () => {
            if (!window.activeElement || aspectRatioPresetSelect.value !== 'custom') return;

            const ratioW = parseInt(customRatioXInput.value);
            const ratioH = parseInt(customRatioYInput.value);

            if (isNaN(ratioW) || isNaN(ratioH) || ratioH === 0 || ratioW <= 0 || ratioH <= 0) {
                console.warn('Invalid custom aspect ratio values.');
                return;
            }

            const newAspectRatio = ratioW / ratioH;
            const newHeight = window.activeElement.width / newAspectRatio;

            window.activeElement.height = newHeight;
            window.globalLockAspectRatio = true; // Lock aspect ratio after applying custom
            window.drawCanvas();
            window.saveState();
            window.updateElementSettingsPanel(); // Update general panel to reflect height change
        });
    }

    /**
     * Event listener for the Frame checkbox.
     */
    function setupFrameCheckboxListener() {
        if (showFrameCheckbox) {
            showFrameCheckbox.addEventListener('change', () => {
                if (!window.activeElement || window.activeElement.type !== 'image') return;
                window.activeElement.show_frame = showFrameCheckbox.checked;
                window.drawCanvas();
                window.saveState();
                window.updateImageSettingsPanel(); // To potentially reflect any UI changes
            });
        }
    }

    // Initialize event listeners
    setupAspectRatioEventListeners();
    setupFrameCheckboxListener();

    // Override the main updateElementSettingsPanel to also call updateImageSettingsPanel
    // This is important because updateElementSettingsPanel is the central function called
    // when a new element is selected or settings need to be refreshed.
    const originalUpdateElementSettingsPanel = window.updateElementSettingsPanel;
    window.updateElementSettingsPanel = function() {
        originalUpdateElementSettingsPanel(); // Call the original logic
        window.updateImageSettingsPanel();    // Then call our image-specific logic
    };
});