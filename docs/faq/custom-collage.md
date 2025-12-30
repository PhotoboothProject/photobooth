# Custom collage design

Use a custom collage layout by pointing the collage setting to `private/collage.json`. You can create the file manually or with the generator at `http://localhost/admin/generator/index.php` (or `http://localhost/photobooth/admin/generator/index.php`). Save the admin panel after modifying the file so Photobooth recalculates the number of photos.

## Collage Layout JSON Lookup

This document describes how the application searches for a collage layout `.json` file.

The lookup process follows a **strict priority order**.
**Once a file is found, the search stops immediately.**

---

## File Name

The lookup is performed for a layout file defined by Photobooth.
Possible file names:

- `1+2-1.json`
- `1+3-1.json`
- `1+3-2.json`
- `2+1-1.json`
- `2+2-1.json`
- `2+2-2.json`
- `2x3-1.json`
- `2x3-2.json`
- `2x4-1.json`
- `2x4-2.json`
- `2x4-3.json`
- `2x4-4.json`
- `3+1-1.json`
- `collage.json`

---

## Lookup Order

The application searches the following locations in order:

### 1. Private, orientation-specific
- `private/collage/portrait/xxx.json`
- `private/collage/landscape/xxx.json`

---

### 2. Private, collage root

- `private/collage/xxx.json`

---

### 3. Private, global root

- `private/xxx.json`

---

### 4. Template, orientation-specific

- `template/collage/landscape/xxx.json`
- `template/collage/portrait/xxx.json`

---

### 5. Template, collage root

- `template/collage/xxx.json`

---

## Lookup Rules

- The search is performed **sequentially** in the order listed above.
- As soon as a matching file is found, it is **loaded and returned**.
- **No further locations are checked** after a successful match.
- If no file is found in any location, the layout is considered **not available**.

## Basic layout format
`collage.json` must be a valid JSON array; each inner array defines one photo position:

```
[
[ "0",                     "y * 0.055",           "1.5 * y * 0.55546",   "y * 0.55546",   "10",         true       ],
[ "x * 0.555",             "y * 0.055",           "1.5 * y * 0.40812",   "y * 0.40812",   "0",          false      ],
[ "x * 0.555",             "y * 0.5368",          "1.5 * y * 0.40812",   "y * 0.40812",   "0",          true       ]
]
```

```
[ "horizontal position",   "vertical position",   "width",               "height",        "rotation",   "apply frame" ]
```

To test changes, reload `http://localhost/test/collage.php` (or `http://localhost/photobooth/test/collage.php` when installed in a subfolder). A malformed JSON (missing quotes, trailing commas) will break the layout.

## Extended collage object (v4.99+)
Wrap the layout array in an object to set resolution, text, frames and backgrounds:

```
{
  "width": "1800",
  "height": "1200",
  "text_custom_style": true,
  "text_font_size": "50",
  "text_rotation": "10",
  "text_locationx": "200",
  "text_locationy": "220",
  "text_font_color": "#420C09",
  "text_font": "/resources/fonts/GreatVibes-Regular.ttf",
  "text_line1": "This is",
  "text_line2": "a",
  "text_line3": "Custom Collage",
  "text_linespace": "100",
  "apply_frame": "once",
  "frame": "/resources/img/frames/frame_stone.png",
  "background": "/resources/img/background.png",
  "background_color": "#FFFFFF",
  "placeholder": true,
  "placeholderpath": "/resources/img/background/01.jpg",
  "placeholderposition": "1",
  "layout": [ ...layout array from above... ]
}
```

Notes:

- Define both `width` and `height` to override the default resolution.
- `text_custom_style` toggles text; the other text properties override admin settings.
- `frame` requires `apply_frame` set to `once` or `always`.
- `placeholder` uses `placeholderpath` at `placeholderposition`.

Single framed images only work when `apply_frame` is `always`.
