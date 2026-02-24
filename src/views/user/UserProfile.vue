<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import DynamicMetaFields from "@/components/Users/DynamicMetaFields.vue";
import { useUserStore } from "@/stores/userStore";
import { useCustomFieldStore } from "@/stores/customFieldStore";

const userStore = useUserStore();
const customFieldStore = useCustomFieldStore();
const loading = ref(true);
const meta = ref({});
const mobileReadonly = ref("");
const form = reactive({
  first_name: "",
  last_name: "",
  email: "",
  password: ""
});

const editableMetaSchema = computed(() =>
  customFieldStore.sortedSchema.filter(
    (field) => !["mobile", "phone", "billing_phone"].includes(String(field.key || "").toLowerCase())
  )
);

const validateDynamicFields = () => {
  for (const field of editableMetaSchema.value) {
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

const load = async () => {
  loading.value = true;
  try {
    await customFieldStore.fetchSchema();
    const { user, meta: userMeta } = await userStore.fetchMe();
    form.first_name = user.first_name || "";
    form.last_name = user.last_name || "";
    form.email = user.email || "";
    form.password = "";
    mobileReadonly.value = userMeta?.mobile || userMeta?.phone || userMeta?.billing_phone || "";
    meta.value = userMeta || {};
  } finally {
    loading.value = false;
  }
};

const save = async () => {
  if (!validateDynamicFields()) return;
  await userStore.updateMe({
    user: {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      ...(form.password ? { password: form.password } : {})
    },
    meta: {
      ...meta.value,
      mobile: mobileReadonly.value,
      phone: mobileReadonly.value,
      billing_phone: mobileReadonly.value
    }
  });
};

onMounted(load);
</script>

<template>
  <div>
    <PageHeader title="پروفایل کاربر" subtitle="ویرایش اطلاعات حساب و فیلدهای سفارشی" />
    <div v-if="loading" class="panel p-4"><el-skeleton :rows="8" animated /></div>
    <div v-else class="panel p-4">
      <el-form label-position="top">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
          <el-form-item label="نام">
            <el-input v-model="form.first_name" />
          </el-form-item>
          <el-form-item label="نام خانوادگی">
            <el-input v-model="form.last_name" />
          </el-form-item>
          <el-form-item label="ایمیل">
            <el-input v-model="form.email" />
          </el-form-item>
          <el-form-item label="شماره موبایل">
            <el-input :model-value="mobileReadonly" disabled />
          </el-form-item>
        </div>
        <el-form-item label="تغییر رمز عبور">
          <el-input v-model="form.password" show-password />
        </el-form-item>

        <h3 class="mb-2 mt-3 text-sm font-semibold">فیلدهای سفارشی</h3>
        <DynamicMetaFields v-model="meta" :schema="editableMetaSchema" />
      </el-form>
      <div class="mt-3 flex justify-end">
        <el-button type="primary" :loading="userStore.saving" @click="save">ذخیره تغییرات</el-button>
      </div>
    </div>
  </div>
</template>
