'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass')(require('sass'));
var babel = require('gulp-babel');

gulp.task('sass', function () {
  return gulp
    .src('./src/sass/**/*.scss')
    .pipe(sass.sync().on('error', sass.logError))
    .pipe(gulp.dest('./resources/css'));
});

gulp.task('js', function () {
  return gulp
    .src('./src/js/**/*.js')
    .pipe(babel({ presets: ['@babel/env'], ignore: [ "src/js/sync-to-drive.js", "src/js/remotebuzzer_server.js" ] }))
    .pipe(gulp.dest('./resources/js'));
});

gulp.task('watch', function () {
  gulp.watch('./src/sass/**/*.scss', gulp.series('sass'));
  gulp.watch('./src/js/*.js', gulp.series('js'));
});

gulp.task('default', gulp.parallel('sass', 'js'));
