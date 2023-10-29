import autoprefixer from 'autoprefixer';
import babel from 'gulp-babel';
import concat from 'gulp-concat';
import filters from 'gulp-filter';
import gulp from 'gulp';
import nodeSassImporter from 'node-sass-importer';
import postcss from 'gulp-postcss';
import rename from 'gulp-rename';
import sass from 'gulp-dart-sass';
import tailwindcss from 'tailwindcss';
import twAdminConfig from './config/tailwind.admin.config.mjs';
import fs from 'fs';
import path from 'path';
import crypto from 'crypto';

gulp.task('sass', function () {
  const twFilter = filters(['**/*', '!tailwind.admin.scss']);

  return gulp
    .src('./assets/sass/**/*.scss')
    .pipe(twFilter)
    .pipe(sass().on('error', sass.logError)) // Use sass() without .sync
    .pipe(gulp.dest('./resources/css'));
});

gulp.task('js', function () {
  return gulp
    .src('./assets/js/**/*.js')
    .pipe(babel({
      presets: ['@babel/env'],
      ignore: ['assets/js/sync-to-drive.js', 'assets/js/remotebuzzer-server.js']
    }))
    .pipe(gulp.dest('./resources/js'));
});

gulp.task('tailwind-admin', function () {
  const plugins = [
    tailwindcss(twAdminConfig),
    autoprefixer(),
  ];

  return gulp
    .src('./assets/sass/tailwind.admin.scss')
    .pipe(sass({
      importer: nodeSassImporter
    }).on('error', sass.logError))
    .pipe(rename({
      extname: '.scss'
    }))
    .pipe(postcss(plugins))
    .pipe(rename({
      extname: '.css'
    }))
    .pipe(gulp.dest('./resources/css'));
});

gulp.task('js-admin', function () {
  return gulp
    .src([
      './assets/js/tools.js',
      './assets/js/admin/index.js',
      './assets/js/admin/buttons.js',
      './assets/js/admin/navi.js',
      './assets/js/admin/keypad.js',
      './assets/js/admin/imageSelect.js',
      './assets/js/admin/toast.js',
    ])
    .pipe(concat('main.admin.js'))
    .pipe(babel({
      presets: ['@babel/env'],
      ignore: ['assets/js/sync-to-drive.js', 'assets/js/remotebuzzer-server.js']
    }))
    .pipe(gulp.dest('./resources/js'));
});

async function generateAssetRevisions() {
    const resourcesFolder = 'resources';
    const revisionsManifest = 'resources/revisions.json';
    const manifest = {};

    const processFile = async (filePath) => {
        const content = fs.readFileSync(filePath);
        const sha1Hash = crypto.createHash('sha1').update(content).digest('hex');
        const relativePath = path.relative(resourcesFolder, filePath).replace(/\\/g, '/');
        manifest[resourcesFolder + '/' + relativePath] = sha1Hash;
    };

    const processFolder = async (folderPath) => {
        const files = fs.readdirSync(folderPath);
        for (const file of files) {
            const filePath = path.join(folderPath, file);
            const stats = fs.statSync(filePath);
            if (stats.isDirectory()) {
                await processFolder(filePath);
            } else if (stats.isFile() && !filePath.endsWith('revisions.json')) {
                await processFile(filePath);
            }
        }
    };

    await processFolder(resourcesFolder);

    const manifestJSON = JSON.stringify(manifest, null, 2);
    fs.writeFileSync(revisionsManifest, manifestJSON);
}

gulp.task('default', gulp.series(
    gulp.parallel('sass', 'js', 'js-admin', 'tailwind-admin'),
    generateAssetRevisions
));
