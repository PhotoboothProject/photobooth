import gulp from 'gulp';
import sass from 'gulp-dart-sass';
import babel from 'gulp-babel';

const { series, parallel, watch } = gulp;

gulp.task('sass', function () {
  return gulp
    .src('./src/sass/**/*.scss')
    .pipe(sass().on('error', sass.logError)) // Use sass() without .sync
    .pipe(gulp.dest('./resources/css'));
});

gulp.task('js', function () {
  return gulp
    .src('./src/js/**/*.js')
    .pipe(babel({ presets: ['@babel/env'], ignore: [ 'src/js/sync-to-drive.js', 'src/js/remotebuzzer_server.js' ] }))
    .pipe(gulp.dest('./resources/js'));
});

gulp.task('default', parallel('sass', 'js'));