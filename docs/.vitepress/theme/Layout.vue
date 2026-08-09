<script setup lang="ts">
import DefaultTheme from 'vitepress/theme'
import Footer from './Footer.vue'
import FrozenBanner from './FrozenBanner.vue'

const { Layout } = DefaultTheme
</script>

<template>
  <Layout>
    <template #layout-top>
      <FrozenBanner />
    </template>
    <template #layout-bottom>
      <Footer />
    </template>
  </Layout>
</template>
