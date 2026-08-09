<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useData } from 'vitepress'

const { page } = useData()

const isRussian = computed(() => page.value.relativePath.startsWith('ru/'))

// VitePress offsets the fixed nav, the sidebar, the local nav and the content
// by --vp-layout-top-height, but it never measures a layout-top slot itself —
// the variable simply defaults to 0px. Without publishing the real height the
// nav renders on top of this banner. It has to be measured rather than
// hardcoded because the text wraps to two or three lines on narrow screens.
const banner = ref<HTMLElement | null>(null)
let observer: ResizeObserver | null = null

function publishHeight(): void {
    if (banner.value !== null) {
        document.documentElement.style.setProperty('--vp-layout-top-height', `${banner.value.offsetHeight}px`)
    }
}

onMounted(() => {
    publishHeight()
    observer = new ResizeObserver(publishHeight)

    if (banner.value !== null) {
        observer.observe(banner.value)
    }
})

onUnmounted(() => {
    observer?.disconnect()
    document.documentElement.style.removeProperty('--vp-layout-top-height')
})
</script>

<template>
  <div ref="banner" class="frozen-banner" role="note">
    <template v-if="isRussian">
      <strong>Эта документация переехала.</strong>
      Пакет заморожен на 2.8.1; актуальная документация — на сайте семейства
      <a href="https://rasuvaeff.github.io/property-testing-core/">property-testing</a>.
      Если переадресация не сработала, откройте
      <a href="https://rasuvaeff.github.io/property-testing-core/guide/migrating-from-2x">страницу миграции</a>.
    </template>
    <template v-else>
      <strong>These docs have moved.</strong>
      The package is frozen at 2.8.1; the current documentation lives on the
      family site,
      <a href="https://rasuvaeff.github.io/property-testing-core/">property-testing</a>.
      If the redirect did not fire, open the
      <a href="https://rasuvaeff.github.io/property-testing-core/guide/migrating-from-2x">migration guide</a>.
    </template>
  </div>
</template>

<style scoped>
.frozen-banner {
  position: fixed;
  top: 0;
  right: 0;
  left: 0;
  z-index: var(--vp-z-index-layout-top);
  box-sizing: border-box;
  padding: 8px 24px;
  border-bottom: 1px solid var(--vp-c-divider);
  background-color: var(--vp-c-warning-soft);
  color: var(--vp-c-text-1);
  font-size: 13px;
  font-weight: 500;
  line-height: 20px;
  text-align: center;
}

.frozen-banner a {
  color: var(--vp-c-brand-1);
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 2px;
  transition: color 0.25s;
}

.frozen-banner a:hover {
  color: var(--vp-c-brand-2);
}
</style>
