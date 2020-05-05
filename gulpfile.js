const gulp = require('gulp');
const runSequence = require('gulp4-run-sequence');
// notification
const notify = require('gulp-notify');
const plumber = require('gulp-plumber');
// Sass
const sass = require('gulp-sass');
sass.compiler = require('node-sass');
const sourcemaps = require('gulp-sourcemaps');
const cache = require('gulp-cached');
var progeny = require('gulp-progeny');
// css sorting and autoprefixer
const postcss = require('gulp-postcss');
const sorting = require('postcss-sorting');
const autoprefixer = require('autoprefixer');

// BrowserSync
const browserSync = require('browser-sync').create();
// switch Env dev or live
const gulpIf = require('gulp-if');
const minimist = require('minimist');
const rev = require('gulp-rev');
const revDistClean = require('gulp-rev-dist-clean');
const del = require('del');
// Path
const basePath = './view/theme/'
const path = {
	'desktopScss': 'desktop-fastor/scss/',
	'mobileScss': 'mobile-fastor/scss/',
	'desktopCss': 'desktop-fastor/css/',
	'mobileCss': 'mobile-fastor/css/',
	'desktopImg': 'desktop-fastor/img/',
	'mobileImg': 'mobile-fastor/img/',
	'desktopTs': 'desktop-fastor/ts/',
	'mobileTs': 'mobile-fastor/ts/',
	'desktopJs': 'desktop-fastor/js/',
	'mobileJs': 'mobile-fastor/js/',	
	'sourcemap': '.', // root is /css
}
// const externalBasePath = './view/'
// const externalPath = {
// 	'externalJs' : 'javascript/',
// }
/* Options */
const postcssOption = {
	'order': [
		"custom-properties",
		"dollar-variables",
		"declarations",
		"rules",
		"at-rules"
	],
	'properties-order': 'alphabetical',
	'unspecified-properties-position': 'bottom'
}
const autoprefixerOption = {
	browsers: ['last 2 versions']
}
const sassOutputStyle = {
	'nested': 'nested',
	'expanded': 'expanded',
	'compact': 'compact',
	'compressed': 'compressed'
}
/* /Options */

// for check Env dev or live
const envSettings = {
	string: 'env',
	default: {
		env: process.env.NODE_ENV || 'dev'
	}
}
const options = minimist(process.argv.slice(2), envSettings);
const live = options.env === 'live';
const config = {
	envLive: live
}

// sass-compile desktop
gulp.task('sass-compile-desktop', (done) => {
	gulp.src(basePath + path.desktopScss + '*.scss')
		.pipe(cache('sass-compile-desktop'))
		.pipe(progeny())
		.pipe(sourcemaps.init())
		.pipe(plumber({
			errorHandler: notify.onError("Error: <%= error.message %>")
		}))
		.pipe(sass({
			outputStyle: gulpIf(config.envLive, sassOutputStyle.compressed, sassOutputStyle.expanded)
		}))
		.pipe(postcss([autoprefixer(autoprefixerOption)]))
		.pipe(postcss([sorting(postcssOption)]))
		.pipe(gulpIf(!config.envLive, sourcemaps.write(path.sourcemap)))
		.pipe(gulp.dest(basePath + path.desktopCss))
		.on('error', function (err) {
			console.log(err.message);
		})
		.pipe(browserSync.reload({
			stream: true
    }));
  done();
});

// sass-compile mobile
gulp.task('sass-compile-mobile', (done) => {
	gulp.src(basePath + path.mobileScss + '*.scss')
		.pipe(cache('sass-compile-mobile'))
		.pipe(progeny())
		.pipe(sourcemaps.init())
		.pipe(plumber({
			errorHandler: notify.onError("Error: <%= error.message %>")
		}))
		.pipe(sass({
			outputStyle: gulpIf(config.envLive, sassOutputStyle.compressed, sassOutputStyle.expanded)
		}))
		.pipe(postcss([autoprefixer(autoprefixerOption)]))
		.pipe(postcss([sorting(postcssOption)]))
		.pipe(gulpIf(!config.envLive, sourcemaps.write(path.sourcemap)))
		.pipe(gulp.dest(basePath + path.mobileCss))
		.on('error', function (err) {
			console.log(err.message);
		})
    .pipe(browserSync.stream());
  done();
});


