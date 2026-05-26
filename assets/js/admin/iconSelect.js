/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals csrf photoboothTools */

const MAX_ICON_GRID_ITEMS = 600;
const CUSTOM_IMAGE_PREFIX = 'image:';
const CUSTOM_IMAGE_CATEGORY = 'custom-images';
const CUSTOM_IMAGE_DIRECTORY = 'private/images/event-symbols/';
const ICON_UPLOAD_FIELD = 'event_symbol_image';
const ADMIN_API_URL = '../api/admin.php';
const CATEGORY_TRANSLATION_KEYS = {
    all: 'event_symbol:category_all',
    event: 'event_symbol:category_event',
    photo: 'event_symbol:category_photo',
    love: 'event_symbol:category_love',
    food: 'event_symbol:category_food',
    nature: 'event_symbol:category_nature',
    music: 'event_symbol:category_music',
    people: 'event_symbol:category_people',
    time: 'event_symbol:category_time',
    tools: 'event_symbol:category_tools',
    legacy: 'event_symbol:category_legacy',
    'custom-images': 'event_symbol:category_custom_images'
};

const LEGACY_TO_LUCIDE_MAP = {
    'fa-camera': 'camera',
    'fa-camera-retro': 'camera',
    'fa-birthday-cake': 'cake',
    'fa-gift': 'gift',
    'fa-tree': 'tree-pine',
    'fa-snowflake': 'snowflake',
    'fa-heart-o': 'heart',
    'fa-regular fa-heart': 'heart',
    'fa-solid fa-heart': 'heart',
    'fa-solid fa-heart-pulse': 'heart-pulse',
    'fa-brands fa-apple': 'apple',
    'fa-anchor': 'anchor',
    'fa-light fa-champagne-glasses': 'party-popper',
    'fa-champagne-glasses': 'party-popper',
    'fa-gears': 'cog',
    'fa-cogs': 'cog',
    'fa-users': 'users'
};

function translateLabel(key, fallback) {
    if (
        typeof photoboothTools !== 'undefined' &&
        photoboothTools &&
        photoboothTools.translations &&
        typeof photoboothTools.translations === 'object' &&
        typeof photoboothTools.getTranslation === 'function'
    ) {
        try {
            const translated = photoboothTools.getTranslation(key);
            if (typeof translated === 'string' && translated !== '' && translated !== key) {
                return translated;
            }
        } catch {
            // Fallback below
        }
    }

    return fallback;
}

function getCategoryTitle(categoryId, fallback) {
    const key = CATEGORY_TRANSLATION_KEYS[String(categoryId || '').trim()];
    if (!key) {
        return fallback || String(categoryId || '');
    }

    return translateLabel(key, fallback || String(categoryId || ''));
}

function normalizeLucideName(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9-]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

function isFontAwesomeValue(value) {
    const normalized = String(value || '')
        .trim()
        .toLowerCase();
    if (!normalized) {
        return false;
    }
    if (LEGACY_TO_LUCIDE_MAP[normalized]) {
        return true;
    }
    return /(^|\s)fa($|\s)|fa-[a-z0-9-]+/.test(normalized);
}

function sanitizeFontAwesomeClasses(value) {
    const raw = String(value || '')
        .trim()
        .toLowerCase()
        .replace(/^fa:/, '');
    if (!raw) {
        return '';
    }

    const styleClasses = ['fa-solid', 'fa-regular', 'fa-brands', 'fa-light', 'fa-thin', 'fa-sharp', 'fa-classic'];
    const tokens = raw.split(/\s+/);
    const unique = {};
    const classes = [];
    let hasIconClass = false;

    tokens.forEach((token) => {
        const t = token.trim();
        if (!t || (!t.startsWith('fa-') && t !== 'fa')) {
            return;
        }
        if (unique[t]) {
            return;
        }
        unique[t] = true;
        classes.push(t);
        if (t.startsWith('fa-') && styleClasses.indexOf(t) === -1) {
            hasIconClass = true;
        }
    });

    if (!hasIconClass) {
        return '';
    }

    if (!unique.fa) {
        classes.unshift('fa');
    }

    return classes.join(' ');
}

function isIconifyValue(value) {
    const raw = String(value || '')
        .trim()
        .toLowerCase();
    if (!raw) {
        return false;
    }

    const cleaned = raw.startsWith('iconify:') ? raw.slice(8) : raw;
    return /^[a-z0-9]+(?:-[a-z0-9]+)*:[a-z0-9][a-z0-9._-]*$/.test(cleaned);
}

