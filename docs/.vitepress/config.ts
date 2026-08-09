import { defineConfig } from 'vitepress'

const enSidebar = [
    {
        text: 'Intro',
        items: [
            { text: 'What is property-testing', link: '/intro/what-is-property-testing' },
            { text: 'Getting started', link: '/intro/getting-started' },
            { text: 'Concepts', link: '/intro/concepts' },
        ],
    },
    { text: 'Shrinking', link: '/shrinking' },
    {
        text: 'Generators',
        items: [
            { text: 'Overview', link: '/generators/index' },
            { text: 'Boundary bias', link: '/generators/boundary-bias' },
            { text: 'Dependent generators (flatMap vs draw)', link: '/generators/dependent' },
            { text: 'Custom arbitrary', link: '/generators/custom-arbitrary' },
        ],
    },
    {
        text: 'Controlling runs',
        items: [
            { text: 'Assume vs filter', link: '/controlling-runs/assume-vs-filter' },
            { text: 'Bounding shrink work', link: '/controlling-runs/bounding-shrink' },
            { text: 'Deadlines', link: '/controlling-runs/deadlines' },
            { text: 'Environment overrides', link: '/controlling-runs/env-overrides' },
        ],
    },
    { text: 'Regression corpus', link: '/regression-corpus' },
    { text: 'Explicit examples', link: '/explicit-examples' },
    { text: 'Distribution', link: '/distribution' },
    { text: 'Sampling & export', link: '/sampling-and-export' },
    {
        text: 'State machine',
        items: [
            { text: 'Concepts', link: '/state-machine/concepts' },
            { text: 'Shrinking', link: '/state-machine/shrinking' },
        ],
    },
    { text: 'Recipes', link: '/recipes' },
    { text: 'Security', link: '/security' },
    { text: 'Examples', link: '/examples' },
    {
        text: 'Cookbook',
        items: [
            { text: 'Your first property', link: '/cookbook/first-property' },
            { text: 'Reproducing with a seed', link: '/cookbook/reproducing-with-seed' },
            { text: 'CI recipes', link: '/cookbook/ci-recipes' },
            { text: 'Writing a state machine test', link: '/cookbook/writing-a-state-machine' },
        ],
    },
    {
        text: 'API',
        items: [
            { text: 'Overview', link: '/api/index' },
            { text: 'Exceptions', link: '/api/exceptions' },
        ],
    },
    { text: 'Roadmap', link: '/roadmap' },
    { text: 'Migrating from 1.x', link: '/migrating-from-1x' },
    { text: 'llms.txt reference', link: '/llms' },
]

const ruSidebar = [
    {
        text: 'Введение',
        items: [
            { text: 'Что такое property-testing', link: '/ru/intro/what-is-property-testing' },
            { text: 'Быстрый старт', link: '/ru/intro/getting-started' },
            { text: 'Концепции', link: '/ru/intro/concepts' },
        ],
    },
    { text: 'Shrinking', link: '/ru/shrinking' },
    {
        text: 'Генераторы',
        items: [
            { text: 'Обзор', link: '/ru/generators/index' },
            { text: 'Boundary bias', link: '/ru/generators/boundary-bias' },
            { text: 'Зависимые генераторы (flatMap vs draw)', link: '/ru/generators/dependent' },
            { text: 'Свой arbitrary', link: '/ru/generators/custom-arbitrary' },
        ],
    },
    {
        text: 'Контроль прогонов',
        items: [
            { text: 'Assume vs filter', link: '/ru/controlling-runs/assume-vs-filter' },
            { text: 'Ограничение shrink', link: '/ru/controlling-runs/bounding-shrink' },
            { text: 'Дедлайны', link: '/ru/controlling-runs/deadlines' },
            { text: 'Переменные окружения', link: '/ru/controlling-runs/env-overrides' },
        ],
    },
    { text: 'Корпус регрессий', link: '/ru/regression-corpus' },
    { text: 'Явные примеры', link: '/ru/explicit-examples' },
    { text: 'Распределение', link: '/ru/distribution' },
    { text: 'Сэмплирование и экспорт', link: '/ru/sampling-and-export' },
    {
        text: 'State machine',
        items: [
            { text: 'Концепции', link: '/ru/state-machine/concepts' },
            { text: 'Shrinking', link: '/ru/state-machine/shrinking' },
        ],
    },
    { text: 'Рецепты', link: '/ru/recipes' },
    { text: 'Безопасность', link: '/ru/security' },
    { text: 'Примеры', link: '/ru/examples' },
    {
        text: 'Кулинарная книга',
        items: [
            { text: 'Первое property', link: '/ru/cookbook/first-property' },
            { text: 'Воспроизведение по seed', link: '/ru/cookbook/reproducing-with-seed' },
            { text: 'CI', link: '/ru/cookbook/ci-recipes' },
            { text: 'State machine тест', link: '/ru/cookbook/writing-a-state-machine' },
        ],
    },
    {
        text: 'API',
        items: [
            { text: 'Обзор', link: '/ru/api/index' },
            { text: 'Исключения', link: '/ru/api/exceptions' },
        ],
    },
    { text: 'Roadmap', link: '/ru/roadmap' },
    { text: 'Миграция с 1.x', link: '/ru/migrating-from-1x' },
    { text: 'llms.txt', link: '/ru/llms' },
]

