export default {
  content: [
    "./index.php",
    "./gallery/**/*.php",
    "./template/**/*.php",
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        'brand-1': 'var(--brand-1, #1B3FAA)',
        'brand-1-hover': 'var(--brand-1-hover, #153288)',
        'content-1': 'var(--content-1, #F1F5F8)',
        'brand-2': 'var(--brand-2, #E8EBF6)',
        'primary': 'var(--primary, #1b3faa)',
        'primary-light': 'var(--primary-light, #E8EBF6)',
        'secondary': 'var(--secondary, #5f78c3)',
        'tertiary': 'var(--tertiary, #8d9fd4)',
        'font': 'var(--font, #c9c9c9)',
        'secondary-font': 'var(--secondary-font, #333333)',
        'button-font': 'var(--button-font, #ffffff)',
        'start-font': 'var(--start-font, #333333)',
        'panel': 'var(--panel, #1b3faa)',
        'btn-border': 'var(--btn-border, #eeeeee)',
        'box': 'var(--box, #e8ebf6)',
        'gallery-button': 'var(--gallery-button, #ffffff)',
        'countdown': 'var(--border, #1b3faa)',
        'countdown-bg': 'var(--countdown-bg, #8d9fd4)',
        'cheese': 'var(--cheese, #aa1b3f)',
      },
      boxShadow: {
        "xl": "0 3px 20px #0000001c"
      },
      maxWidth: {
        "admin": "2000px"
      }
    },
  },
};