function normalizeIconifyValue(value) {
    let raw = String(value || '')
        .trim()
        .toLowerCase();
    if (!raw) {
        return '';
    }

    raw = raw.startsWith('iconify:') ? raw.slice(8) : raw;
    const parts = raw.split(':');
    if (parts.length !== 2) {
        return '';
    }

    const prefix = parts[0]
        .replace(/[^a-z0-9-]+/g, '')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
    const icon = parts[1]
        .replace(/[^a-z0-9._-]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^[-._]+|[-._]+$/g, '');

    if (!prefix || !icon) {
        return '';
    }

    return `${prefix}:${icon}`;
}

function looksLikeCustomImagePath(value) {
    const normalized = String(value || '')
        .trim()
        .replace(/\\/g, '/')
        .replace(/^\/+/, '')
        .toLowerCase();
    return normalized.indexOf(CUSTOM_IMAGE_DIRECTORY) === 0;
}

function normalizeCustomImageValue(value) {
    let raw = String(value || '').trim();
    if (!raw) {
        return '';
    }

    if (raw.toLowerCase().startsWith(CUSTOM_IMAGE_PREFIX)) {
        raw = raw.slice(CUSTOM_IMAGE_PREFIX.length);
    }

    raw = raw
        .replace(/\\/g, '/')
        .replace(/^\/+/, '')
        .replace(/\/{2,}/g, '/');

    if (!raw || raw.indexOf('..') !== -1 || raw.indexOf('\0') !== -1) {
        return '';
    }

    if (!/^[A-Za-z0-9._/-]+$/.test(raw)) {
        return '';
    }

    if (raw.toLowerCase().indexOf(CUSTOM_IMAGE_DIRECTORY) !== 0) {
        return '';
    }

    if (!/\.(svg|png|jpe?g|webp|gif|avif)$/i.test(raw)) {
        return '';
    }

    return `${CUSTOM_IMAGE_PREFIX}${raw}`;
}

function isCustomImageValue(value) {
    return normalizeCustomImageValue(value) !== '';
}

function buildPublicPath(relativePath) {
    const normalized = String(relativePath || '').replace(/^\/+/, '');
    if (!normalized) {
        return '';
    }

    const base =
        typeof environment !== 'undefined' && environment && typeof environment.baseUrl === 'string'
            ? environment.baseUrl
            : '/';
    const basePath = base.endsWith('/') ? base : `${base}/`;
    return new URL(normalized, `${window.location.origin}${basePath}`).toString();
}

function normalizeEventSymbolValue(value) {
    let raw = String(value || '').trim();
    if (!raw) {
        return 'camera';
    }

    if (raw.toLowerCase().startsWith(CUSTOM_IMAGE_PREFIX) || looksLikeCustomImagePath(raw)) {
        const customImage = normalizeCustomImageValue(raw);
        return customImage || 'camera';
    }

    const lower = raw.toLowerCase();
    if (lower.indexOf('lucide:') === 0) {
        raw = raw.slice(7);
    } else if (lower.indexOf('fa:') === 0) {
        raw = raw.slice(3);
    } else if (lower.indexOf('iconify:') === 0) {
        raw = raw.slice(8);
    }

    if (isFontAwesomeValue(raw)) {
        return sanitizeFontAwesomeClasses(raw) || 'fa fa-camera';
    }

    if (isIconifyValue(raw)) {
        const iconifyName = normalizeIconifyValue(raw);
        return iconifyName ? `iconify:${iconifyName}` : 'iconify:mdi:camera';
    }

    return normalizeLucideName(raw) || 'camera';
}

function mapLegacyToLucide(value) {
    const normalized = String(value || '')
        .trim()
        .toLowerCase();
    if (LEGACY_TO_LUCIDE_MAP[normalized]) {
        return LEGACY_TO_LUCIDE_MAP[normalized];
    }

    const tokens = normalized.split(/\s+/);
    for (let i = 0; i < tokens.length; i++) {
        if (LEGACY_TO_LUCIDE_MAP[tokens[i]]) {
            return LEGACY_TO_LUCIDE_MAP[tokens[i]];
        }
    }

    return 'camera';
}

