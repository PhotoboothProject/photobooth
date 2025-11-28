# Customizing SCSS styles

Photobooth uses a small SCSS build pipeline based on `gulp` and `sass`. You can add your own styles that will be compiled to CSS and loaded together with the default styles.

## Recommended: `_custom.scss` (loaded automatically)

This means:

-   If `private/sass/_custom.scss` exists, it is automatically imported into `framework.scss`.
-   No template changes are needed – your styles are bundled into `resources/css/framework.css`, which is already loaded by Photobooth.

To use this:

1. Create a new file called `_custom.scss` in `private/sass`.
2. Add your overrides or additional styles to this file.

## Building the CSS

From the project root run:

-   `npm install` (first time only, to install Node.js dependencies)
-   `npm run build:sass` to only compile SCSS, or
-   `npm run build` to run the full asset build (SCSS, JS, etc.).
