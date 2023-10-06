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

const { parallel } = gulp;

gulp.task('sass', function () {
  const twFilter = filters(['**/*', '!tailwind.admin.scss']);

  return gulp
    .src('./src/sass/**/*.scss')
    .pipe(twFilter)
    .pipe(sass().on('error', sass.logError)) // Use sass() without .sync
    .pipe(gulp.dest('./resources/css'));
});

gulp.task('js', function () {
  return gulp
    .src('./src/js/**/*.js')
    .pipe(babel({
      presets: ['@babel/env'],
      ignore: ['src/js/sync-to-drive.js', 'src/js/remotebuzzer_server.js']
    }))
    .pipe(gulp.dest('./resources/js'));
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
    .pipe(babel({
      presets: ['@babel/env'],
      ignore: ['src/js/sync-to-drive.js', 'src/js/remotebuzzer_server.js']
    }))
    .pipe(gulp.dest('./resources/js'));
});

gulp.task('default', parallel('sass', 'js', 'js-admin', 'tailwind-admin'));
