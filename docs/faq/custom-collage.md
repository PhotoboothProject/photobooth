# Custom collage design

Use a custom collage layout by pointing the collage setting to `private/collage.json`. You can create the file manually or with the generator at `http://localhost/admin/generator/index.php` (or `http://localhost/photobooth/admin/generator/index.php`). Save the admin panel after modifying the file so Photobooth recalculates the number of photos.

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

To test changes, reload `http://localhost/test/collage.php`. A malformed JSON (missing quotes, trailing commas) will break the layout.

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
