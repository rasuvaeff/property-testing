<script setup lang="ts">
import { computed } from 'vue'
import { useData, withBase } from 'vitepress'

const { lang } = useData()
const year = new Date().getFullYear()
const isRu = computed(() => lang.value === 'ru')

// English is the unprefixed root locale; Russian pages live under '/ru/'.
const prefix = computed(() => (isRu.value ? '/ru' : ''))

const columnsEn = [
    {
        title: 'Guide',
        links: [
            { text: 'What is property-testing', link: '/intro/what-is-property-testing' },
            { text: 'Getting started', link: '/intro/getting-started' },
            { text: 'Shrinking', link: '/shrinking' },
            { text: 'Generators', link: '/generators/index' },
        ],
    },
    {
        title: 'Advanced',
        links: [
            { text: 'Regression corpus', link: '/regression-corpus' },
            { text: 'State machine', link: '/state-machine/concepts' },
            { text: 'Recipes', link: '/recipes' },
            { text: 'Security', link: '/security' },
        ],
    },
    {
        title: 'Cookbook',
        links: [
            { text: 'Your first property', link: '/cookbook/first-property' },
            { text: 'Reproducing with a seed', link: '/cookbook/reproducing-with-seed' },
            { text: 'CI recipes', link: '/cookbook/ci-recipes' },
            { text: 'State machine test', link: '/cookbook/writing-a-state-machine' },
        ],
    },
    {
        title: 'Community',
        links: [
            { text: 'GitHub', link: 'https://github.com/rasuvaeff/property-testing' },
            { text: 'Report an issue', link: 'https://github.com/rasuvaeff/property-testing/issues/new' },
            { text: 'Packagist', link: 'https://packagist.org/packages/rasuvaeff/property-testing' },
            { text: 'Roadmap', link: '/roadmap' },
        ],
    },
]

const columnsRu = [
    {
        title: 'Руководство',
        links: [
            { text: 'Что такое property-testing', link: '/intro/what-is-property-testing' },
            { text: 'Быстрый старт', link: '/intro/getting-started' },
            { text: 'Shrinking', link: '/shrinking' },
            { text: 'Генераторы', link: '/generators/index' },
        ],
    },
    {
        title: 'Продвинутое',
        links: [
            { text: 'Корпус регрессий', link: '/regression-corpus' },
            { text: 'State machine', link: '/state-machine/concepts' },
            { text: 'Рецепты', link: '/recipes' },
            { text: 'Безопасность', link: '/security' },
        ],
    },
    {
        title: 'Кулинарная книга',
        links: [
            { text: 'Первое property', link: '/cookbook/first-property' },
            { text: 'Воспроизведение по seed', link: '/cookbook/reproducing-with-seed' },
            { text: 'CI', link: '/cookbook/ci-recipes' },
            { text: 'State machine тест', link: '/cookbook/writing-a-state-machine' },
        ],
    },
    {
        title: 'Сообщество',
        links: [
            { text: 'GitHub', link: 'https://github.com/rasuvaeff/property-testing' },
            { text: 'Сообщить об ошибке', link: 'https://github.com/rasuvaeff/property-testing/issues/new' },
            { text: 'Packagist', link: 'https://packagist.org/packages/rasuvaeff/property-testing' },
            { text: 'Roadmap', link: '/roadmap' },
        ],
    },
]

const columns = computed(() => isRu.value ? columnsRu : columnsEn)
const tagline = computed(() =>
    isRu.value
        ? 'Property-based testing для PHP 8.3+ — плагин для Testo.'
        : 'Property-based testing for PHP 8.3+ — a Testo plugin.',
)
const builtWith = computed(() => (isRu.value ? 'Собрано на' : 'Built with'))

// Plain <a href="/..."> is NOT rewritten with `base` by VitePress (only
// markdown links and router-aware components get that) — an absolute path
// here would 404 under the '/property-testing/' base. External links pass
// through; internal ones also need the locale prefix ('/ru' or none).
function href(link: string): string {
    if (/^https?:\/\//.test(link)) return link
    return withBase(prefix.value + link)
}
</script>

<template>
  <footer class="site-footer">
    <div class="site-footer-inner">
      <div class="site-footer-grid">
        <div class="site-footer-brand">
          <a :href="withBase(prefix + '/')" class="brand-mark">Testo Property Testing</a>
          <p>{{ tagline }}</p>
        </div>
        <div v-for="(col, i) in columns" :key="col.title + i" class="site-footer-col">
          <h3>{{ col.title }}</h3>
          <ul>
            <li v-for="l in col.links" :key="l.text">
              <a :href="href(l.link)">{{ l.text }}</a>
            </li>
          </ul>
        </div>
      </div>
      <div class="site-footer-bottom">
        <span>© {{ year }} <a href="https://github.com/rasuvaeff">Victor Razuvaev</a> · BSD-3-Clause</span>
        <span>{{ builtWith }} <a href="https://vitepress.dev/" target="_blank" rel="noreferrer">VitePress</a></span>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  /* VPSidebar is `position: fixed` and pinned to the viewport for the
     entire scroll height, above static content by CSS painting rules —
     a plain static footer renders BEHIND it once scrolled this far.
     Opting into the positioned layer with a higher z-index fixes it. */
  position: relative;
  z-index: 30;
  border-top: 1px solid var(--vp-c-divider);
  background: var(--vp-c-bg-alt);
  margin-top: 64px;
}

.site-footer-inner {
  max-width: 1152px;
  margin: 0 auto;
  padding: 48px 24px 32px;
}

.site-footer-grid {
  display: grid;
  /* `1fr` alone won't shrink below its content's min-content width, so a
     fixed-column template can force this wider than its container at
     in-between viewport widths — auto-fit/minmax reflows to fewer columns
     instead, so it physically cannot overflow. */
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 32px 24px;
}

.site-footer-brand {
  min-width: 0;
  grid-column: span 2;
}

.site-footer-brand .brand-mark {
  font-weight: 700;
  font-size: 15px;
  color: var(--vp-c-text-1);
}

.site-footer-brand p {
  margin: 8px 0 0;
  font-size: 13px;
  line-height: 1.6;
  color: var(--vp-c-text-2);
  max-width: 32ch;
}

.site-footer-col {
  min-width: 0;
}

.site-footer-col h3 {
  margin: 0 0 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--vp-c-text-2);
  border: none;
  padding: 0;
}

.site-footer-col ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.site-footer-col a {
  font-size: 13px;
  color: var(--vp-c-text-1);
}

.site-footer-col a:hover {
  color: var(--vp-c-brand-1);
}

.site-footer-bottom {
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid var(--vp-c-divider);
  display: flex;
  flex-wrap: wrap;
  gap: 8px 24px;
  justify-content: space-between;
  font-size: 12px;
  color: var(--vp-c-text-3);
}

.site-footer-bottom a {
  color: var(--vp-c-text-2);
}

.site-footer-bottom a:hover {
  color: var(--vp-c-brand-1);
}
</style>
