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
    // Per-page canonical + Open Graph/Twitter title & description — VitePress's
    // static `head` array above can't vary per page, and every page otherwise
    // shares one generic <meta description>, which is worse for search than a
    // page-specific one (set via each page's own `description` frontmatter).
    // `pageData.relativePath` already carries the 'ru/' prefix for Russian
    // pages and no prefix for English ones (English is the unprefixed root
    // locale), so no locale-specific branching is needed here.
    transformHead: ({ pageData, title, description }) => {
        const clean = pageData.relativePath.replace(/\.md$/, '').replace(/(^|\/)index$/, '$1')
        const url = SITE_URL + clean

        return [
            ['link', { rel: 'canonical', href: url }],
            ['meta', { property: 'og:title', content: title }],
            ['meta', { property: 'og:description', content: description }],
            ['meta', { property: 'og:url', content: url }],
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
