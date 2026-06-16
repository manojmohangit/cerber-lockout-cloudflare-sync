const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const pluginFilePath = path.join(__dirname, '../src/cerber-lockout-cloudflare-sync.php');
if (!fs.existsSync(pluginFilePath)) {
	console.error('Error: Main plugin file not found!');
	process.exit(1);
}

const content = fs.readFileSync(pluginFilePath, 'utf8');
const versionMatch = content.match(/Version:\s*([0-9.]+)/i);
const version = versionMatch ? versionMatch[1] : '1.0.0';

console.log(`Detected Plugin Version: ${version}`);

const distDir = path.join(__dirname, '../dist');
if (!fs.existsSync(distDir)) {
	fs.mkdirSync(distDir, { recursive: true });
}

const outputZipPath = path.join(distDir, `cerber-lockout-cloudflare-sync-v${version}.zip`);
const output = fs.createWriteStream(outputZipPath);
const archive = archiver('zip', {
	zlib: { level: 9 }
});

output.on('close', () => {
	console.log(`Plugin ZIP created successfully: ${path.basename(outputZipPath)} (${archive.pointer()} total bytes)`);
});

archive.on('warning', (err) => {
	if (err.code === 'ENOENT') {
		console.warn('Archive Warning:', err);
	} else {
		throw err;
	}
});

archive.on('error', (err) => {
	console.error('Archive Error:', err);
	process.exit(1);
});

archive.pipe(output);

const srcDir = path.join(__dirname, '../src');
archive.directory(srcDir, 'cerber-lockout-cloudflare-sync');

archive.finalize();
