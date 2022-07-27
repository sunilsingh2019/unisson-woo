/*
 * @title Scripts
 * @description A task to concatenate and compress js files via webpack.
 */

// Dependencies
import { src, dest, series } from 'gulp';
import uglify from 'gulp-uglify';

// Config
import { paths } from "../config";

// Task
export function esTranspile() {
    return src([
      paths.scripts.src
      ])
      .pipe(uglify())
      .pipe(dest(paths.scripts.dest)
    );

}

export const scripts = series(esTranspile);
