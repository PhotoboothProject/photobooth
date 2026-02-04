<!-- components/collage-designer-tools.php -->

<!-- TODO: designs might need some more love and fine tune... -->
<style>
/* Default style for all 'btn-outline-primary' buttons */
.btn-outline-primary {
    transition: all 0.1s ease-in-out;   /* Smooth transition for all visual changes */
    border: 1px solid var(--gray-300);  /* Default gray border */
    color: var(--gray-600);             /* Default darker gray text/icon */
    background-color: transparent;      /* Default transparent background */
    box-shadow: none;                   /* No shadow in resting state */
    transform: none;                    /* No transform (shift/scale) in resting state */
}

/* Style when hovering over a NON-disabled button */
.btn-outline-primary:hover:not(:disabled) {
    border-color: var(--brand-1);                   /* Border changes to primary brand color */
    color: var(--brand-1);                          /* Text and icons also change to primary brand color */
    background-color: var(--gray-100);              /* A very light, subtle background to highlight the button */
    box-shadow: 0 2px 4px var(--shadow-color);      /* A subtle shadow to make the button appear to "lift" */
    transform: translateY(-0.5px);                  /* A very slight upward shift for a "lift" effect */
}

/* Style for the "pressed" state (on click) and for the permanently "active" state of a NON-disabled button */
.btn-outline-primary:active:not(:disabled),
.btn-outline-primary.active:not(:disabled) {    /* The .active class is set via JavaScript */
    border-color: var(--brand-1);               /* Border remains in primary brand color */
    color: var(--brand-2);                      /* Text and icons turn white, as the background is now colored */
    background-color: var(--brand-1);           /* The button completely fills with the primary brand color */
    box-shadow: inset 0 1px 2px var(--shadow-inset-color); /* An inset shadow creates the impression of the button being "pressed in" */
    transform: translateY(0.5px);               /* A slight downward shift to enhance the "pressed" effect */
}

/* Style for disabled buttons */
.btn-outline-primary:disabled {
    opacity: 0.5;                       /* Button is slightly transparent to indicate it's disabled */
    cursor: not-allowed;                /* Cursor changes to indicate no action is possible */
    border-color: var(--gray-200);      /* Lighter, gray border */
    color: var(--gray-300);             /* Lighter, gray text/icon to appear "passive" */
    background-color: var(--gray-100);  /* A very light gray background */
    box-shadow: none;                   /* No shadow for disabled buttons */
    transform: none;                    /* No transform for disabled buttons */
}

/* Specific adjustments for buttons with rounded corners (like 'rounded-md'), if necessary */
.btn.rounded-md {
    border-radius: 0.375rem;
}
</style>


<div class="flex flex-wrap gap-2 h-min">
    <!-- lock aspect ratio Button --> 
    <button id="lockAspectRatioBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('lock_aspect_ratio') ?>">
        <i class="material-icons">aspect_ratio</i>
    </button>  
    <!-- show element outline Button --> 
    <button id="showElmntOutlineBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('show_element_outlines') ?>">
        <i class="material-icons">filter_frames</i>
    </button>    
    <!-- Separator -->
    <div class="border-l border-gray-300 h-6 mx-2"></div> <!-- with vertical line, just spacing: <div class="w-4"></div> -->
    <!-- Undo/Redo Buttons -->
    <button id="undoBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('undo') ?>">
        <i class="material-icons">undo</i>
    </button>
    <button id="redoBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('redo') ?>">
        <i class="material-icons">redo</i>
    </button>    
    <!-- Separator -->
    <div class="border-l border-gray-300 h-6 mx-2"></div> <!-- with vertical line, just spacing: <div class="w-4"></div> -->
    <!-- Add / Remove Buttons (Img) -->
    <button id="addImg" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('add_image') ?>">
        <i class="material-icons">add_photo_alternate</i>
    </button>
    <button id="addTxt" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('add_text') ?>">
        <i class="material-icons">post_add</i>
    </button>
    <button id="removeBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('delete') ?>">
        <i class="material-icons">delete</i>
    </button>
    <!-- Separator -->
    <div class="border-l border-gray-300 h-6 mx-2"></div> <!-- with vertical line, just spacing: <div class="w-4"></div> -->
    <!-- Alignment Buttons -->
    <button id="alignLeftBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_left') ?>">
        <i class="material-icons">align_horizontal_left</i>
    </button>
    <button id="alignCenterHBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_center_horizontal') ?>">
        <i class="material-icons">align_horizontal_center</i>
    </button>
    <button id="alignRightBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_right') ?>">
        <i class="material-icons">align_horizontal_right</i>
    </button>
    <button id="alignTopBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_top') ?>">
        <i class="material-icons">align_vertical_top</i>
    </button>
    <button id="alignMiddleVBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_middle_vertical') ?>">
        <i class="material-icons">align_vertical_center</i>
    </button>
    <button id="alignBottomBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_bottom') ?>">
        <i class="material-icons">align_vertical_bottom</i>
    </button>
    <!-- Distribution Buttons -->
    <button id="distributeHBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('distribute_horizontal') ?>">
    <i class="material-icons">horizontal_distribute</i>
    </button>
    <button id="distributeVBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('distribute_vertical') ?>">
        <i class="material-icons">vertical_distribute</i>
    </button>
    <!-- Separator -->
    <div class="border-l border-gray-300 h-6 mx-2"></div> <!-- with vertical line, just spacing: <div class="w-4"></div> -->
    <!-- Layering controls -->
    <button id="sendToBackBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" disabled title="<?= $languageService->translate('Send to Back') ?>">
        <i class="fas fa-level-down-alt"></i>
    </button>
    <button id="sendBackwardBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" disabled title="<?= $languageService->translate('Send Backward') ?>">
        <i class="fas fa-chevron-down"></i>
    </button>
    <button id="bringForwardBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" disabled title="<?= $languageService->translate('Bring Forward') ?>">
        <i class="fas fa-chevron-up"></i>
    </button>
    <button id="bringToFrontBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" disabled title="<?= $languageService->translate('Bring to Front') ?>">
        <i class="fas fa-level-up-alt"></i>
    </button>
    <!-- Placeholder for other tools (e.g., text specific) -->
</div>
