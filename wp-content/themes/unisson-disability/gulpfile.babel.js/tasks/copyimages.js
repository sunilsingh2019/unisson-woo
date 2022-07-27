import { src, dest } from 'gulp';
import changed from "gulp-changed";

// Config
import { paths } from "../config";

// Task
export function copyimages() {
  return src(paths.copyimages.src)
    .pipe(changed(paths.copyimages.dest))
    .pipe(dest(paths.copyimages.dest))
}
