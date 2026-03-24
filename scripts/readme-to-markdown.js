/**
 * Converts WordPress readme.txt to GitHub README.md.
 *
 * Replaces the grunt-wp-readme-to-markdown task with a standalone script.
 * Handles screenshot URL rewriting and prepends the plugin icon.
 */

const fs = require( 'fs' );
const path = require( 'path' );

const pkg = JSON.parse(
	fs.readFileSync( path.join( __dirname, '..', 'package.json' ), 'utf8' )
);

const readmePath = path.join( __dirname, '..', 'readme.txt' );
const outputPath = path.join( __dirname, '..', 'README.md' );

let content = fs.readFileSync( readmePath, 'utf8' );

// Convert WordPress readme headers to markdown.
// Plugin name.
content = content.replace( /^=== (.+?) ===/m, '# $1' );

// Section headers (== to ##, = to ###).
content = content.replace( /^== (.+?) ==/gm, '## $1' );
content = content.replace( /^= (.+?) =/gm, '### $1' );

// Replace screenshot references with full URLs.
const screenshotUrl =
	pkg.repository.url + '/raw/master/assets/{screenshot}.png';
content = content.replace(
	/\(screenshot-(\d+)\)/g,
	( match, num ) =>
		'(' + screenshotUrl.replace( '{screenshot}', 'screenshot-' + num ) + ')'
);

// Prepend the plugin icon.
const iconUrl = pkg.repository.url + '/raw/master/assets/icon-128x128.png';
content = "<img src='" + iconUrl + "' align='right' />\n\n" + content;

fs.writeFileSync( outputPath, content );

// eslint-disable-next-line no-console
console.log( 'README.md generated from readme.txt' );
