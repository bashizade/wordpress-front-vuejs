<script setup>
import UserSidebar from "@/components/Layout/UserSidebar.vue";
</script>

<template>
  <div class="min-h-screen">
    <UserSidebar />
    <div class="lg:pr-72">
      <main class="p-4 md:p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>
