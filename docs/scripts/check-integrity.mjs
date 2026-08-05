import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs'
import { dirname, join, extname } from 'node:path'
import { fileURLToPath } from 'node:url'

const scriptsDir = dirname(fileURLToPath(import.meta.url))
const docsDir = join(scriptsDir, '..')
const pkgDir = join(docsDir, '..')

const errors = []
const fail = (message) => errors.push(message)

// 1. README.md and README.ru.md must exist and be non-trivial.
for (const readme of ['README.md', 'README.ru.md']) {
    const path = join(pkgDir, readme)
    if (!existsSync(path)) {
        fail(`${readme} is missing.`)
        continue
    }
    if (readFileSync(path, 'utf8').trim().length < 200) {
        fail(`${readme} is suspiciously short (< 200 chars) — looks empty or truncated.`)
    }
}

// 2. Every @api class from the reflection snapshot must have a generated
//    api/classes page in both locales, and every public method/property name
//    must actually appear on that page (catches a generator regression that
//    silently drops members, not just a missing file).
const snapshot = JSON.parse(readFileSync(join(scriptsDir, 'api-snapshot.json'), 'utf8'))
const apiClasses = snapshot.filter((entry) => entry.isApi)

function shortName(className) {
    const parts = className.split('\\')
    return parts[parts.length - 1]
}

for (const entry of apiClasses) {
    const name = shortName(entry.class)
    for (const lang of ['en', 'ru']) {
        const pagePath = join(docsDir, lang, 'api', 'classes', `${name}.md`)
        if (!existsSync(pagePath)) {
            fail(`Missing generated API page for @api class "${entry.class}": ${lang}/api/classes/${name}.md`)
            continue
        }
        const content = readFileSync(pagePath, 'utf8')
        for (const method of entry.publicMethods) {
            if (!content.includes(method.name)) {
                fail(`API page ${lang}/api/classes/${name}.md is missing method "${method.name}" from the reflection snapshot.`)
            }
        }
        for (const prop of entry.publicProperties) {
            if (!content.includes(prop.name)) {
                fail(`API page ${lang}/api/classes/${name}.md is missing property "${prop.name}" from the reflection snapshot.`)
            }
        }
    }
}

// 3. No generated page for a public-but-non-@api class (e.g. AssumptionSkipped)
//    — the reference must filter by the @api tag, never leak internals.
const apiShortNames = new Set(apiClasses.map((entry) => shortName(entry.class)))
for (const lang of ['en', 'ru']) {
    const classesDir = join(docsDir, lang, 'api', 'classes')
    if (!existsSync(classesDir)) continue
    for (const file of readdirSync(classesDir)) {
        const name = file.replace(/\.md$/, '')
        if (!apiShortNames.has(name)) {
            fail(`${lang}/api/classes/${file} has no corresponding @api entry in the reflection snapshot — a non-@api or removed class leaked into the reference.`)
        }
    }
}

// 4. Every internal link in the sidebar/nav config, and every internal link
//    inside a page's own markdown, must resolve to a file on disk.
function collectMarkdownFiles(dir) {
    const results = []
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
        const path = join(dir, entry.name)
        if (entry.isDirectory()) {
            results.push(...collectMarkdownFiles(path))
        } else if (extname(entry.name) === '.md') {
            results.push(path)
        }
    }
    return results
}

function resolveLink(link) {
    // Strip a query/hash suffix; VitePress `cleanUrls` links omit `.md`.
    const clean = link.split('#')[0].split('?')[0]
    if (clean === '' || clean === '/') return true
    const withoutLeadingSlash = clean.replace(/^\//, '')
    const candidates = [
        join(docsDir, withoutLeadingSlash + '.md'),
        join(docsDir, withoutLeadingSlash, 'index.md'),
    ]
    return candidates.some((path) => existsSync(path) && statSync(path).isFile())
}

const configPath = join(docsDir, '.vitepress', 'config.ts')
const configSource = readFileSync(configPath, 'utf8')
const linkPattern = /link:\s*'([^']+)'/g
for (const match of configSource.matchAll(linkPattern)) {
    const link = match[1]
    if (/^https?:\/\//.test(link)) continue
    if (!resolveLink(link)) {
        fail(`config.ts references a link that does not resolve to a file: "${link}"`)
    }
}

const markdownLinkPattern = /\]\((\/(?:en|ru)\/[^)#\s]+)(#[^)\s]*)?\)/g
// Raw HTML anchors like <a href="/en/..."> bypass VitePress's `base` rewriting
// (only markdown links and Vue Router links get the base prefix), so they point
// at the domain root on a project-Pages site. Internal links must be markdown.
const rawHtmlAnchorPattern = /<a\s[^>]*href="\/(?!\/)[^"]*"/i
for (const lang of ['en', 'ru']) {
    for (const file of collectMarkdownFiles(join(docsDir, lang))) {
        const content = readFileSync(file, 'utf8')
        if (rawHtmlAnchorPattern.test(content)) {
            const lineNum = content.split('\n').findIndex((l) => rawHtmlAnchorPattern.test(l)) + 1
            fail(`${file.replace(docsDir + '/', '')}:${lineNum} uses a raw HTML <a href="/..."> internal link — VitePress does not apply \`base\` to it. Use a markdown link instead.`)
        }
        for (const match of content.matchAll(markdownLinkPattern)) {
            const link = match[1]
            if (!resolveLink(link)) {
                fail(`${file.replace(docsDir + '/', '')} links to "${link}", which does not resolve to a file.`)
            }
        }
    }
}
// The root language picker uses relative <a href="./en/"> (browser-resolved),
// so it is exempt — but an absolute <a href="/en/"> there is still wrong.
const rootIndex = join(docsDir, 'index.md')
if (existsSync(rootIndex)) {
    const rootContent = readFileSync(rootIndex, 'utf8')
    if (/<a\s[^>]*href="\/(?:en|ru)\//i.test(rootContent)) {
        fail('docs/index.md uses an absolute <a href="/en|/ru/..."> link — use a relative "./en/" form so the browser resolves it under the site base.')
    }
}

// 5. Every @api class name should be mentioned somewhere in llms.txt — a weak
//    but cheap proxy for "the compact LLM reference wasn't left behind when
//    the public API grew."
const llms = readFileSync(join(pkgDir, 'llms.txt'), 'utf8')
for (const entry of apiClasses) {
    const name = shortName(entry.class)
    if (!llms.includes(name)) {
        fail(`llms.txt does not mention "@api" class "${name}" — update llms.txt when the public API changes.`)
    }
}

if (errors.length > 0) {
    console.error(`docs integrity check found ${errors.length} problem(s):\n`)
    for (const error of errors) {
        console.error(`  - ${error}`)
    }
    process.exit(1)
}

console.log(`docs integrity check passed: ${apiClasses.length} @api classes, all links resolve.`)
