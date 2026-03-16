// admin/collage-designer/assets/js/collage-designer.js

document.addEventListener('DOMContentLoaded', () => {
    //console.log('Collage Designer JS loaded.');

    //=================================================================================
    // --- Global Variables (exposed via window for external scripts) ---
    //=================================================================================
    window.collageCanvas = document.getElementById('collageCanvas');
    window.collageCanvasWrapper = document.getElementById('collageCanvasWrapper');
    window.loadingOverlay = document.getElementById('loadingOverlay');

    window.ctx = window.collageCanvas.getContext('2d');
    window.collageElements = [];
    window.activeElement = null; // Represents the *single* element currently being interacted with (dragged, resized, rotated)

    // Global settings for the collage
    window.backgroundImage = null;          // Path to the global background image (e.g., 'path/to/image.jpg')
    window.backgroundColor = '#ffffff';     // Global background color, default white
    window.showGlobalFrameImage = false;    // Whether to show the global frame image on top
    window.globalFrameImage = null;         // Path to the global frame image (e.g., 'path/to/frame.png')

    // Global caches
    window.loadedFrames = {};           // globale cache for frame images
    window.loadedFontsMap = new Map();  // Global cache for fonts
    window.imageCache = {};             // globale cache for loaded images

    // Global setting for showing element outlines
    window.globalShowElementOutlines = true; // standard enabled

    // Global setting for aspect ratio lock during resizing
    window.globalLockAspectRatio = false; // standard locked

    // --- Global Fallback for Images (loaded from PHP) ---
    window.phpFallbackImageUrl = (initialDemoImagePaths && initialDemoImagePaths.length > 0) ? initialDemoImagePaths[0] : null; 

    window.currentLayout = initialCollageLayout;
    currentDesignName = document.getElementById('currentDesignName');
    currentDesignName.textContent = window.currentLayout.name || 'Unnamed Design';

    //=================================================================================
    // --- Local Variables (not exposed globally) ---
    //=================================================================================
    const BASE_URL = typeof window.AppBaseUrl !== 'undefined' ? window.AppBaseUrl : './';


    //=================================================================================
    // --- Utility Functions for Loading Overlay ---
    //=================================================================================
    if (!window.collageCanvas || !window.collageCanvasWrapper || !initialCollageLayout || typeof initialDemoImagePaths === 'undefined') {
        console.error('Required elements or data not found for collage designer.');
        return;
    }

    if (!window.ctx) {
        console.error('Failed to get 2D rendering context for canvas.');
        return;
    }

    //=================================================================================
    // --- Configuration Constants ---
    //=================================================================================
    const BORDER_COLOR = '#000000';
    const BORDER_WIDTH = 2;
    const SELECTION_COLOR = 'rgba(0, 123, 255, 0.7)';

    // Globale basevalues for all handles
    const BASE_HANDLE_SIZE = 24;
    const HANDLE_COLOR = '#FFFFFF';
    const HANDLE_STROKE_COLOR = SELECTION_COLOR;
    const HANDLE_BORDER_WIDTH = 2;

    // RESIZE HANDLES
    const RESIZE_HANDLE_SIZE = BASE_HANDLE_SIZE;
    const RESIZE_HANDLE_COLOR = HANDLE_COLOR;
    const RESIZE_HANDLE_STROKE_COLOR = HANDLE_STROKE_COLOR;
    const RESIZE_HANDLE_BORDER_WIDTH = HANDLE_BORDER_WIDTH;

    // ROTATION HANDLE
    const ROTATION_HANDLE_SIZE = BASE_HANDLE_SIZE;
    const ROTATION_HANDLE_OFFSET = 20;
    const ROTATION_HANDLE_COLOR = HANDLE_COLOR;
    const ROTATION_HANDLE_STROKE_COLOR = HANDLE_STROKE_COLOR;
    const ROTATION_HANDLE_ICON = '\u21BA';
    const ROTATION_HANDLE_ICON_FONT_SIZE = `${ROTATION_HANDLE_SIZE * 0.7}px Arial`;
    const ROTATION_CURSOR_RELATIVE_PATH = 'assets/icons/rotate-ccw.svg';
    window.ROTATION_CURSOR_URL = `url("${BASE_URL}${ROTATION_CURSOR_RELATIVE_PATH}") 12 12, auto`;

    // DELETE HANDLE
    const DELETE_HANDLE_SIZE = BASE_HANDLE_SIZE;
    const DELETE_HANDLE_OFFSET = 25; 
    const DELETE_HANDLE_COLOR = '#dc3545';
    const DELETE_HANDLE_STROKE_COLOR = '#FFFFFF';
    const DELETE_HANDLE_ICON_FONT_SIZE = `${DELETE_HANDLE_SIZE * 0.7}px Arial`;
    const DELETE_CURSOR_RELATIVE_PATH = 'assets/icons/trash.svg';
    window.DELETE_CURSOR_URL = `url("${BASE_URL}${DELETE_CURSOR_RELATIVE_PATH}") 12 12, auto`;

    window.CollageElement = class CollageElement {
        constructor(id, x, y, width, height, rotation, type = 'image', data = {}) { // Added type and generic data object
            this.id = id;
            this.x = x;
            this.y = y;
            this.width = width;
            this.height = height;
            this.rotation = rotation;
            this.isSelected = false;
            this.type = type; // 'image', 'text', 'shape', etc.

            // Type-specific properties
            switch (this.type) {
                case 'image':
                    this.image = data.image || null; // HTMLImageElement
                    this.src = data.src || null; // Original source path (can be null for demo images)
                    this.originalLayoutDataIndex = data.originalLayoutDataIndex !== undefined ? data.originalLayoutDataIndex : -1; // -1 for dynamically added images
                    this.show_frame = data.show_frame || false; // New property for image frames
                    // Potentially: aspect_ratio, original_aspect_ratio, etc. (if stored per element)
                    break;
                case 'text':
                    this.content = data.content || ''; // The actual text string
                    this.font_family = data.font_family || 'resources/fonts/GreatVibes-Regular.ttf';
                    this.font_color = data.font_color || '#000000';
                    this.font_size = data.font_size !== undefined ? data.font_size : 2; // Default font size (e.g., in %)
                    this.text_horizontal_align = data.text_horizontal_align || 'center';
                    this.text_vertical_align = data.text_vertical_align || 'center';
                    this.font_bold = data.font_bold || false;
                    this.font_italic = data.font_italic || false;
                    this.font_underline = data.font_underline || false;
                    break;
                // Add more cases for other types if needed (e.g., 'background', 'shape')
                default:
                    console.warn(`CollageElement created with unknown type: ${type}`);
                    break;
            }
        }

        isHit(mouseX, mouseY) {
            const {x: localX, y: localY } = window.getLocalMouseCoordinates(mouseX, mouseY, this);
            return window.isPointInRect(localX, localY, 0, 0, this.width, this.height);
        }
    }

    

    //=================================================================================
    // --- update Buttons ---
    //=================================================================================

    /**
     * Updates the enabled/disabled state of the layering buttons.
     */
    window.updateLayerButtonStates = function() {
        const selectedElements = window.collageElements.filter(el => el.isSelected);
        const sendToBackBtn = document.getElementById('sendToBackBtn');
        const sendBackwardBtn = document.getElementById('sendBackwardBtn');
        const bringForwardBtn = document.getElementById('bringForwardBtn');
        const bringToFrontBtn = document.getElementById('bringToFrontBtn');

        if (selectedElements.length === 0) {
            // No elements selected, disable all layering buttons
            if (sendToBackBtn) sendToBackBtn.disabled = true;
            if (sendBackwardBtn) sendBackwardBtn.disabled = true;
            if (bringForwardBtn) bringForwardBtn.disabled = true;
            if (bringToFrontBtn) bringToFrontBtn.disabled = true;
            return;
        }

        // Determine min/max index of selected elements
        let minSelectedIndex = Infinity;
        let maxSelectedIndex = -Infinity;

        selectedElements.forEach(selectedEl => {
            const index = window.collageElements.indexOf(selectedEl);
            if (index !== -1) {
                minSelectedIndex = Math.min(minSelectedIndex, index);
                maxSelectedIndex = Math.max(maxSelectedIndex, index);
            }
        });

        // Check if any selected element can be moved further back
        let canSendBackward = false;
        for (let i = 0; i < minSelectedIndex; i++) {
            if (!selectedElements.includes(window.collageElements[i])) { // Is there an unselected element further back?
                canSendBackward = true;
                break;
            }
        }
        
        // Check if any selected element can be moved further forward
        let canBringForward = false;
        for (let i = maxSelectedIndex + 1; i < window.collageElements.length; i++) {
            if (!selectedElements.includes(window.collageElements[i])) { // Is there an unselected element further forward?
                canBringForward = true;
                break;
            }
        }

        // Update button states
        if (sendToBackBtn) sendToBackBtn.disabled = (minSelectedIndex === 0);
        if (sendBackwardBtn) sendBackwardBtn.disabled = !canSendBackward;
        if (bringForwardBtn) bringForwardBtn.disabled = !canBringForward;
        if (bringToFrontBtn) bringToFrontBtn.disabled = (maxSelectedIndex === window.collageElements.length - 1);
    };

    /**
     * Updates the enabled/disabled state of the distribution buttons.
     */
    window.updateDistributionButtonStates = function() {
        const selectedElements = window.collageElements.filter(el => el.isSelected);
        const distributeH = document.getElementById('distributeHBtn');
        const distributeV = document.getElementById('distributeVBtn');

        if (selectedElements.length < 3) {
            // Less than 3 elements selected, disable all distribution buttons
            if (distributeH) distributeH.disabled = true;
            if (distributeV) distributeV.disabled = true;
            return;
        }
        // Enable all distribution buttons
        if (distributeH) distributeH.disabled = false;
        if (distributeV) distributeV.disabled = false;
    };

    /**
     * Update outline toggle button state.
     */
    window.updateOutlineToggleButtonState = function() {
        const outlineToggleBtn = document.getElementById('showElmntOutlineBtn');
        if (outlineToggleBtn) {
            if (window.globalShowElementOutlines) {
                outlineToggleBtn.classList.add('active');
                outlineToggleBtn.title = photoboothTools.getTranslation('Element Outlines: ON (Click to toggle)');
            } else {
                outlineToggleBtn.classList.remove('active');
                outlineToggleBtn.title = photoboothTools.getTranslation('Element Outlines: OFF (Click to toggle)');
            }
        }
    };

    /**
     * Update aspect ratio lock toggle button state.
     */
    window.updateAspectRatioLockButtonState = function() {
        const aspectRatioLockBtn = document.getElementById('lockAspectRatioBtn');
        if (aspectRatioLockBtn) {
            if (window.globalLockAspectRatio) {
                aspectRatioLockBtn.classList.add('active');
                aspectRatioLockBtn.title = photoboothTools.getTranslation('Aspect Ratio Lock: ON (Click to toggle)');
            } else {
                aspectRatioLockBtn.classList.remove('active');
                aspectRatioLockBtn.title = photoboothTools.getTranslation('Aspect Ratio Lock: OFF (Click to toggle)');
            }
        }
    };


    //=================================================================================
    // --- Canvas Drawing Function ---
    //=================================================================================

    window.drawCanvas = async function() {
        window.ctx.clearRect(0, 0, window.collageCanvas.width, window.collageCanvas.height);

        // --- Draw background image ---
        if (window.backgroundImage && window.backgroundImage.trim() !== '') {
            const loadedBgImage = await window.loadImageFromSrc(window.backgroundImage); // Await the actual Image object
            if (loadedBgImage) { // Only draw if the image loaded successfully
                window.ctx.drawImage(loadedBgImage, 0, 0, loadedBgImage.width, loadedBgImage.height);
            } else {
                console.warn(`Could not draw background image from: ${window.backgroundImage}`);
            }
        } else {
            // No background image path, just use the background color
            window.ctx.fillStyle = window.backgroundColor;
            window.ctx.fillRect(0, 0, window.collageCanvas.width, window.collageCanvas.height);
        }

        const frameImage = window.loadedFrames['global_frame'];

        for (const element of window.collageElements) { 
            const { x, y, width, height, rotation, type } = element;

            window.ctx.save(); // Save the current context state
            
            if (type === 'image') {
                let image = element.image;

                // If a specific src is defined, load that image instead
                if (element.src && element.src.trim() !== '') {
                    image = await window.loadImageFromSrc(element.src);
                }

                if (image) {
                    if (element.show_frame && frameImage && frameImage.complete) {
                        // if frame active, create combined image with frame
                        image = window.combineImages(image, frameImage, width, height);
                    }

                    if (rotation !== 0) {
                        const preparedImageCanvas = window.prepareRotatedImage(image, rotation, width, height);
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


            } else if (type === 'text') {
                const { content, font_family, font_color, font_size, text_horizontal_align, text_vertical_align, font_bold, font_italic, font_underline } = element;
                if (content) {

                    // apply rotation around center
                    const pivotX = x + width / 2;
                    const pivotY = y + height / 2;
                    if (rotation !== 0) {
                        window.ctx.translate(pivotX, pivotY);
                        window.ctx.rotate(-rotation * Math.PI / 180);
                        window.ctx.translate(-pivotX, -pivotY);
                    }

                    window.ctx.fillStyle = font_color;
                    
                    // Build the font string
                    let fontStyle = '';
                    if (font_italic) fontStyle += 'italic ';
                    let fontWeight = '';
                    if (font_bold) fontWeight += 'bold ';

                    const cssFontName = await window.loadFont(font_family);
                    const effectiveFontSizePx = (font_size / 100) * window.collageCanvas.height;
                    
                    window.ctx.font = `${fontStyle}${fontWeight}${effectiveFontSizePx}px "${cssFontName}", sans-serif`; 

                    window.ctx.textAlign = text_horizontal_align; // Directly use element.text_horizontal_align
                    window.ctx.textBaseline = 'middle'; // Center vertically in the bounding box

                    let translateX = x;
                    if (text_horizontal_align === 'left') {
                        //translateX = x
                    } else if (text_horizontal_align === 'right') {
                        translateX += width;
                    } else { // 'center'
                        translateX += width / 2;
                    }

                    let translateY = y;
                    if (text_vertical_align === 'top') {
                        translateY += effectiveFontSizePx / 2;
                    } else if (text_vertical_align === 'bottom') {
                        translateY += height - effectiveFontSizePx / 2;
                    } else { // 'center'
                        translateY += height / 2;
                    }

                    window.ctx.fillText(content, translateX, translateY);

                    // --- Underline Logic ---
                    if (font_underline) {
                        const metrics = window.ctx.measureText(content);
                        const textWidth = metrics.width;
                        // Calculate underline position. It needs to be below the text.
                        // `actualBoundingBoxDescent` gives the distance from baseline to bottom of text
                        const underlineOffset = metrics.actualBoundingBoxDescent + 2; // +2 for a small gap
                        
                        let underlineXStart;
                        if (text_horizontal_align === 'left') {
                            underlineXStart = -width / 2;
                        } else if (text_horizontal_align === 'right') {
                            underlineXStart = width / 2 - textWidth;
                        } else { // 'center'
                            underlineXStart = -textWidth / 2;
                        }

                        window.ctx.strokeStyle = font_color;
                        window.ctx.lineWidth = 2; // Adjust underline thickness as needed
                        window.ctx.beginPath();
                        window.ctx.moveTo(x + width/2 + underlineXStart, translateY + underlineOffset);
                        window.ctx.lineTo(x + width/2 + underlineXStart + textWidth, translateY + underlineOffset);
                        window.ctx.stroke();
                    }
                } else {
                    // Fallback for empty text content
                    window.ctx.fillStyle = '#AAAAAA';
                    window.ctx.fillRect(x, y, width, height);
                    window.ctx.fillStyle = '#FFFFFF';
                    window.ctx.font = `${Math.min(width, height) * 0.1}px Arial`;
                    window.ctx.textAlign = 'center';
                    window.ctx.textBaseline = 'middle';
                    window.ctx.fillText(`Text Placeholder`, x + width / 2, y + height / 2);
                }
            }

            // Only draw default border if not selected
            if (window.globalShowElementOutlines && !element.isSelected) { 
                window.ctx.strokeStyle = BORDER_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.strokeRect(x, y, width, height);
            }
            if (element.isSelected) {
                window.ctx.strokeStyle = SELECTION_COLOR;
                window.ctx.lineWidth = BORDER_WIDTH;
                window.ctx.strokeRect(element.x, element.y, element.width, element.height);
            }

            window.ctx.restore(); // Restore context to original state before next element
        };

        // --- After all elements are drawn, draw the handles and selection overlay ---
        window.drawHandles();

        // --- draw global frame on top if set ---
        if (showGlobalFrameImage && window.globalFrameImage !== null) {
            if(window.globalFrameImage.trim() !== '') {
                const loadedFrImage = await window.loadImageFromSrc(window.globalFrameImage); // Await the actual Image object
                if (loadedFrImage) { // Only draw if the image loaded successfully
                    window.ctx.drawImage(loadedFrImage, 0, 0, window.collageCanvas.width, window.collageCanvas.height);
                } else {
                    console.warn(`Could not draw frame from: ${window.globalFrameImage}`);
                }
            }
        }

        // --- Finally, update button states ---
        window.updateElementSettingsPanel();
        window.updateRemoveButtonState();
        window.updateLayerButtonStates();
        window.updateDistributionButtonStates();
        window.updateOutlineToggleButtonState();
    };

    //=================================================================================
    // --- Handles ---
    //=================================================================================
    window.currentHandles = {
        resize: [],         // Array of {type, cursor, handleX, handleY, handleWidth, handleHeight} (bounding box of handle itself)
        delete: null,       // {type, cursor, handleCenterX, handleCenterY, radius} (center and radius for circle test, rotation for cursor icon)
        rotate: null,       // {type, cursor, handleCenterX, handleCenterY, radius}
        selectionBox: null  // {type, x, y, width, height, rotation} (bounding box of the selection itself)
    };

    window.drawHandles = function() {
        // Clear previous handle data
        window.currentHandles = {
            resize: [],
            delete: null,
            rotate: null,
            selectionBox: null
        };

        const selectedElementsCount = window.collageElements.filter(el => el.isSelected).length;

        const visualScale = window.getCanvasVisualScale();
        const inverseScale = 1 / visualScale;

        const effectiveResizeHandleSize = RESIZE_HANDLE_SIZE * inverseScale;
        const effectiveRotationHandleSize = ROTATION_HANDLE_SIZE * inverseScale;
        const effectiveDeleteHandleSize = DELETE_HANDLE_SIZE * inverseScale;

        const effectiveRotationHandleOffset = ROTATION_HANDLE_OFFSET * inverseScale;
        const effectiveDeleteHandleOffset = DELETE_HANDLE_OFFSET * inverseScale;

        let targetForHandles = null; // Either activeElement or groupBoundingBox for resizing
        let targetRotation = 0; // Rotation for handles if a single element is selected

        if (selectedElementsCount === 1 && window.activeElement && window.activeElement.isSelected) {
            targetForHandles = window.activeElement;
            // The rotation for the handles depends on the type of the active element
            if (window.activeElement.type === 'text') {
                targetRotation = window.activeElement.rotation;
            } else if (window.activeElement.type === 'image') {
                // If images use `prepareRotatedImage` and the bounding box should still be axis-aligned,
                // then targetRotation remains 0 for images. 
                targetRotation = 0; 
            }
        } else if (selectedElementsCount > 1) {
            targetForHandles = window.getSelectionBoundingBox(); // Group bounding box is always axis-aligned, so rotation is 0
            targetRotation = 0; 
        }

        if (targetForHandles && targetForHandles.width > 0 && targetForHandles.height > 0) {
            const { x, y, width, height } = targetForHandles;

            // --- Save context for this selection/handle drawing phase ---
            window.ctx.save(); 

            // Apply transformations if a single, rotated text element is selected.
            // Group bounding box (selectedElementsCount > 1) is typically not rotated.
            // Image selection boxes are also handled as axis-aligned for now.
            if (selectedElementsCount === 1 && targetForHandles.type === 'text' && targetRotation !== 0) {
                const pivotX = x + width / 2;
                const pivotY = y + height / 2;
                window.ctx.translate(pivotX, pivotY);
                window.ctx.rotate(-targetRotation * Math.PI / 180); // Adjust sign for Canvas vs. PHP imagettftext
                window.ctx.translate(-pivotX, -pivotY);
            }

            // --- Draw selection border for the active element or group ---
            window.ctx.strokeStyle = SELECTION_COLOR;
            window.ctx.lineWidth = BORDER_WIDTH;
            if (selectedElementsCount > 1) {
                window.ctx.setLineDash([5, 5]); // Dashed line for group
            }
            window.ctx.strokeRect(x, y, width, height); // This rectangle will be rotated if context is rotated
            window.ctx.setLineDash([]); // Reset to solid line

            // Store selection box data for hit detection
            window.currentHandles.selectionBox = { x, y, width, height, rotation: targetRotation };

            // --- Draw Resizing Handles (at the corners of the rotated/unrotated bounding box) ---
            const handleOffsets = [
                { localX: 0     - effectiveResizeHandleSize / 2,    localY: 0       - effectiveResizeHandleSize / 2,    type: 'top-left',       cursor: 'nwse-resize' },
                { localX: width - effectiveResizeHandleSize / 2,    localY: 0       - effectiveResizeHandleSize / 2,    type: 'top-right',      cursor: 'nesw-resize' },
                { localX: 0     - effectiveResizeHandleSize / 2,    localY: height  - effectiveResizeHandleSize / 2,    type: 'bottom-left',    cursor: 'nesw-resize' },
                { localX: width - effectiveResizeHandleSize / 2,    localY: height  - effectiveResizeHandleSize / 2,    type: 'bottom-right',   cursor: 'nwse-resize' }
            ];
            
            handleOffsets.forEach(handleData  => {
                window.ctx.fillStyle = RESIZE_HANDLE_COLOR;
                window.ctx.strokeStyle = RESIZE_HANDLE_STROKE_COLOR;
                window.ctx.lineWidth = RESIZE_HANDLE_BORDER_WIDTH;
                window.ctx.fillRect(x + handleData.localX, y + handleData.localY, effectiveResizeHandleSize, effectiveResizeHandleSize);
                window.ctx.strokeRect(x + handleData.localX, y + handleData.localY, effectiveResizeHandleSize, effectiveResizeHandleSize);
                
                
                // Store handle data for mouse events - these are LOCAL to the targetForHandles
                window.currentHandles.resize.push({
                    type:           handleData.type,
                    cursor:         handleData.cursor,
                    handleLocalX:   handleData.localX,
                    handleLocalY:   handleData.localY,
                    handleWidth:    effectiveResizeHandleSize,
                    handleHeight:   effectiveResizeHandleSize
                });
            });

            // --- Draw Delete Handle (ONLY FOR SINGLE ACTIVE ELEMENT) ---
            if (selectedElementsCount === 1) { // Implicitly window.activeElement.isSelected is true here
                // Local coordinates for drawing (relative to targetForHandles's (x,y))
                const deleteHandleLocalCenterX = width - effectiveDeleteHandleOffset; // Positioned outside top-right
                const deleteHandleLocalCenterY = 0 + effectiveDeleteHandleOffset;
                const deleteHandleRadius = effectiveDeleteHandleSize / 2;

                window.ctx.beginPath();
                window.ctx.arc(x + deleteHandleLocalCenterX, y + deleteHandleLocalCenterY, deleteHandleRadius, 0, Math.PI * 2);
                window.ctx.fillStyle = DELETE_HANDLE_COLOR;
                window.ctx.fill();
                window.ctx.strokeStyle = DELETE_HANDLE_STROKE_COLOR;
                window.ctx.lineWidth = HANDLE_BORDER_WIDTH;
                window.ctx.stroke();
                window.ctx.fillStyle = DELETE_HANDLE_STROKE_COLOR;
                window.ctx.font = `${effectiveDeleteHandleSize * 0.7}px Arial`;
                window.ctx.textAlign = 'center';
                window.ctx.textBaseline = 'middle';
                window.ctx.fillText('X', x + deleteHandleLocalCenterX, y + deleteHandleLocalCenterY);

                // Store delete handle data for mouse events - LOCAL to targetForHandles
                window.currentHandles.delete = {
                    type: 'delete',
                    cursor: DELETE_CURSOR_URL, 
                    handleLocalX: deleteHandleLocalCenterX,
                    handleLocalY: deleteHandleLocalCenterY,
                    radius: deleteHandleRadius
                };

                // --- Draw Rotation Handle (ONLY FOR SINGLE ACTIVE ELEMENT) ---
                // Local coordinates for drawing (relative to targetForHandles's (x,y))
                const rotationHandleLocalCenterX = width / 2; // Above center
                const rotationHandleLocalCenterY = 0 - effectiveRotationHandleOffset;
                const rotationHandleRadius = effectiveRotationHandleSize / 2;

                window.ctx.beginPath();
                window.ctx.arc(x + rotationHandleLocalCenterX, y + rotationHandleLocalCenterY, rotationHandleRadius, 0, Math.PI * 2);
                window.ctx.fillStyle = ROTATION_HANDLE_COLOR;
                window.ctx.fill();
                window.ctx.strokeStyle = ROTATION_HANDLE_STROKE_COLOR;
                window.ctx.lineWidth = HANDLE_BORDER_WIDTH;
                window.ctx.stroke();
                window.ctx.fillStyle = ROTATION_HANDLE_STROKE_COLOR;
                window.ctx.font = `${effectiveRotationHandleSize * 0.7}px Arial`;
                window.ctx.textAlign = 'center';
                window.ctx.textBaseline = 'middle';
                window.ctx.fillText(ROTATION_HANDLE_ICON, x + rotationHandleLocalCenterX, y + rotationHandleLocalCenterY);

                // Store rotation handle data for mouse events - LOCAL to targetForHandles
                window.currentHandles.rotate = {
                    type: 'rotate',
                    cursor: ROTATION_CURSOR_URL, 
                    handleLocalX: rotationHandleLocalCenterX,
                    handleLocalY: rotationHandleLocalCenterY,
                    radius: rotationHandleRadius,
                };
            }
            window.ctx.restore(); // Restore context for this selection/handle drawing phase
        }
    };

    

    //=================================================================================
    // --- Initialization Function ---
    //=================================================================================

    async function initDesigner() {
        window.setupCanvasDimensions();
        const loadedImagesArray = await window.loadDemoImages();
        window.loadFrameImg();
        window.updateCollageElements(loadedImagesArray);
        window.drawCanvas();
    }

    //=================================================================================
    // --- Initialize the designer and save the very first state
    //=================================================================================
    initDesigner().then(() => {
        window.saveState(); // Save initial state after everything is loaded
        window.updateUndoRedoButtonStates(); // Update button states based on initial stack
    });
});