function humanizeIconName(iconName) {
    return String(iconName || '')
        .replace(/[._]/g, '-')
        .split('-')
        .filter((part) => part)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function getFileBasename(pathValue) {
    const normalized = String(pathValue || '')
        .replace(/\\/g, '/')
        .split('/');
    const fileName = normalized.length ? normalized[normalized.length - 1] : '';
    return fileName.replace(/\.[^.]+$/, '');
}

function getCatalogFromPicker(picker) {
    const script = picker.querySelector('.adminIconSelection-catalog');
    if (!script || !script.textContent) {
        return null;
    }

    try {
        const parsed = JSON.parse(script.textContent);
        if (!parsed || typeof parsed !== 'object') {
            return null;
        }

        return {
            categories: Array.isArray(parsed.categories) ? parsed.categories : [],
            icons: Array.isArray(parsed.icons) ? parsed.icons : []
        };
    } catch {
        return null;
    }
}

function getLegacyIconsFromPicker(picker) {
    const script = picker.querySelector('.adminIconSelection-legacy-icons');
    if (script && script.textContent) {
        try {
            const parsed = JSON.parse(script.textContent);
            if (Array.isArray(parsed)) {
                return parsed;
            }
        } catch {
            // fallback below
        }
    }

    return [
        { value: 'fa-camera', label: 'Camera' },
        { value: 'fa-camera-retro', label: 'Camera Retro' },
        { value: 'fa-birthday-cake', label: 'Birthday Cake' },
        { value: 'fa-gift', label: 'Gift' },
        { value: 'fa-tree', label: 'Tree' },
        { value: 'fa-snowflake', label: 'Snowflake' },
        { value: 'fa-solid fa-heart', label: 'Heart (Filled)' },
        { value: 'fa-regular fa-heart', label: 'Heart' },
        { value: 'fa-anchor', label: 'Anchor' },
        { value: 'fa-users', label: 'People' }
    ];
}

function fallbackCatalog() {
    return {
        categories: [
            { id: 'all', title: getCategoryTitle('all', 'All'), source: 'system' },
            { id: 'event', title: getCategoryTitle('event', 'Event/Party'), source: 'mixed' },
            { id: 'photo', title: getCategoryTitle('photo', 'Photo/Media'), source: 'mixed' },
            { id: 'legacy', title: getCategoryTitle('legacy', 'Classic (FA)'), source: 'legacy' },
            {
                id: CUSTOM_IMAGE_CATEGORY,
                title: getCategoryTitle(CUSTOM_IMAGE_CATEGORY, 'Custom images'),
                source: 'custom'
            }
        ],
        icons: [
            {
                provider: 'lucide',
                value: 'camera',
                label: 'Camera',
                categories: ['photo', 'event'],
                search: 'camera photo event'
            },
            {
                provider: 'lucide',
                value: 'heart',
                label: 'Heart',
                categories: ['love', 'event'],
                search: 'heart love event'
            },
            {
                provider: 'lucide',
                value: 'cake',
                label: 'Cake',
                categories: ['food', 'event'],
                search: 'cake party event'
            }
        ]
    };
}

function normalizeCategories(rawCategories) {
    if (!Array.isArray(rawCategories)) {
        return ['all'];
    }

    const unique = {};
    const categories = [];
    rawCategories.forEach((category) => {
        const id = String(category || '').trim();
        if (!id || unique[id]) {
            return;
        }
        unique[id] = true;
        categories.push(id);
    });

    if (!unique.all) {
        categories.push('all');
    }

    return categories;
}

function createEntryFromCatalogItem(item) {
    if (!item || typeof item !== 'object') {
        return null;
    }

    const rawValue = String(item.value || '').trim();
    const normalizedValue = normalizeEventSymbolValue(rawValue);
    if (!normalizedValue) {
        return null;
    }

    let provider = String(item.provider || '')
        .trim()
        .toLowerCase();
    if (!provider) {
        if (isCustomImageValue(normalizedValue)) {
            provider = 'image';
        } else if (isFontAwesomeValue(normalizedValue)) {
            provider = 'fa';
        } else if (isIconifyValue(normalizedValue)) {
            provider = 'iconify';
        } else {
            provider = 'lucide';
        }
    }

    const label = String(item.label || humanizeIconName(rawValue || normalizedValue));
    const categories = normalizeCategories(item.categories || []);

    const entry = {
        provider: provider,
        value: normalizedValue,
        label: label,
        categories: categories,
        searchText: `${normalizedValue} ${label.toLowerCase()} ${String(item.search || '').toLowerCase()}`,
        lucideName: '',
        iconifyName: '',
        faClasses: '',
        imagePath: '',
        imageSrc: ''
    };

    if (provider === 'fa') {
        entry.faClasses = sanitizeFontAwesomeClasses(normalizedValue);
        if (!entry.faClasses) {
            return null;
        }
        entry.lucideName = mapLegacyToLucide(normalizedValue);
    } else if (provider === 'iconify') {
        const iconifyName = normalizeIconifyValue(normalizedValue);
        if (!iconifyName) {
            return null;
        }
        entry.iconifyName = iconifyName;
    } else if (provider === 'image') {
        const imageValue = normalizeCustomImageValue(normalizedValue);
        if (!imageValue) {
            return null;
        }
        entry.value = imageValue;
        entry.imagePath = imageValue.slice(CUSTOM_IMAGE_PREFIX.length);
        entry.imageSrc = buildPublicPath(entry.imagePath);
        if (!entry.imageSrc) {
            return null;
        }
        if (!entry.label || entry.label === entry.value) {
            entry.label = humanizeIconName(getFileBasename(entry.imagePath));
        }
        entry.categories = normalizeCategories([CUSTOM_IMAGE_CATEGORY]);
    } else {
        const lucideName = normalizeLucideName(normalizedValue);
        if (!lucideName) {
            return null;
        }
        entry.lucideName = lucideName;
    }

    return entry;
}

function createEntryFromRawValue(value) {
    const normalizedValue = normalizeEventSymbolValue(value);
    if (!normalizedValue) {
        return null;
    }

    let provider = 'lucide';
    if (isCustomImageValue(normalizedValue)) {
        provider = 'image';
    } else if (isFontAwesomeValue(normalizedValue)) {
        provider = 'fa';
    } else if (isIconifyValue(normalizedValue)) {
        provider = 'iconify';
    }

    const rawLabel =
        provider === 'image' ? humanizeIconName(getFileBasename(normalizedValue)) : humanizeIconName(normalizedValue);

    return createEntryFromCatalogItem({
        provider: provider,
        value: normalizedValue,
        label: rawLabel,
        categories: provider === 'image' ? [CUSTOM_IMAGE_CATEGORY] : ['event'],
        search: normalizedValue
    });
}

function buildPickerEntries(picker) {
    const entries = [];
    const seenValues = {};

    const catalog = getCatalogFromPicker(picker) || fallbackCatalog();
    catalog.icons.forEach((item) => {
        const entry = createEntryFromCatalogItem(item);
        if (!entry || seenValues[entry.value]) {
            return;
        }

        seenValues[entry.value] = true;
        entries.push(entry);
    });

    const legacyIcons = getLegacyIconsFromPicker(picker);
    legacyIcons.forEach((item) => {
        const normalizedValue = normalizeEventSymbolValue(item && item.value ? item.value : '');
        const faClasses = sanitizeFontAwesomeClasses(normalizedValue);
        if (!faClasses || seenValues[normalizedValue]) {
            return;
        }

        seenValues[normalizedValue] = true;
        const label = item && item.label ? String(item.label) : normalizedValue;
        const lucideFallback = mapLegacyToLucide(normalizedValue);

        entries.push({
            provider: 'fa',
            value: normalizedValue,
            label: label,
            categories: ['legacy', 'all'],
            searchText: `${normalizedValue} ${label.toLowerCase()} ${lucideFallback} legacy`,
            lucideName: lucideFallback,
            iconifyName: '',
            faClasses: faClasses,
            imagePath: '',
            imageSrc: ''
        });
    });

    return entries;
}

function buildCategoryList(picker, entries) {
    const catalog = getCatalogFromPicker(picker) || fallbackCatalog();
    const available = {};
    entries.forEach((entry) => {
        entry.categories.forEach((id) => {
            available[id] = true;
        });
    });

    const result = [];
    const seen = {};

    catalog.categories.forEach((item) => {
        if (!item || typeof item !== 'object') {
            return;
        }

        const id = String(item.id || '').trim();
        const title = getCategoryTitle(id, String(item.title || '').trim());
        if (!id || !title || seen[id] || !available[id]) {
            return;
        }

        seen[id] = true;
        result.push({ id: id, title: title, source: String(item.source || 'system') });
    });

    if (!seen.all) {
        result.unshift({ id: 'all', title: getCategoryTitle('all', 'All'), source: 'system' });
    }

    return result;
}

function renderLucideIcons() {
    if (!window.lucide || !window.lucide.createIcons || !window.lucide.icons) {
        return;
    }

    window.lucide.createIcons({
        icons: window.lucide.icons
    });

    document
        .querySelectorAll('.adminIconSelection svg.lucide, .event-symbol-icon.lucide, .screensaver-event-icon.lucide')
        .forEach((svg) => {
            svg.setAttribute('stroke-width', '2.8');
        });
}

function escapeAttribute(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function renderEntryIcon(entry, sizeClass) {
    const wrapClass = `adminIconSelection-iconWrap ${sizeClass}`;

    if (entry.provider === 'fa') {
        return `<span class="${wrapClass}" aria-hidden="true"><i class="${entry.faClasses} adminIconSelection-faIcon"></i></span>`;
    }

    if (entry.provider === 'iconify') {
        return `<span class="${wrapClass}" aria-hidden="true"><iconify-icon icon="${entry.iconifyName}" class="adminIconSelection-iconifyIcon"></iconify-icon></span>`;
    }

    if (entry.provider === 'image') {
        return `<span class="${wrapClass}" aria-hidden="true"><img src="${escapeAttribute(entry.imageSrc)}" class="adminIconSelection-imageIcon" alt="" loading="lazy" /></span>`;
    }

    return `<span class="${wrapClass}" aria-hidden="true"><i data-lucide="${entry.lucideName}" class="adminIconSelection-lucideIcon h-full w-full"></i></span>`;
}

function findEntryByValue(picker, value) {
    const state = picker._iconPickerState;
    if (!state) {
        return null;
    }

    const normalized = normalizeEventSymbolValue(value);
    for (let i = 0; i < state.entries.length; i++) {
        if (state.entries[i].value === normalized) {
            return state.entries[i];
        }
    }

    return null;
}

function updateCategoryButtons(picker, activeCategory) {
    picker.querySelectorAll('.adminIconSelection-theme').forEach((button) => {
        const isActive = button.getAttribute('data-icon-category') === activeCategory;
        button.classList.toggle('bg-brand-1', isActive);
        button.classList.toggle('text-white', isActive);
        button.classList.toggle('border-brand-1', isActive);
        button.classList.toggle('bg-white', !isActive);
        button.classList.toggle('text-gray-700', !isActive);
    });
}

function updateDeleteCustomImageButton(picker, value) {
    const button = picker.querySelector('.adminIconSelection-deleteImage');
    if (!button) {
        return;
    }

    const normalized = normalizeEventSymbolValue(value);
    const isCustom = isCustomImageValue(normalized);

    button.classList.toggle('hidden', !isCustom);
    if (isCustom) {
        button.setAttribute('data-icon-value', normalized);
    } else {
        button.removeAttribute('data-icon-value');
    }
}

function updatePickerPreview(picker, value) {
    const entry = findEntryByValue(picker, value) || createEntryFromRawValue(value);
    const previewButton = picker.querySelector('.adminIconSelection-open');
    const currentText = picker.querySelector('.adminIconSelection-current');

    if (!previewButton || !entry) {
        return;
    }

    previewButton.innerHTML = renderEntryIcon(entry, 'adminIconSelection-iconWrap--preview text-brand-1');
    if (currentText) {
        currentText.textContent = normalizeEventSymbolValue(entry.value);
    }

    const directInput = picker.querySelector('.adminIconSelection-directInput');
    if (directInput) {
        directInput.value = normalizeEventSymbolValue(entry.value);
    }

    updateDeleteCustomImageButton(picker, entry.value);
    renderLucideIcons();
}

function setPickerIcon(picker, value) {
    const input = picker.querySelector('.adminIconSelection-input');
    if (!input) {
        return;
    }

    const normalized = normalizeEventSymbolValue(value);
    input.value = normalized;
    updatePickerPreview(picker, normalized);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

function buildIconButton(picker, entry) {
    const selectedValue = normalizeEventSymbolValue(
        picker.querySelector('.adminIconSelection-input')?.value || picker.getAttribute('data-default-icon') || 'camera'
    );

    const isSelected = selectedValue === entry.value;
    const button = document.createElement('button');
    button.type = 'button';
    button.className =
        'adminIconSelection-item min-h-20 rounded-lg border p-2 flex flex-col items-center justify-center gap-1 transition text-[11px] ' +
        (entry.provider === 'image' ? 'min-h-24 ' : '') +
        (isSelected
            ? 'border-brand-1 bg-brand-1/10 text-brand-1'
            : 'border-gray-300 bg-white text-gray-700 hover:border-brand-1 hover:text-brand-1');
    button.setAttribute('data-icon-name', entry.value);
    button.setAttribute('title', `${entry.label} (${entry.value})`);
    button.innerHTML =
        renderEntryIcon(entry, 'adminIconSelection-iconWrap--grid') +
        '<span class="block text-center leading-tight break-all">' +
        entry.label +
        '</span>';

    if (entry.provider === 'image') {
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'adminIconSelection-itemDelete';
        deleteButton.textContent = translateLabel('event_symbol:delete_image', 'Delete image');
        deleteButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            deleteCustomImage(picker, entry.value);
        });
        button.appendChild(deleteButton);
    }

    button.addEventListener('click', function () {
        setPickerIcon(picker, entry.value);
        closeAdminIconSelect();
    });

    return button;
}

function renderIconGridForPicker(picker) {
    const grid = picker.querySelector('.adminIconSelection-grid');
    if (!grid || !picker._iconPickerState) {
        return;
    }

    const state = picker._iconPickerState;
    const query = String(state.searchQuery || '')
        .trim()
        .toLowerCase();
    const activeCategory = state.activeCategory || 'all';

    const filtered = state.entries.filter((entry) => {
        if (activeCategory !== 'all' && entry.categories.indexOf(activeCategory) === -1) {
            return false;
        }

        if (!query) {
            return true;
        }

        return entry.searchText.indexOf(query) !== -1;
    });

    grid.innerHTML = '';
    if (filtered.length === 0) {
        const noIconsLabel = translateLabel('event_symbol:no_icons_found', 'No icons found for this filter.');
        grid.innerHTML = `<div class="col-span-full text-center text-sm text-gray-500 py-8">${escapeAttribute(noIconsLabel)}</div>`;
        return;
    }

    const visible = filtered.slice(0, MAX_ICON_GRID_ITEMS);
    visible.forEach((entry) => {
        grid.appendChild(buildIconButton(picker, entry));
    });

    if (filtered.length > MAX_ICON_GRID_ITEMS) {
        const hint = document.createElement('div');
        hint.className = 'col-span-full text-center text-xs text-gray-500 py-2';
        const hiddenCount = filtered.length - MAX_ICON_GRID_ITEMS;
        const hiddenTemplate = translateLabel(
            'event_symbol:hidden_icons_hint',
            '%d more icons hidden. Please narrow down search or category.'
        );
        hint.textContent = hiddenTemplate.replace('%d', String(hiddenCount));
        grid.appendChild(hint);
    }

    renderLucideIcons();
}

function renderCategoryButtons(picker) {
    const state = picker._iconPickerState;
    const themesContainer = picker.querySelector('.adminIconSelection-themes');
    if (!themesContainer || !state) {
        return;
    }

    themesContainer.innerHTML = '';
    state.categories.forEach((category) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('data-icon-category', category.id);
        button.className =
            'adminIconSelection-theme px-2 py-1 rounded-full text-xs border border-gray-300 bg-white text-gray-700 hover:border-brand-1 hover:text-brand-1 transition';
        button.textContent = category.title;
        button.addEventListener('click', function () {
            picker._iconPickerState.activeCategory = category.id;
            updateCategoryButtons(picker, category.id);
            renderIconGridForPicker(picker);
        });
        themesContainer.appendChild(button);
    });

    updateCategoryButtons(picker, state.activeCategory || 'all');
}

