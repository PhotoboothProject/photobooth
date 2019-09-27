'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');

gulp.task('sass', function () {
  return gulp.src('./resources/sass/**/*.scss')
    .pipe(sass.sync().on('error', sass.logError))
    .pipe(gulp.dest('./resources/css'));
});

gulp.task('sass:watch', function () {
  gulp.watch('./resources/sass/**/*.scss', gulp.series('sass'));
});
