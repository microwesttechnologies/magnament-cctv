import { deflateSync } from 'node:zlib';
import { writeFileSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

function crc32(buffer) {
    let crc = 0xffffffff;
    for (const byte of buffer) {
        crc ^= byte;
        for (let i = 0; i < 8; i += 1) {
            crc = (crc >>> 1) ^ (crc & 1 ? 0xedb88320 : 0);
        }
    }
    return (crc ^ 0xffffffff) >>> 0;
}

function chunk(type, data) {
    const typeBuffer = Buffer.from(type, 'ascii');
    const length = Buffer.alloc(4);
    length.writeUInt32BE(data.length);
    const crcBuffer = Buffer.alloc(4);
    crcBuffer.writeUInt32BE(crc32(Buffer.concat([typeBuffer, data])));
    return Buffer.concat([length, typeBuffer, data, crcBuffer]);
}

function png(size, background, accent) {
    const raw = Buffer.alloc((size * 3 + 1) * size);
    const cx = (size - 1) / 2;
    const outer = size * 0.32;
    const inner = size * 0.18;

    for (let y = 0; y < size; y += 1) {
        const row = y * (size * 3 + 1);
        raw[row] = 0;
        for (let x = 0; x < size; x += 1) {
            const dx = x - cx;
            const dy = y - cx;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const color = dist <= inner || (dist <= outer && dist >= outer - size * 0.06)
                ? accent
                : background;
            const offset = row + 1 + x * 3;
            raw[offset] = color[0];
            raw[offset + 1] = color[1];
            raw[offset + 2] = color[2];
        }
    }

    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(size, 0);
    ihdr.writeUInt32BE(size, 4);
    ihdr[8] = 8;
    ihdr[9] = 2;

    return Buffer.concat([
        Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]),
        chunk('IHDR', ihdr),
        chunk('IDAT', deflateSync(raw)),
        chunk('IEND', Buffer.alloc(0)),
    ]);
}

const dir = dirname(fileURLToPath(import.meta.url));
const outDir = `${dir}/../public/images/pwa`;
mkdirSync(outDir, { recursive: true });

const background = [15, 23, 42];
const accent = [56, 189, 248];

writeFileSync(`${outDir}/icon-192.png`, png(192, background, accent));
writeFileSync(`${outDir}/icon-512.png`, png(512, background, accent));
console.log('PWA icons written');