function refreshPickerCategories(picker) {
    if (!picker._iconPickerState) {
        return;
    }

    const state = picker._iconPickerState;
    const categories = buildCategoryList(picker, state.entries);
    state.categories = categories;

    if (!categories.some((item) => item.id === state.activeCategory)) {
        state.activeCategory = categories.some((item) => item.id === 'all') ? 'all' : categories[0]?.id || 'all';
    }

    renderCategoryButtons(picker);
}

function updateUploadFileName(picker) {
    const fileInput = picker.querySelector('.adminIconSelection-uploadInput');
    const fileNameElement = picker.querySelector('.adminIconSelection-uploadFileName');
    if (!fileInput || !fileNameElement) {
        return;
    }

    const emptyLabel =
        fileNameElement.getAttribute('data-empty-label') ||
        translateLabel('event_symbol:upload_no_file_selected', 'No file selected');
    const fileName =
        fileInput.files && fileInput.files.length > 0 && fileInput.files[0] && fileInput.files[0].name
            ? fileInput.files[0].name
            : emptyLabel;

    fileNameElement.textContent = fileName;
    fileNameElement.setAttribute('title', fileName);
    fileNameElement.classList.toggle('is-empty', fileName === emptyLabel);
}

function setUploadStatus(picker, message, type) {
    const status = picker.querySelector('.adminIconSelection-uploadStatus');
    if (!status) {
        return;
    }

    status.textContent = message || '';
    status.classList.remove('text-gray-600', 'text-rose-600', 'text-emerald-600');

    if (type === 'error') {
        status.classList.add('text-rose-600');
    } else if (type === 'success') {
        status.classList.add('text-emerald-600');
    } else {
        status.classList.add('text-gray-600');
    }
}

