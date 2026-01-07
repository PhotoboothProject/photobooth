# Contribute to the documentation

Help improve the Photobooth docs by following these steps.

## 1) Fork and clone

- Fork the repository on GitHub, then clone your fork.
- Work from the `dev` branch for documentation changes.

## 2) Set up a docs environment

- Create and activate a virtual environment:
  ```
  python3 -m venv .venv
  source .venv/bin/activate
  ```

- Install MkDocs and the theme:
  ```
  pip install mkdocs mkdocs-material
  ```

## 3) Run the docs locally

- From the repo root, build the docs and start the preview server:
  ```
  mkdocs build --config-file mkdocs_remote.yml
  mkdocs serve -f mkdocs_remote.yml
  ```

- Open the URL shown in the terminal (typically `http://127.0.0.1:8000`) to review your changes live.

## 4) Edit guidelines

- Don't commit changes to the `faq/` directory directly; it's generated from the `docs/` source files during the build step. Your local build files are stored inside `site/` and not tracked by git.
- Keep text concise and practical; prefer short paragraphs and bullet lists.
- Use relative links to other docs pages (e.g. `faq/index.md`), and ensure new pages are added to `mkdocs_remote.yml`.
- Place new images in `docs/assets/` and reference them with relative paths.
- Stick to ASCII unless an existing page already uses extended characters.
- Check for build warnings (missing nav entries, broken links) before opening a PR.

## 5) Open a pull request

- Commit your changes to a feature branch on your fork.
- Open a PR against `PhotoboothProject/photobooth` targeting the `dev` branch.
- Include a brief summary of what you changed and how you tested the docs (e.g. `mkdocs serve` build output).
