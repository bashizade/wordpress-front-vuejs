<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import DynamicMetaFields from "@/components/Users/DynamicMetaFields.vue";
import { useAuthStore } from "@/stores/authStore";
import { useCustomFieldStore } from "@/stores/customFieldStore";

const router = useRouter();
const authStore = useAuthStore();
const customFieldStore = useCustomFieldStore();

const form = reactive({
  username: "",
  email: "",
  password: "",
  mobile: ""
});
const meta = ref({});
const otpDialog = ref(false);
const otp = ref("");

const validateDynamicFields = () => {
  for (const field of customFieldStore.sortedSchema) {
    const value = meta.value?.[field.key];
    if (field.required && (value === "" || value === null || value === undefined)) {
      ElMessage.error(`فیلد ${field.label} اجباری است`);
      return false;
    }
    if (field.validation && value) {
      const regex = new RegExp(field.validation);
      if (!regex.test(String(value))) {
        ElMessage.error(`مقدار ${field.label} معتبر نیست`);
        return false;
      }
    }
  }
  return true;
};

const sendRegistrationOtp = async () => {
  if (!validateDynamicFields()) return;
  const ok = await authStore.sendRegisterOtp({
    username: form.username,
    email: form.email,
    password: form.password,
    mobile: form.mobile,
    meta: {
      ...meta.value,
      mobile: form.mobile
    }
  });
  if (!ok) return;
  otpDialog.value = true;
};

const verifyRegistrationOtp = async () => {
  const ok = await authStore.verifyRegisterOtp({
    mobile: form.mobile,
    otp: otp.value
  });
  if (!ok) return;
  otpDialog.value = false;
  router.push(authStore.getHomeRoute());
};

onMounted(() => {
  customFieldStore.fetchSchema();
});
</script>

<template>
  <div class="flex min-h-screen items-center justify-center p-4">
    <div class="panel w-full max-w-3xl p-8 shadow-xl">
      <h1 class="mb-2 text-2xl font-bold">ثبت نام</h1>
      <p class="mb-6 text-sm text-slate-500">قبل از ساخت حساب، تایید موبایل اجباری است</p>
      <el-form label-position="top" @submit.prevent="sendRegistrationOtp">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
          <el-form-item label="نام کاربری">
            <el-input v-model="form.username" />
          </el-form-item>
          <el-form-item label="ایمیل">
            <el-input v-model="form.email" type="email" />
          </el-form-item>
          <el-form-item label="رمز عبور">
            <el-input v-model="form.password" show-password />
          </el-form-item>
          <el-form-item label="شماره موبایل">
            <el-input v-model="form.mobile" placeholder="09xxxxxxxxx" />
          </el-form-item>
        </div>

        <h3 class="mb-2 mt-4 text-sm font-semibold">فیلدهای سفارشی</h3>
        <DynamicMetaFields v-model="meta" :schema="customFieldStore.sortedSchema" />

        <div class="mt-4 flex justify-end gap-2">
          <router-link :to="{ name: 'login' }">
            <el-button>بازگشت</el-button>
          </router-link>
          <el-button type="primary" :loading="authStore.loading" native-type="submit">ارسال کد تایید</el-button>
        </div>
      </el-form>
    </div>
  </div>

  <el-dialog v-model="otpDialog" title="تایید ثبت نام" width="420">
    <el-form label-position="top" @submit.prevent="verifyRegistrationOtp">
      <el-form-item label="کد 6 رقمی پیامک">
        <el-input v-model="otp" maxlength="6" />
      </el-form-item>
      <div class="flex justify-end gap-2">
        <el-button @click="otpDialog = false">انصراف</el-button>
        <el-button type="primary" :loading="authStore.loading" native-type="submit">تایید و ساخت حساب</el-button>
      </div>
    </el-form>
  </el-dialog>
</template>
