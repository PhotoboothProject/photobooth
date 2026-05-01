# Docker development workflow

Use this setup to develop Photobooth inside Docker while editing files on your host machine.

It provides:

- Apache + PHP in container
- Source code bind-mounted from your local checkout
- Automatic JS/SCSS rebuild via watcher container
- Named volumes for `node_modules` and `vendor`

## Start development stack

From the repository root run:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build
```

Then open:

- http://localhost:8080

Stop with:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml down
```

## How live updates work

- All project files are mounted to `/app` in both services.
- The `watcher` service runs:
  - `npm run watch:gulp`
- JavaScript and Sass changes are rebuilt into `resources/` automatically.
- Polling is enabled for reliable file detection on mounted volumes:
  - `CHOKIDAR_USEPOLLING=1`
  - `CHOKIDAR_INTERVAL=250`

## Dependency handling

Dependencies stay in Docker-managed named volumes:

- `/app/node_modules`
- `/app/vendor`

At startup, the watcher bootstraps missing dependencies:

- runs `npm install` if gulp binary is missing
- runs `php bin/composer install` if `vendor/autoload.php` is missing

## Verify hot reload

1. Start the stack.
2. Edit a file in `assets/js/` or `assets/sass/`.
3. Watch logs for rebuild output from `watcher`.
4. Refresh the browser and verify your change.

## Troubleshooting

- If changes are not detected, check `watcher` logs:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml logs -f watcher
```

- If dependencies look broken, recreate containers and volumes:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml down -v
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build
```