function upsertEntry(picker, entry) {
    if (!picker._iconPickerState || !entry) {
        return;
    }

    const state = picker._iconPickerState;
    const normalizedValue = normalizeEventSymbolValue(entry.value);
    const existingIndex = state.entries.findIndex((item) => item.value === normalizedValue);

    if (existingIndex >= 0) {
        state.entries[existingIndex] = entry;
    } else {
        state.entries.push(entry);
    }

    refreshPickerCategories(picker);
    renderIconGridForPicker(picker);
}

function removeEntryByValue(picker, value) {
    if (!picker._iconPickerState) {
        return;
    }

    const normalizedValue = normalizeEventSymbolValue(value);
    const state = picker._iconPickerState;
    state.entries = state.entries.filter((entry) => entry.value !== normalizedValue);

    refreshPickerCategories(picker);
    renderIconGridForPicker(picker);
}

async function postIconAction(formData) {
    if (typeof csrf !== 'undefined' && csrf && csrf.key && csrf.token) {
        formData.append(csrf.key, csrf.token);
    }

    const response = await fetch(ADMIN_API_URL, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    });

    const payload = await response.json().catch(() => ({
        status: 'error',
        message: translateLabel('event_symbol:invalid_server_response', 'Invalid server response.')
    }));

    if (!response.ok || !payload || payload.status !== 'success') {
        const message =
            payload && payload.message
                ? payload.message
                : translateLabel('event_symbol:request_failed', 'Request failed.');
        throw new Error(message);
    }

    return payload;
}

