<script setup>
import { reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const tab = ref("mobile");
const passwordForm = reactive({
  username: "",
  password: ""
});
const mobileForm = reactive({
  mobile: "",
  otp: ""
});
const otpDialog = ref(false);

const onPasswordLogin = async () => {
  const ok = await authStore.login(passwordForm);
  if (!ok) return;
  const redirect = route.query.redirect;
  if (redirect) {
    router.push(String(redirect));
    return;
  }
  router.push(authStore.getHomeRoute());
};

const sendMobileCode = async () => {
  const ok = await authStore.sendLoginOtp(mobileForm.mobile);
  if (!ok) return;
  otpDialog.value = true;
};

const verifyMobileCode = async () => {
  const ok = await authStore.verifyLoginOtp({
    mobile: mobileForm.mobile,
    otp: mobileForm.otp
  });
  if (!ok) return;
  otpDialog.value = false;
  const redirect = route.query.redirect;
  if (redirect) {
    router.push(String(redirect));
    return;
  }
  router.push(authStore.getHomeRoute());
};
</script>

<template>
  <div class="flex min-h-screen items-center justify-center p-4">
    <div class="panel w-full max-w-md p-8 shadow-xl">
      <h1 class="mb-2 text-2xl font-bold">ورود</h1>
      <p class="mb-6 text-sm text-slate-500">ورود با موبایل (OTP) یا نام کاربری و رمز عبور</p>

      <el-tabs v-model="tab">
        <el-tab-pane label="ورود با موبایل" name="mobile">
          <el-form label-position="top" @submit.prevent="sendMobileCode">
            <el-form-item label="شماره موبایل">
              <el-input v-model="mobileForm.mobile" placeholder="09xxxxxxxxx" />
            </el-form-item>
            <el-button class="w-full" type="primary" :loading="authStore.loading" native-type="submit">
              ارسال کد تایید
            </el-button>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="ورود کلاسیک" name="password">
          <el-form label-position="top" @submit.prevent="onPasswordLogin">
            <el-form-item label="نام کاربری">
              <el-input v-model="passwordForm.username" autocomplete="username" />
            </el-form-item>
            <el-form-item label="رمز عبور">
              <el-input v-model="passwordForm.password" show-password autocomplete="current-password" />
            </el-form-item>
            <el-button class="w-full" type="primary" :loading="authStore.loading" native-type="submit">
              ورود
            </el-button>
          </el-form>
        </el-tab-pane>
      </el-tabs>

      <div class="mt-4 text-center text-sm">
        <router-link :to="{ name: 'register' }" class="text-sky-600">ثبت نام کاربر جدید</router-link>
      </div>
    </div>
  </div>

  <el-dialog v-model="otpDialog" title="تایید کد پیامک" width="420">
    <el-form label-position="top" @submit.prevent="verifyMobileCode">
      <el-form-item label="کد 6 رقمی">
        <el-input v-model="mobileForm.otp" maxlength="6" />
      </el-form-item>
      <div class="flex justify-end gap-2">
        <el-button @click="otpDialog = false">انصراف</el-button>
        <el-button type="primary" :loading="authStore.loading" native-type="submit">تایید و ورود</el-button>
      </div>
    </el-form>
  </el-dialog>
</template>
