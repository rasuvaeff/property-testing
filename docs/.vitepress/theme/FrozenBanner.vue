<script setup lang="ts">
import { computed } from 'vue'
import { useData } from 'vitepress'

const { page } = useData()

const isRussian = computed(() => page.value.relativePath.startsWith('ru/'))
</script>

<template>
  <div class="frozen-banner" role="note">
    <template v-if="isRussian">
      <strong>Пакет заморожен.</strong>
      Этот сайт документирует линию <code>rasuvaeff/property-testing</code> 2.x, последний
      функциональный релиз которой — <code>2.8.1</code>. Дальше выходят только security-фиксы.
      Новая работа идёт в
      <a href="https://github.com/rasuvaeff/property-testing-core">property-testing-core</a> (движок),
      <a href="https://github.com/rasuvaeff/property-testing-testo">-testo</a> (тот же
      <code>#[Property]</code>, drop-in) и
      <a href="https://github.com/rasuvaeff/property-testing-phpunit">-phpunit</a>.
      <a href="https://github.com/rasuvaeff/property-testing-core/blob/master/MIGRATION.md">Руководство по миграции</a>.
    </template>
    <template v-else>
      <strong>This package is frozen.</strong>
      This site documents the <code>rasuvaeff/property-testing</code> 2.x line, whose last
      functional release is <code>2.8.1</code>. Only security fixes follow. New work happens in
      <a href="https://github.com/rasuvaeff/property-testing-core">property-testing-core</a> (the engine),
      <a href="https://github.com/rasuvaeff/property-testing-testo">-testo</a> (the same
      <code>#[Property]</code>, drop-in) and
      <a href="https://github.com/rasuvaeff/property-testing-phpunit">-phpunit</a>.
      <a href="https://github.com/rasuvaeff/property-testing-core/blob/master/MIGRATION.md">Migration guide</a>.
    </template>
  </div>
</template>

<style scoped>
.frozen-banner {
  padding: 10px 24px;
  border-bottom: 1px solid var(--vp-c-warning-2, var(--vp-c-divider));
  background-color: var(--vp-c-warning-soft, var(--vp-c-default-soft));
  color: var(--vp-c-text-1);
  font-size: 14px;
  line-height: 1.5;
  text-align: center;
}

.frozen-banner a {
  color: var(--vp-c-brand-1);
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.frozen-banner code {
  font-size: 0.9em;
}
</style>