gulp.task('generate_build_number', function(callback) {
	runSequence('versioning-css-js',	
				'rev-dist-clean',
				callback);
});
  
  gulp.task('versioning-css-js', function() {	  
	  return gulp.src([
		basePath + path.desktopCss + '*.css',
		basePath + path.desktopCss + 'blog/*.css',
		basePath + path.desktopCss + 'discountonleave/*.css',
		basePath + path.desktopCss + 'reviewchart/*.css',
		basePath + path.desktopCss + 'smartnotifications/*.css',
		basePath + path.desktopJs + '*.js',
		basePath + path.desktopJs + 'reviewchart/*.js',
		basePath + path.desktopJs + 'smartnotifications/*.js',
		basePath + path.mobileCss + '*.css',
		basePath + path.mobileCss + 'blog/*.css',
		basePath + path.mobileCss + 'discountonleave/*.css',
		basePath + path.mobileCss + 'reviewchart/*.css',
		basePath + path.mobileCss + 'smartnotifications/*.css',
		basePath + path.mobileJs + '*.js',
		basePath + path.mobileJs + 'reviewchart/*.js',			
		basePath + path.mobileJs + 'smartnotifications/*.js',
	],  {base: basePath } )
	.pipe(rev())
	.pipe(gulp.dest(basePath + 'build/' )) 		
	.pipe(rev.manifest())
	.pipe(gulp.dest(basePath + 'build/'))	
	.on('error', function (err) {
		console.log(err.message);
	})
  });
  
  gulp.task('rev-dist-clean', function() {	
		return gulp.src([
		 basePath + 'build/**/**/*',
		], {read: false})
		.pipe(revDistClean(basePath + 'build/rev-manifest.json'), {keepRenamedFiles: false})		
		.on('error', function (err) {
			console.log(err.message);
		})		
  });

gulp.task('del-manifest-file', (done) => {
	if(!config.envLive){
		// gulp.src([
		// 	basePath + 'build/**/**/*',
		// ], {read: false})
		// .pipe(revDistClean(basePath + 'build/rev-manifest.json'), {keepRenamedFiles: false})
		del(basePath + 'build/rev-manifest.json')
	}		
	done();	
});


gulp.task('watch-sass-compile-desktop', (done) => {
	const watcher = gulp.watch(basePath + path.desktopScss + '*.scss');
	watcher.on('change', function (path,stats) {
    console.log('File ' + path + ' was change, status: ' + stats + ', running tasks...');
    runSequence('sass-compile-desktop');
  });
  done();
});

gulp.task('watch-sass-compile-mobile', (done) => {
	const watcher = gulp.watch(basePath + path.mobileScss + '*.scss');
	watcher.on('change', function (path,stats) {
    console.log('File ' + path + ' was change, status: ' + stats + ', running tasks...');
		runSequence('sass-compile-mobile')
  });
  done();
});

gulp.task('watch-css-js', (done) => {
	const watcher = gulp.watch(basePath + path.desktopCss + '*.css');
	watcher.on('change', function (path,stats) {
    console.log('File ' + path + ' was change, status: ' + stats + ', running tasks...');
    runSequence('versioning-css-js');
  });
  done();
});

gulp.task('default', function (callback) {
		return runSequence(
			'sass-compile-desktop',
			'sass-compile-mobile',
			'watch-sass-compile-desktop',
			'watch-sass-compile-mobile',		
			'generate_build_number',
			'del-manifest-file',		
			callback
		);
});

