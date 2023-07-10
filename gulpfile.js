'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass')(require('sass'));
var babel = require('gulp-babel');
var php = require('gulp-connect-php');
var browserSync = require('browser-sync').create();
var filters = require('gulp-filter');

// tw
const twConfig = require("./config/tailwind.config");
const twAdminConfig = require("./config/tailwind.admin.config");
const postcss = require("gulp-postcss");
const tailwindcss = require("tailwindcss");
const rename = require("gulp-rename");
const autoprefixer = require("autoprefixer");
const concat = require("gulp-concat");
const nodeSassImporter = require('node-sass-importer');

gulp.task('dev', function() {
  php.server({
    base:'./',
    port: 3000,
    keepalive: true
  });

  browserSync.init({
    proxy: '127.0.0.1:3000'
  }); 

  // watch
  gulp
    .watch('./src/sass/**/*.scss', gulp.series('sass'))
    .on('change', browserSync.reload);
  gulp
    .watch('./**/*.php')
    .on('change', browserSync.reload);
  gulp
    .watch('./src/js/*.js', gulp.series('js'))
    .on('change', browserSync.reload);

  // tailwind
  gulp
    .watch([
      './index.php',
      './gallery/**/*.php',
      './template/**/*.php',
      './src/sass/tailwind.scss',
    ], gulp.series('tailwind'))
    .on('change', browserSync.reload);

  // admin
  gulp
  .watch('./src/js/admin/**/*.js', gulp.series('js-admin'))
  .on('change', browserSync.reload);
  gulp
    .watch([
      './admin/**/*.php',
      './login/**/*.php',
      './manual/**/*.php',
      './welcome/**/*.php',
      './src/sass/tailwind.admin.scss',
    ], gulp.series('tailwind-admin'))
    .on('change', browserSync.reload);
});


gulp.task('sass', function () {
  var twFilter = filters(['**/*', '!tailwind.admin.scss', '!tailwind.scss']);

  return gulp
    .src('./src/sass/**/*.scss')
    .pipe(twFilter)
    .pipe(sass.sync().on('error', sass.logError))
    .pipe(gulp.dest('./resources/css'));
});

gulp.task('js', function () {
  return gulp
    .src('./src/js/**/*.js')
    .pipe(babel({ presets: ['@babel/env'], ignore: [ 'src/js/sync-to-drive.js', 'src/js/remotebuzzer_server.js' ] }))
    .pipe(gulp.dest('./resources/js'));
});

gulp.task('watch', function () {
  gulp.watch('./src/sass/**/*.scss', gulp.series('sass'));
  gulp.watch('./src/js/*.js', gulp.series('js'));
});



gulp.task('tailwind', function () {
  var plugins = [
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
  var plugins = [
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
      './src/js/admin/index.js',
      './src/js/tools.js',
      './src/js/i18n.js',
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


gulp.task('default', gulp.parallel('sass', 'js', 'js-admin', 'tailwind', 'tailwind-admin'));