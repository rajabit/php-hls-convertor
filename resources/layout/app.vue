<template>
  <v-app>
    <v-app-bar app elevation="4">
      <v-toolbar-title>php-hls-convertor</v-toolbar-title>
    </v-app-bar>
    <v-main class="fill-height">
      <router-view />
    </v-main>
  </v-app>
</template>

<script>
export default {
  name: "app",
};
</script>

<style lang="scss">
</style>