async function uploadCustomImage(picker) {
    const fileInput = picker.querySelector('.adminIconSelection-uploadInput');
    const uploadButton = picker.querySelector('.adminIconSelection-uploadBtn');

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        setUploadStatus(
            picker,
            translateLabel('event_symbol:upload_select_file_first', 'Please select a file first.'),
            'error'
        );
        return;
    }

    const file = fileInput.files[0];
    const data = new FormData();
    data.append('type', 'event_symbol_upload');
    data.append(ICON_UPLOAD_FIELD, file);

    if (uploadButton) {
        uploadButton.disabled = true;
    }

    setUploadStatus(picker, translateLabel('event_symbol:upload_in_progress', 'Uploading...'), 'info');

    try {
        const payload = await postIconAction(data);
        const entry = createEntryFromCatalogItem(payload.icon || null);
        if (entry) {
            upsertEntry(picker, entry);
            picker._iconPickerState.activeCategory = CUSTOM_IMAGE_CATEGORY;
            updateCategoryButtons(picker, CUSTOM_IMAGE_CATEGORY);
            renderIconGridForPicker(picker);
            setPickerIcon(picker, entry.value);
            setUploadStatus(
                picker,
                payload.message || translateLabel('event_symbol:upload_success', 'Image uploaded.'),
                'success'
            );
        } else {
            setUploadStatus(
                picker,
                translateLabel(
                    'event_symbol:upload_success_but_unreadable',
                    'Upload succeeded, but icon entry could not be read.'
                ),
                'error'
            );
        }

        fileInput.value = '';
        updateUploadFileName(picker);
    } catch (error) {
        setUploadStatus(
            picker,
            error.message || translateLabel('event_symbol:upload_failed', 'Upload failed.'),
            'error'
        );
    } finally {
        if (uploadButton) {
            uploadButton.disabled = false;
        }
    }
}

