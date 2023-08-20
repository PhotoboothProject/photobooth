import gulp from 'gulp';
import sass from 'gulp-dart-sass';
import babel from 'gulp-babel';
import php from 'gulp-connect-php';
import browserSync from 'browser-sync';
import filters from 'gulp-filter';
import twConfig from './config/tailwind.config.mjs';
import twAdminConfig from './config/tailwind.admin.config.mjs';
import postcss from 'gulp-postcss';
import tailwindcss from 'tailwindcss';
import rename from 'gulp-rename';
import autoprefixer from 'autoprefixer';
import concat from 'gulp-concat';
import nodeSassImporter from 'node-sass-importer';

const { series, parallel, watch } = gulp;
const { create } = browserSync;

gulp.task('sass', function () {
  const twFilter = filters(['**/*', '!tailwind.admin.scss', '!tailwind.scss']);

  return gulp
    .src('./src/sass/**/*.scss')
    .pipe(twFilter)
    .pipe(sass().on('error', sass.logError)) // Use sass() without .sync
    .pipe(gulp.dest('./resources/css'));
});

gulp.task('js', function () {
  return gulp
    .src('./src/js/**/*.js')
    .pipe(babel({ presets: ['@babel/env'], ignore: [ 'src/js/sync-to-drive.js', 'src/js/remotebuzzer_server.js' ] }))
    .pipe(gulp.dest('./resources/js'));
});

gulp.task('tailwind', function () {
  const plugins = [
    tailwindcss(twConfig),
    autoprefixer(),
  ];
  return gulp
    .src('./src/sass/tailwind.scss')
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

gulp.task('tailwind-admin', function () {
  const plugins = [
    tailwindcss(twAdminConfig),
    autoprefixer(),
  ];
  return gulp
    .src('./src/sass/tailwind.admin.scss')
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
      './src/js/tools.js',
      './src/js/admin/index.js',
      './src/js/admin/buttons.js',
      './src/js/admin/navi.js',
      './src/js/admin/keypad.js',
      './src/js/admin/imageSelect.js',
      './src/js/admin/toast.js',
    ])
    .pipe(concat('main.admin.js'))
    .pipe(babel({ presets: ['@babel/env'], ignore: [ 'src/js/sync-to-drive.js', 'src/js/remotebuzzer_server.js' ] }))
    .pipe(gulp.dest('./resources/js'));
});

gulp.task('dev', function () {
  php.server({
    base: './',
    port: 3000,
    keepalive: true
  });

  browserSync.init({
    proxy: '127.0.0.1:3000'
  });

  watch('./src/sass/**/*.scss', series('sass'))
    .on('change', browserSync.reload);
  watch('./**/*.php')
    .on('change', browserSync.reload);
  watch('./src/js/*.js', series('js'))
    .on('change', browserSync.reload);

  watch([
    './index.php',
    './gallery/**/*.php',
    './template/**/*.php',
    './src/sass/tailwind.scss'
  ], series('tailwind'))
    .on('change', browserSync.reload);

  watch('./src/js/admin/**/*.js', series('js-admin'))
    .on('change', browserSync.reload);
  watch([
    './admin/**/*.php',
    './login/**/*.php',
    './manual/**/*.php',
    './welcome/**/*.php',
    './src/sass/tailwind.admin.scss'
  ], series('tailwind-admin'))
    .on('change', browserSync.reload);
});

gulp.task('default', parallel('sass', 'js', 'js-admin', 'tailwind', 'tailwind-admin'));

