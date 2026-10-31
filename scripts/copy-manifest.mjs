import { copyFileSync, existsSync, mkdirSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const src = join(root, 'public/build/.vite/manifest.json')
const dest = join(root, 'public/build/manifest.json')

if (!existsSync(src)) {
  console.error('manifest introuvable:', src)
  process.exit(1)
}

mkdirSync(dirname(dest), { recursive: true })
copyFileSync(src, dest)
console.log('manifest → public/build/manifest.json')
