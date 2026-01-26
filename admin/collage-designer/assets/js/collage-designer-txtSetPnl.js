// admin/collage-designer/assets/js/collage-designer-txtSetPnl.js

document.addEventListener('DOMContentLoaded', () => {
    // Basic check to ensure main designer variables/functions are available
    if (typeof window.collageCanvas === 'undefined' || typeof window.drawCanvas === 'undefined' ||
        typeof window.collageElements === 'undefined' || typeof window.activeElement === 'undefined' ||
        typeof window.CollageElement === 'undefined' || typeof window.createSnapshot === 'undefined' ||
        typeof window.restoreSnapshot === 'undefined' || typeof window.saveState === 'undefined'
    ) {
        console.error('collage-designer-txtSetPnl.js: Dependent main designer variables/functions not found. Ensure collage-designer.js is loaded first and exposes necessary variables globally.');
        return;
    }

    // --- References to the Text Settings Panel and its elements ---
    const text_specific_settings_panel = document.getElementById('text_specific_settings_panel');
    const selectedTextElementIdDisplay = document.getElementById('selected_text_element_id_display');

     // References to the individual buttons for updating their state (e.g., active class)
    const txtIncrBtn = document.getElementById('txtIncr');
    const txtDecrBtn = document.getElementById('txtDecr');
    const txtBoldBtn = document.getElementById('txtBold');
    const txtItalicBtn = document.getElementById('txtIalic'); // ACHTUNG: ID ist 'txtIalic'
    const txtUnderlineBtn = document.getElementById('txtUnderline');
    const txtAlignLeftBtn = document.getElementById('txtAlignLeft');
    const txtAlignHorCenterBtn = document.getElementById('txtAlignHorCenter');
    const txtAlignRightBtn = document.getElementById('txtAlignRight');
    const txtAlignVerTopBtn = document.getElementById('txtAlignVerTop');
    const txtAlignVerCenterBtn = document.getElementById('txtAlignVerCenter');
    const txtAlignVerBottomBtn = document.getElementById('txtAlignVerBottom');

    // Helper to toggle active class for buttons
    function toggleButtonActiveState(button, isActive) {
        if (button) {
            button.classList.toggle('active', isActive);
            button.classList.toggle('btn-primary', isActive);
            button.classList.toggle('btn-outline-primary', !isActive);
        }
    }

    /**
     * Updates the text-specific settings panel based on the currently active element.
     * This function is expected to be called by a main updateElementSettingsPanel.
     * It only updates the UI elements within this panel.
     */
    window.updateTextSettingsPanel = function() {
        if (!window.activeElement || window.activeElement.type !== 'text') {
            text_specific_settings_panel.classList.add('hidden');
            return;
        }

        text_specific_settings_panel.classList.remove('hidden');
        selectedTextElementIdDisplay.textContent = `ID: ${window.activeElement.id}`;
        //electedTextElementIdDisplay.classList.remove('hidden'); // Show ID if text element is active

        // Update Button States based on activeElement properties
        toggleButtonActiveState(txtBoldBtn, window.activeElement.font_bold);
        toggleButtonActiveState(txtItalicBtn, window.activeElement.font_italic);
        toggleButtonActiveState(txtUnderlineBtn, window.activeElement.font_underline);

        // Update Horizontal Alignment Buttons
        toggleButtonActiveState(txtAlignLeftBtn, window.activeElement.text_horizontal_align === 'left');
        toggleButtonActiveState(txtAlignHorCenterBtn, window.activeElement.text_horizontal_align === 'center');
        toggleButtonActiveState(txtAlignRightBtn, window.activeElement.text_horizontal_align === 'right');

        // Update Vertical Alignment Buttons
        toggleButtonActiveState(txtAlignVerTopBtn, window.activeElement.text_vertical_align === 'top');
        toggleButtonActiveState(txtAlignVerCenterBtn, window.activeElement.text_vertical_align === 'center');
        toggleButtonActiveState(txtAlignVerBottomBtn, window.activeElement.text_vertical_align === 'bottom');

    };

    //=================================================================================
    // --- Text Functions ---
    //=================================================================================
    
    // Increase Font Size
    txtIncrBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.font_size += 1; // Increase font size by 1 unit
        });
        window.drawCanvas();
    });

    // Decrease Font Size
    txtDecrBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            if (element.font_size > 1) { // Prevent font size from going below 1
                element.font_size -= 1; // Decrease font size by 1 unit
            }
        });
        window.drawCanvas();
    });

    // Toggle Bold
    txtBoldBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.font_bold = !element.font_bold; // Toggle bold
        });
        window.drawCanvas();
    });

    // Toggle Italic
    txtItalicBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.font_italic = !element.font_italic; // Toggle italic
        });
        window.drawCanvas();
    });

    // Toggle Underline
    txtUnderlineBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.font_underline = !element.font_underline; // Toggle underline
        });
        window.drawCanvas();
    });

    // Align Text Left
    txtAlignLeftBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.text_horizontal_align = 'left'; // Set text alignment to left
        });
        window.drawCanvas();
    });

    // Align Text Center
    txtAlignHorCenterBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.text_horizontal_align = 'center'; // Set text alignment to center
        });
        window.drawCanvas();
    });

    // Align Text Right
    txtAlignRightBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.text_horizontal_align = 'right'; // Set text alignment to right
        });
        window.drawCanvas();
    });

    // Align Text Vertical Top
    txtAlignVerTopBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.text_vertical_align = 'top'; // Set text vertical alignment to top
        });
        window.drawCanvas();
    });

    // Align Text Vertical Center
    txtAlignVerCenterBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.text_vertical_align = 'center'; // Set text vertical alignment to center
        });
        window.drawCanvas();
    });

    // Align Text Vertical Bottom
    txtAlignVerBottomBtn.addEventListener('click', () => {
        window.saveState(); // Save state for undo functionality
        const selectedElements = window.getSelectedElements().filter(el => el.type === 'text');
        selectedElements.forEach(element => {
            element.text_vertical_align = 'bottom'; // Set text vertical alignment to bottom
        });
        window.drawCanvas();
    });
});