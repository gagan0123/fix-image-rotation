import { execSync } from 'child_process';
import fs from 'fs';

// Convert readme.txt to README.md using wp-readme-to-markdown CLI
execSync(
	'npx wp-readme-to-md --readme readme.txt --md README.md --screenshot-url "https://github.com/gagan0123/fix-image-rotation/raw/master/assets/{screenshot}.png"',
	{ stdio: 'inherit' }
);

// Prepend plugin icon
const content = fs.readFileSync( 'README.md', 'utf8' );
const withIcon = "<img src='https://github.com/gagan0123/fix-image-rotation/raw/master/assets/icon-128x128.png' align='right' />\n\n" + content;
fs.writeFileSync( 'README.md', withIcon );

console.log( 'Icon prepended to README.md' );
