import autoprefixer from 'autoprefixer';
import babel from 'gulp-babel';
import concat from 'gulp-concat';
import filters from 'gulp-filter';
import gulp from 'gulp';
import nodeSassImporter from 'node-sass-importer';
import postcss from 'postcss';
import rename from 'gulp-rename';
import sass from 'gulp-dart-sass';
import { promisify } from 'util';
import { compileAsync } from 'sass'
import tailwindcss from 'tailwindcss';
import twAdminConfig from './config/tailwind.admin.config.mjs';
import fs from 'fs';
import path from 'path';
import crypto from 'crypto';

const writeFile = promisify(fs.writeFile);

gulp.task('sass', async function () {
  try {
    const scssDir = './assets/sass';
    const outputDir = './resources/css';
    const files = fs.readdirSync(scssDir);

    const scssFiles = files.filter(file => path.extname(file) === '.scss' && file !== 'tailwind.admin.scss');

    for (const file of scssFiles) {
      const inputPath = path.join(scssDir, file);
      const outputPath = path.join(outputDir, path.basename(file, '.scss') + '.css');

      const result = await compileAsync(inputPath, {
        loadPaths: [scssDir],
      });

      await writeFile(outputPath, result.css);
      console.log(`Compiled ${file} to ${outputPath}`);
    }
  } catch (error) {
    console.error('Error compiling Sass:', error);
  }
});

gulp.task('tailwind-admin', async function () {
  try {
    const inputPath = './assets/sass/tailwind.admin.scss';
    const outputPath = './resources/css/tailwind.admin.css';

    const result = await compileAsync(inputPath, {
      loadPaths: ['./assets/sass'],
      importer: nodeSassImporter,
    });

    const processedCss = await postcss([tailwindcss(twAdminConfig), autoprefixer()]).process(result.css, {
      from: inputPath,
      to: outputPath,
    });

    await writeFile(outputPath, processedCss.css);
    console.log(`Compiled and processed Tailwind Admin SCSS to ${outputPath}`);
  } catch (error) {
    console.error('Error compiling Tailwind Admin:', error);
  }
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

gulp.task('js-admin', function () {
  return gulp
    .src([
      './assets/js/tools.js',
      './assets/js/admin/index.js',
      './assets/js/admin/buttons.js',
      './assets/js/admin/navi.js',
      './assets/js/admin/keypad.js',
      './assets/js/admin/imageSelect.js',
      './assets/js/admin/fontSelect.js',
      './assets/js/admin/videoSelect.js',
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
