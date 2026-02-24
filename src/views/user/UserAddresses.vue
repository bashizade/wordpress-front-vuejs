<script setup>
import { onMounted, reactive, ref } from "vue";
import PageHeader from "@/components/common/PageHeader.vue";
import { useUserStore } from "@/stores/userStore";

const userStore = useUserStore();
const loading = ref(true);
const saving = ref(false);

const form = reactive({
  billing: {
    first_name: "",
    last_name: "",
    company: "",
    address_1: "",
    address_2: "",
    city: "",
    state: "",
    postcode: "",
    country: "IR",
    email: "",
    phone: ""
  },
  shipping: {
    first_name: "",
    last_name: "",
    company: "",
    address_1: "",
    address_2: "",
    city: "",
    state: "",
    postcode: "",
    country: "IR"
  }
});

const load = async () => {
  loading.value = true;
  try {
    await userStore.fetchMe();
    const customer = await userStore.fetchCustomerProfile();
    Object.assign(form.billing, customer?.billing || {});
    Object.assign(form.shipping, customer?.shipping || {});
  } finally {
    loading.value = false;
  }
};

const save = async () => {
  saving.value = true;
  try {
    await userStore.updateAddresses({
      billing: form.billing,
      shipping: form.shipping
    });
  } finally {
    saving.value = false;
  }
};

onMounted(load);
</script>

<template>
  <div>
    <PageHeader title="مدیریت آدرس ها" subtitle="ویرایش آدرس صورتحساب و ارسال" />
    <div v-if="loading" class="panel p-4"><el-skeleton :rows="10" animated /></div>
    <div v-else class="space-y-4">
      <div class="panel p-4">
        <h3 class="mb-3 font-semibold">آدرس صورتحساب</h3>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <el-input v-model="form.billing.first_name" placeholder="نام" />
          <el-input v-model="form.billing.last_name" placeholder="نام خانوادگی" />
          <el-input v-model="form.billing.phone" placeholder="تلفن" />
          <el-input v-model="form.billing.email" placeholder="ایمیل" />
          <el-input v-model="form.billing.city" placeholder="شهر" />
          <el-input v-model="form.billing.postcode" placeholder="کدپستی" />
          <el-input v-model="form.billing.address_1" class="md:col-span-2" placeholder="آدرس" />
        </div>
      </div>

      <div class="panel p-4">
        <h3 class="mb-3 font-semibold">آدرس ارسال</h3>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <el-input v-model="form.shipping.first_name" placeholder="نام" />
          <el-input v-model="form.shipping.last_name" placeholder="نام خانوادگی" />
          <el-input v-model="form.shipping.city" placeholder="شهر" />
          <el-input v-model="form.shipping.postcode" placeholder="کدپستی" />
          <el-input v-model="form.shipping.address_1" class="md:col-span-2" placeholder="آدرس" />
        </div>
      </div>
      <div class="flex justify-end">
        <el-button type="primary" :loading="saving" @click="save">ذخیره آدرس ها</el-button>
      </div>
    </div>
  </div>
</template>
