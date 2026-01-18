// admin/collage-designer/assets/js/collage-designer-imgSetPnl.js

document.addEventListener('DOMContentLoaded', () => {
    // Basic check to ensure main designer variables/functions are available
    if (typeof window.collageCanvas === 'undefined' || typeof window.drawCanvas === 'undefined' ||
        typeof window.collageElements === 'undefined' ||
        typeof window.saveState === 'undefined' || typeof window.globalLockAspectRatio === 'undefined'
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

    // Store the last custom ratio values locally to persist them when switching away from 'custom'
    // Initialize with a common aspect ratio, e.g., 16:9
    let lastCustomRatioX = 16;
    let lastCustomRatioY = 9;

    /**
     * Calculates the greatest common divisor (GCD) of two numbers.
     * Used for simplifying aspect ratios.
     * @param {number} a
     * @param {number} b
     * @returns {number} The GCD.
     */
    function gcd(a, b) {
        return b === 0 ? a : gcd(b, a % b);
    }

    /**
     * Updates the visibility of the "Apply Aspect Ratio" button.
     */
    function updateApplyButtonVisibility() {
        if (applyAspectRatioBtn) {
            applyAspectRatioBtn.classList.toggle('hidden', aspectRatioPresetSelect.value !== 'custom');
        }
    }

    /**
     * Updates the image-specific settings panel based on the currently active element.
     * This function is expected to be called by the main updateElementSettingsPanel.
     * It only updates the UI elements within this panel.
     */
    window.updateImageSettingsPanel = function() {
        if (!window.activeElement || window.activeElement.type !== 'image') {
            imageSettingsPanel.classList.add('hidden');
            return;
        }

        imageSettingsPanel.classList.remove('hidden');

        document.getElementById('selected_image_element_id_display').textContent = `ID: ${window.activeElement.id}`;

        // --- Update Aspect Ratio settings ---
        const currentWidth = window.activeElement.width;
        const currentHeight = window.activeElement.height;
        const currentAspectRatio = currentWidth / currentHeight;

        let presetFound = false;
        let selectedPresetValue = 'custom'; // Default to 'custom'

        // Check if current AR matches any preset
        for (const option of aspectRatioPresetSelect.options) {
            if (option.value !== 'original' && option.value !== 'custom') {
                const [ratioW, ratioH] = option.value.split(':').map(Number);
                if (ratioH !== 0 && Math.abs((ratioW / ratioH) - currentAspectRatio) < 0.001) { // More precise float comparison
                    selectedPresetValue = option.value;
                    presetFound = true;
                    break;
                }
            }
        }
        
        aspectRatioPresetSelect.value = selectedPresetValue;

        // If 'custom' is selected (or no preset matches), populate custom inputs
        if (aspectRatioPresetSelect.value === 'custom') {
            customAspectRatioInputsDiv.classList.remove('hidden');

            // Calculate current element's ratio in simplest integer form (e.g., 622:777 -> 2:3 or 5:6)
            const commonDivisor = gcd(Math.round(currentWidth), Math.round(currentHeight));
            const displayRatioX = Math.round(currentWidth / commonDivisor);
            const displayRatioY = Math.round(currentHeight / commonDivisor);

            // Update lastCustomRatioX/Y with the element's actual ratio for persistence
            lastCustomRatioX = displayRatioX;
            lastCustomRatioY = displayRatioY;

            customRatioXInput.value = displayRatioX;
            customRatioYInput.value = displayRatioY;
            customRatioXSlider.value = displayRatioX;
            customRatioYSlider.value = displayRatioY;

        } else {
            customAspectRatioInputsDiv.classList.add('hidden');
        }
        
        updateApplyButtonVisibility(); // Update button visibility based on selection

        // --- Update Frame checkbox ---
        if (showFrameCheckbox) {
            showFrameCheckbox.checked = window.activeElement.show_frame === true;
        }
    };

    /**
     * Applies a chosen aspect ratio to the active element.
     * This function centralizes the logic for aspect ratio changes.
     * @param {string} type 'preset', 'original', or 'custom'
     * @param {number} [ratioW] Custom ratio width component (optional, for preset/custom)
     * @param {number} [ratioH] Custom ratio height component (optional, for preset/custom)
     */
    function applyAspectRatio(type, ratioW, ratioH) {
        if (!window.activeElement || window.activeElement.type !== 'image') return;

        window.saveState(); // Save state BEFORE applying changes

        let newAspectRatio;

        if (type === 'original') {
            const imgElement = window.activeElement.image;
            if (imgElement && imgElement.naturalWidth && imgElement.naturalHeight) {
                newAspectRatio = imgElement.naturalWidth / imgElement.naturalHeight;
            } else {
                console.warn('Original image dimensions not available for aspect ratio reset. Using current ratio as fallback.');
                newAspectRatio = window.activeElement.width / window.activeElement.height; // Fallback to current ratio
            }
        } else if ((type === 'preset' || type === 'custom') && ratioH !== 0 && ratioW > 0 && ratioH > 0) {
            newAspectRatio = ratioW / ratioH;
        } else {
            console.warn('Invalid aspect ratio parameters for application.');
            return;
        }

        // Apply new aspect ratio while maintaining current width
        const newHeight = window.activeElement.width / newAspectRatio;
        window.activeElement.height = newHeight;
        window.globalLockAspectRatio = true; // Lock aspect ratio after applying any ratio

        window.drawCanvas(); // Draw after applying value
        window.updateElementSettingsPanel(); // Re-populate all panels to reflect height/width changes
    }

    /**
     * Event listeners for the Aspect Ratio section.
     */
    function setupAspectRatioEventListeners() {
        // Preset select changed
        aspectRatioPresetSelect.addEventListener('change', () => {
            if (!window.activeElement || window.activeElement.type !== 'image') return;

            const selectedValue = aspectRatioPresetSelect.value;
            customAspectRatioInputsDiv.classList.toggle('hidden', selectedValue !== 'custom');
            updateApplyButtonVisibility(); // Update button visibility immediately

            if (selectedValue === 'custom') {
                // When switching to custom, ensure current element's ratio is displayed
                window.updateImageSettingsPanel(); // This will recalculate and set custom fields
            } else {
                // For 'original' or presets, immediately apply the ratio.
                if (selectedValue === 'original') {
                    applyAspectRatio('original');
                } else { // Preset values like '1:1', '4:3'
                    const [ratioW, ratioH] = selectedValue.split(':').map(Number);
                    if (!isNaN(ratioW) && !isNaN(ratioH) && ratioH !== 0) {
                        applyAspectRatio('preset', ratioW, ratioH);
                    }
                }
            }
        });

        // Custom ratio sliders and inputs synchronization
        [
            { slider: customRatioXSlider, input: customRatioXInput, prop: 'x' },
            { slider: customRatioYSlider, input: customRatioYInput, prop: 'y' }
        ].forEach(({ slider, input, prop }) => {
            slider.addEventListener('input', () => {
                input.value = slider.value;
                // Store last custom value
                if (prop === 'x') lastCustomRatioX = parseInt(slider.value);
                if (prop === 'y') lastCustomRatioY = parseInt(slider.value);
            });
            input.addEventListener('input', () => {
                let val = parseInt(input.value);
                // Ensure input value is within slider's min/max and is a valid number
                if (isNaN(val) || val < parseInt(slider.min)) val = parseInt(slider.min);
                if (val > parseInt(slider.max)) val = parseInt(slider.max);
                input.value = val;
                slider.value = val;
                // Store last custom value
                if (prop === 'x') lastCustomRatioX = val;
                if (prop === 'y') lastCustomRatioY = val;
            });
        });

        // Apply Custom Aspect Ratio Button
        applyAspectRatioBtn.addEventListener('click', () => {
            if (!window.activeElement || window.activeElement.type !== 'image' || aspectRatioPresetSelect.value !== 'custom') return;

            const ratioW = parseInt(customRatioXInput.value);
            const ratioH = parseInt(customRatioYInput.value);

            if (isNaN(ratioW) || isNaN(ratioH) || ratioH === 0 || ratioW <= 0 || ratioH <= 0) {
                console.warn('Invalid custom aspect ratio values.');
                return;
            }
            applyAspectRatio('custom', ratioW, ratioH);
        });
        
        // No initial setTimeout needed anymore, updateImageSettingsPanel will call updateApplyButtonVisibility
        // right after setting the dropdown value.
    }

    /**
     * Event listener for the Frame checkbox.
     */
    function setupFrameCheckboxListener() {
        if (showFrameCheckbox) {
            showFrameCheckbox.addEventListener('change', () => {
                if (!window.activeElement || window.activeElement.type !== 'image') return;
                
                window.saveState(); // Save state BEFORE applying changes

                window.activeElement.show_frame = showFrameCheckbox.checked;
                
                window.drawCanvas(); // Draw after applying value
                window.updateElementSettingsPanel(); // Update general panel to reflect UI changes (if any)
            });
        }
    }

    // Initialize event listeners
    setupAspectRatioEventListeners();
    setupFrameCheckboxListener();
});