const SITE_URL = 'https://rasuvaeff.github.io/property-testing/'

// This site redirects to the family site (property-testing-evolution-plan.md,
// §I.0, §I.5.8). The package is frozen at 2.8.1 and abandoned; every page
// here describes behaviour that is identical in the successor packages, so
// keeping a second copy alive only splits the search results.
//
// Per page, not one blanket redirect to the family home: a reader arriving
// from a search result for "PROPERTY_SEED" must land on the page about
// PROPERTY_SEED, not on a home page that makes them search again.
const FAMILY_SITE = 'https://rasuvaeff.github.io/property-testing-core/'
const MIGRATION_PAGE = 'guide/migrating-from-2x'

// 2.x pages whose content the family site keeps somewhere else, plus the
// three that have no successor at all (they go to the migration page — the
// plan's rule is "never a 404, and never a page that pretends to answer").
const RELOCATED: Record<string, string> = {
    'cookbook/first-property': 'guide/intro/getting-started',
    'cookbook/reproducing-with-seed': 'guide/controlling-runs/env-overrides',
    'cookbook/ci-recipes': 'guide/regression-corpus',
    'cookbook/writing-a-state-machine': 'guide/state-machine/concepts',
    'migrating-from-1x': MIGRATION_PAGE,
    llms: MIGRATION_PAGE,
    roadmap: MIGRATION_PAGE,
}

function successorUrl(relativePath: string): string {
    const withoutExtension = relativePath.replace(/\.md$/, '')
    // A section index keeps its trailing slash on the way out: the successor
    // is a directory index too, and asking GitHub Pages for the extensionless
    // form relies on its implicit directory redirect.
    const isSectionIndex = /(^|\/)index$/.test(withoutExtension)
    const page = withoutExtension.replace(/(^|\/)index$/, '$1').replace(/\/$/, '')

    // The family site is EN-only by decision (§I.0). A Russian URL therefore
    // resolves to the English page on the same topic rather than to the
    // migration page: the same content in the other language beats no content.
    const enPage = page.replace(/^ru(\/|$)/, '')

    // '' is the home page; '404' is VitePress's not-found page, which GitHub
    // Pages serves for every unknown path under this site — so an old URL
    // that never existed here still ends up somewhere useful instead of on a
    // dead end. Neither maps into /guide/.
    if (enPage === '' || enPage === '404') {
        return FAMILY_SITE
    }

    // Every reference page goes to the family API index, not to the matching
    // class page. The family layout nests pages by sub-namespace, so a map
    // would be needed — and this site never rebuilds, so that map would rot
    // into 404s the first time a class moves. The index is a linked table:
    // one extra click that cannot go stale.
    if (enPage === 'api' || enPage.startsWith('api/')) {
        return `${FAMILY_SITE}api/`
    }

    const relocated = RELOCATED[enPage]
    if (relocated !== undefined) {
        return FAMILY_SITE + relocated
    }

    return `${FAMILY_SITE}guide/${enPage}${isSectionIndex ? '/' : ''}`
}

