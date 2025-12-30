# Themes

Themes allow you to save and restore groups of visual and event‑related settings from the admin panel.

## Where to find it

- Open the admin panel.
- Go to the *General* section and look for the **Themes** card.

## What gets saved

All inputs in the admin panel that are marked as theme‑relevant are saved when you create a theme.
Typical examples include:

- Colors (primary, secondary, background, countdown, etc.)
- Background images and frames
- Event text and icon
- Logo visibility, path and position
- Picture/collage specific appearance options (polaroid effect, text overlays, placeholders, …)

## How themes are stored

Themes are stored as JSON files in `private/themes`.

- File name: `<theme-name>.theme.config.json`
- Format: **grouped by category**, similar to `my.config.inc.php`, for example:

```json
{
  "event": {
    "symbol": "fa-birthday-cake",
    "textLeft": "Lisa & Tom",
    "textRight": "01.01."
  },
  "colors": {
    "primary": "#4a7c1f",
    "secondary": "#6b8a55",
    "background_countdown": "#eef4ea",
    "countdown": "#5b7d4a"
  },
  "picture": {
    "frame": "private/images/frames/birthday.png",
    "extend_by_frame": "false"
  },
  "collage": {
    "background_color": "#292035",
    "take_frame": "once"
  }
}
```

- Keys correspond to the configuration categories (for example `event`, `colors`, `picture`, `collage`).
- Inside each category, the keys match the setting names (for example `symbol`, `frame`, `background_color`).

## Using themes

In the **Themes** card you can:

- **Save** a theme
    - Configure your settings in the admin panel.
    - Enter a name in the theme name input.
    - Click **Save theme**.
    - A JSON file is created or updated in `private/themes`.

- **Export** a theme
    - Select a theme from the dropdown.
    - Click **Export**.
    - A ZIP is downloaded containing:
        - `private/themes/<theme-name>.theme.config.json`
        - All files referenced by the theme (for example background images, frames, logos) from `private/` and `resources/`.
        - The folder structure inside the ZIP preserves the paths relative to the project root.

- **Import** a theme
    - Click **Import** and choose a ZIP created by the export feature.
    - The theme name is taken from the `.theme.config.json` filename inside the ZIP.
    - If a theme with the same name already exists, it is overwritten.
    - The ZIP is unpacked into the project preserving its contained folder structure (only `private/` and `resources/` are written).
    - The imported theme shows up in the dropdown and will be applied immediately.

- **Load** a theme
    - Select a theme from the dropdown.
    - Click **Load theme**.
    - All theme‑related fields are updated to the saved values.

- **Delete** a theme
    - Select a theme from the dropdown.
    - Click **Delete theme**.
    - The corresponding JSON file in `private/themes` is removed.

## Relation to `my.config.inc.php`

`config/my.config.inc.php` contains your current active configuration. Themes are templates that contain only the theme‑relevant parts of this configuration.

- Loading a theme updates the corresponding settings in the admin form.
- After loading a theme, you still have to **save the configuration** in the admin panel to write the values back to `my.config.inc.php`.
