"use strict";

const gulp = require('gulp');
const autoprefixer = require('gulp-autoprefixer');
const csso = require('gulp-csso');
const rename = require("gulp-rename");
const plumber = require('gulp-plumber');
const terser = require('gulp-terser');
const wpPot = require('gulp-wp-pot');
const gulpZip = require('gulp-zip');


/*== Paths to source/build/watch files ==*/
let path = {
    build: {
        js: 'assets/js/',
        css: 'assets/css/',
        img: 'assets/img/',
    },
    src: {
        js: 'src/js/',
        css: 'src/css/',
        img: 'src/img/',
    },
    watch: {
        js: 'src/js/',
        css: 'src/css/',
        img: 'src/img/',
    }
};

/* Compile CSS */
const styles = (done) => {
    gulp.src(path.src.css + '*.css')
        .pipe(plumber())
        .pipe(autoprefixer())
        .pipe(rename({suffix: '.min'}))
        .pipe(csso())
        .pipe(gulp.dest(path.build.css));
    done();
}
exports.styles = styles;

/* Compile JS */
const scripts = (done) => {
    gulp.src(path.src.js + '*.js')
        .pipe(plumber())
        .pipe(rename({suffix: '.min'}))
        .pipe(terser())
        .pipe(gulp.dest(path.build.js));
    done();
}
exports.scripts = scripts;

/* Compile POT */
const pot = (done) => {
    gulp.src(['inc/*.php', 'sendpulse-newsletter.php'])
        .pipe(plumber())
        .pipe(wpPot({
            domain: 'sendpulse-email-marketing-newsletter',
            package: 'SendPulse Email Marketing Newsletter'
        }))
        .pipe(gulp.dest('languages/sendpulse-email-marketing-newsletter.pot'));
    done();
}
exports.pot = pot;

/*  SVN */
const svn = (done) => {
    gulp.src(['**/*', '!node_modules', '!node_modules/**'], {base: "."})
        .pipe(gulp.dest('../../svn/sendpulse-email-marketing-newsletter/trunk'));
    done();
}
exports.svn = svn;

/*  ZIP */
const zip = (done) => {
    gulp.src(['../sendpulse-email-marketing-newsletter/**/*', '!node_modules', '!node_modules/**', '!tests', '!tests/**', '!.travis.yml', '!phpcs.ruleset.xml', '!phpunit.xml.dist', '!bin', '!bin/**'], {base: "../"})
        .pipe(gulpZip('sendpulse-email-marketing-newsletter.zip'))
        .pipe(gulp.dest('../../dist'));
    done();
}
exports.zip = zip;

/*  IMAGE */
const image = (done) => {
    gulp.src(path.src.img + '*.*')
        .pipe(gulp.dest(path.build.img));
    done();
}
exports.image = image;

/* Watcher */
const watch = () => {
    gulp.watch(path.watch.css + '*.css', styles);
    gulp.watch(path.watch.js + '*.js', scripts);
    gulp.watch(path.watch.img + '*.*', image);
}
exports.watch = watch;

const prod = gulp.parallel(styles, scripts, pot, image);
exports.prod = prod;