async function deleteCustomImage(picker, value) {
    const normalizedValue = normalizeEventSymbolValue(value);
    if (!isCustomImageValue(normalizedValue)) {
        setUploadStatus(
            picker,
            translateLabel('event_symbol:no_custom_image_selected', 'No custom image is currently selected.'),
            'error'
        );
        return;
    }

    const data = new FormData();
    data.append('type', 'event_symbol_delete');
    data.append('value', normalizedValue);

    setUploadStatus(picker, translateLabel('event_symbol:delete_in_progress', 'Deleting image...'), 'info');

    try {
        const payload = await postIconAction(data);
        removeEntryByValue(picker, normalizedValue);

        const input = picker.querySelector('.adminIconSelection-input');
        if (input && normalizeEventSymbolValue(input.value) === normalizedValue) {
            setPickerIcon(picker, 'camera');
        }

        setUploadStatus(
            picker,
            payload.message || translateLabel('event_symbol:delete_success', 'Image deleted.'),
            'success'
        );
    } catch (error) {
        setUploadStatus(
            picker,
            error.message || translateLabel('event_symbol:delete_failed', 'Delete failed.'),
            'error'
        );
    }
}

function initAdminIconSelection(picker) {
    if (!picker || picker.getAttribute('data-icon-picker-init') === '1') {
        return;
    }

    const entries = buildPickerEntries(picker);
    const categories = buildCategoryList(picker, entries);
    const activeCategory = categories.some((item) => item.id === 'all') ? 'all' : categories[0]?.id || 'all';

    picker._iconPickerState = {
        entries: entries,
        categories: categories,
        activeCategory: activeCategory,
        searchQuery: ''
    };

    renderCategoryButtons(picker);

    const searchInput = picker.querySelector('.adminIconSelection-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            picker._iconPickerState.searchQuery = this.value || '';
            renderIconGridForPicker(picker);
        });
    }

    const valueInput = picker.querySelector('.adminIconSelection-input');
    if (valueInput) {
        valueInput.addEventListener('change', function () {
            const normalized = normalizeEventSymbolValue(this.value || 'camera');
            this.value = normalized;
            updatePickerPreview(picker, normalized);
            renderIconGridForPicker(picker);
        });

        valueInput.value = normalizeEventSymbolValue(valueInput.value || 'camera');
        updatePickerPreview(picker, valueInput.value);
    }

    const directInput = picker.querySelector('.adminIconSelection-directInput');
    const directApply = picker.querySelector('.adminIconSelection-directApply');

    if (directInput && directApply) {
        directApply.addEventListener('click', function () {
            setPickerIcon(picker, directInput.value || 'camera');
            renderIconGridForPicker(picker);
        });

        directInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                setPickerIcon(picker, directInput.value || 'camera');
                renderIconGridForPicker(picker);
            }
        });
    }

    const uploadButton = picker.querySelector('.adminIconSelection-uploadBtn');
    const uploadInput = picker.querySelector('.adminIconSelection-uploadInput');
    if (uploadInput) {
        uploadInput.addEventListener('change', function () {
            updateUploadFileName(picker);
        });
        updateUploadFileName(picker);
    }

    if (uploadButton) {
        uploadButton.addEventListener('click', function () {
            uploadCustomImage(picker);
        });
    }

    const deleteButton = picker.querySelector('.adminIconSelection-deleteImage');
    if (deleteButton) {
        deleteButton.addEventListener('click', function () {
            const selectedValue =
                this.getAttribute('data-icon-value') || picker.querySelector('.adminIconSelection-input')?.value || '';
            deleteCustomImage(picker, selectedValue);
        });
    }

    updateCategoryButtons(picker, activeCategory);
    renderIconGridForPicker(picker);
    picker.setAttribute('data-icon-picker-init', '1');
}

function initAllAdminIconSelections() {
    document.querySelectorAll('.adminIconSelection').forEach((picker) => {
        initAdminIconSelection(picker);
    });
}

// eslint-disable-next-line no-unused-vars
function openAdminIconSelect(element) {
    const picker = element.closest('.adminIconSelection');
    if (!picker) {
        return;
    }

    initAdminIconSelection(picker);
    picker.classList.add('isOpen');

    const searchInput = picker.querySelector('.adminIconSelection-search');
    if (searchInput) {
        searchInput.focus();
    }

    const directInput = picker.querySelector('.adminIconSelection-directInput');
    const currentValue = picker.querySelector('.adminIconSelection-input')?.value || '';
    if (directInput) {
        directInput.value = normalizeEventSymbolValue(currentValue);
    }
}

function closeAdminIconSelect() {
    document.querySelectorAll('.adminIconSelection').forEach((picker) => {
        picker.classList.remove('isOpen');
    });
}

$(function () {
    initAllAdminIconSelections();
    renderLucideIcons();
});