export default defineConfig({
    title: 'Testo Property Testing',
    description:
        'Property-based testing for PHP 8.3+, a Testo plugin: generate hundreds of random inputs, find the one that falsifies your property, and shrink it to a minimal counterexample.',
    base: '/property-testing/',
    // sources/ holds the frozen 2.8.1 README snapshot the aggregator reads
    // (see scripts/aggregate.mjs). It is input, not a page: its links are
    // relative to the repository root, which VitePress would report as dead.
    srcExclude: ['sources/**'],
    cleanUrls: true,
    lastUpdated: true,
    sitemap: { hostname: SITE_URL },
    head: [
        ['link', { rel: 'icon', type: 'image/svg+xml', href: '/property-testing/logo-mark.svg' }],
        ['meta', { name: 'theme-color', content: '#12796A' }],
        ['meta', { property: 'og:type', content: 'website' }],
        ['meta', { property: 'og:site_name', content: 'Testo Property Testing' }],
        ['meta', { name: 'twitter:card', content: 'summary' }],
    ],
    // Per-page redirect to the successor site, plus the Open Graph/Twitter
    // title & description VitePress's static `head` array above cannot vary
    // per page. Canonical and og:url point at the SUCCESSOR, not at this
    // page: this site is a forwarding address now, and a self-canonical
    // would ask search engines to keep indexing the copy being retired.
    // `pageData.relativePath` carries the 'ru/' prefix for Russian pages and
    // none for English ones (English is the unprefixed root locale), which
    // is what successorUrl() reads.
    transformHead: ({ pageData, title, description }) => {
        const successor = successorUrl(pageData.relativePath)

        return [
            // The redirect itself. A meta refresh rather than a rule on the
            // host, because GitHub Pages serves static files and has no
            // redirect configuration; search engines treat a 0-second refresh
            // as a permanent move, and the canonical below says the same
            // thing to the ones that do not.
            ['meta', { 'http-equiv': 'refresh', content: `0; url=${successor}` }],
            ['link', { rel: 'canonical', href: successor }],
            ['meta', { property: 'og:title', content: title }],
            ['meta', { property: 'og:description', content: description }],
            ['meta', { property: 'og:url', content: successor }],
            ['meta', { name: 'twitter:title', content: title }],
            ['meta', { name: 'twitter:description', content: description }],
        ]
    },
    themeConfig: {
        logo: '/logo-mark.svg',
        search: { provider: 'local' },
        socialLinks: [
            { icon: 'github', link: 'https://github.com/rasuvaeff/property-testing' },
        ],
        editLink: {
            pattern: 'https://github.com/rasuvaeff/property-testing/edit/master/docs/:path',
        },
    },
    locales: {
        root: {
            label: 'English',
            lang: 'en',
            themeConfig: {
                nav: [
                    { text: 'Guide', link: '/intro/what-is-property-testing' },
                    { text: 'API', link: '/api/index' },
                    { text: 'Testo', link: 'https://php-testo.github.io/' },
                ],
                sidebar: { '/': enSidebar },
                outlineTitle: 'On this page',
            },
        },
        ru: {
            label: 'Русский',
            lang: 'ru',
            link: '/ru/',
            themeConfig: {
                nav: [
                    { text: 'Руководство', link: '/ru/intro/what-is-property-testing' },
                    { text: 'API', link: '/ru/api/index' },
                    { text: 'Testo', link: 'https://php-testo.github.io/' },
                ],
                sidebar: { '/ru/': ruSidebar },
                outlineTitle: 'На этой странице',
                docFooter: { prev: 'Назад', next: 'Далее' },
                returnToTopLabel: 'Наверх',
                langMenuLabel: 'Язык',
                darkModeSwitchLabel: 'Тема',
            },
        },
    },
})
