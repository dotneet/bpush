var glob = require('glob');
var gulp = require('gulp');
var sass = require('gulp-sass');
var closureCompiler = require('gulp-closure-compiler');

gulp.task('sass', function() {
  gulp.src('./private/sass/**/*.scss')
      .pipe(sass().on('error', sass.logError))
      .pipe(gulp.dest('./public/css'))
});

gulp.task('sass:watch', function() {
  gulp.watch('./private/sass/**/*.scss', ['sass']);
});

gulp.task('closure', function() {
  var compilerPath = glob.sync('public/bower_components/closure-compiler/*compiler*.jar')[0];
  console.log('jar:' + compilerPath);
  gulp.src('./private/js/service-worker.js')
      .pipe(closureCompiler({
        compilerPath: compilerPath,
        fileName: 'service-worker.js',
        compilerFlags: {
          language_in: "ECMASCRIPT6",
          language_out: "ECMASCRIPT5"
        }
      }))
      .pipe(gulp.dest('public/js'));
  gulp.src('./private/js/swlib.js')
      .pipe(closureCompiler({
        compilerPath: compilerPath,
        fileName: 'swlib.js',
        compilerFlags: {
          language_in: "ECMASCRIPT6",
          language_out: "ECMASCRIPT5"
        }
      }))
      .pipe(gulp.dest('public/js'));
});


gulp.task('closure:watch', function() {
  gulp.watch('./private/js/*.js', ['closure']);
});

gulp.task('watch', ['sass:watch', 'closure:watch'], function() {
});

