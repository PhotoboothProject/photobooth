<!-- components/general-tools-panel.php -->
<div class="flex flex-wrap gap-2 h-min">
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
    <button id="addBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('add') ?>">
        <i class="material-icons">add_photo_alternate</i>
    </button>
    <button id="removeBtn" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('remove') ?>">
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
    <!-- Placeholder for other tools (e.g., text specific) -->
</div>
