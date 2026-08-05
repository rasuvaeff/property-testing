import { defineConfig } from 'vitepress'

const enSidebar = [
    {
        text: 'Intro',
        items: [
            { text: 'What is property-testing', link: '/en/intro/what-is-property-testing' },
            { text: 'Getting started', link: '/en/intro/getting-started' },
            { text: 'Concepts', link: '/en/intro/concepts' },
        ],
    },
    { text: 'Shrinking', link: '/en/shrinking' },
    {
        text: 'Generators',
        items: [
            { text: 'Overview', link: '/en/generators/index' },
            { text: 'Boundary bias', link: '/en/generators/boundary-bias' },
            { text: 'Dependent generators (flatMap vs draw)', link: '/en/generators/dependent' },
            { text: 'Custom arbitrary', link: '/en/generators/custom-arbitrary' },
        ],
    },
    {
        text: 'Controlling runs',
        items: [
            { text: 'Assume vs filter', link: '/en/controlling-runs/assume-vs-filter' },
            { text: 'Bounding shrink work', link: '/en/controlling-runs/bounding-shrink' },
            { text: 'Deadlines', link: '/en/controlling-runs/deadlines' },
            { text: 'Environment overrides', link: '/en/controlling-runs/env-overrides' },
        ],
    },
    { text: 'Regression corpus', link: '/en/regression-corpus' },
    { text: 'Explicit examples', link: '/en/explicit-examples' },
    { text: 'Distribution', link: '/en/distribution' },
    { text: 'Sampling & export', link: '/en/sampling-and-export' },
    {
        text: 'State machine',
        items: [
            { text: 'Concepts', link: '/en/state-machine/concepts' },
            { text: 'Shrinking', link: '/en/state-machine/shrinking' },
        ],
    },
    { text: 'Recipes', link: '/en/recipes' },
    { text: 'Security', link: '/en/security' },
    { text: 'Examples', link: '/en/examples' },
    {
        text: 'Cookbook',
        items: [
            { text: 'Your first property', link: '/en/cookbook/first-property' },
            { text: 'Reproducing with a seed', link: '/en/cookbook/reproducing-with-seed' },
            { text: 'CI recipes', link: '/en/cookbook/ci-recipes' },
            { text: 'Writing a state machine test', link: '/en/cookbook/writing-a-state-machine' },
        ],
    },
    {
        text: 'API',
        items: [
            { text: 'Overview', link: '/en/api/index' },
            { text: 'Exceptions', link: '/en/api/exceptions' },
        ],
    },
    { text: 'Roadmap', link: '/en/roadmap' },
    { text: 'Migrating from 1.x', link: '/en/migrating-from-1x' },
    { text: 'llms.txt reference', link: '/en/llms' },
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

export default defineConfig({
    title: 'property-testing',
    description: 'Property-based testing for PHP 8.3+, built as a plugin for Testo',
    cleanUrls: true,
    lastUpdated: true,
    themeConfig: {
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
            link: '/en/',
            themeConfig: {
                nav: [
                    { text: 'Guide', link: '/en/intro/what-is-property-testing' },
                    { text: 'API', link: '/en/api/index' },
                    { text: 'Testo', link: 'https://php-testo.github.io/' },
                ],
                sidebar: { '/en/': enSidebar },
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
