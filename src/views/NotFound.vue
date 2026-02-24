<template>
  <div class="flex min-h-screen flex-col items-center justify-center gap-4">
    <h1 class="text-6xl font-extrabold">404</h1>
    <p class="text-slate-500">صفحه مورد نظر یافت نشد</p>
    <router-link :to="{ name: 'login' }">
      <el-button type="primary">بازگشت به ورود</el-button>
    </router-link>
  </div>
</template>
