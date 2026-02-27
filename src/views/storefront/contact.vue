<script setup>
import { onMounted, reactive, ref } from "vue";
import { ElMessage } from "element-plus";
import SiteShell from "@/components/storefront/SiteShell.vue";
import Breadcrumb from "@/components/storefront/Breadcrumb.vue";
import Loader from "@/components/storefront/Loader.vue";
import { api } from "@/services/api";
import { useSiteStore } from "@/stores/siteStore";

document.title = "Contact | Vue Store";

const siteStore = useSiteStore();
const loading = ref(true);
const submitting = ref(false);

const form = reactive({
  name: "",
  email: "",
  mobile: "",
  message: ""
});

const submit = async () => {
  submitting.value = true;
  try {
    await api.sendContact(form);
    ElMessage.success("Message sent successfully");
    form.name = "";
    form.email = "";
    form.mobile = "";
    form.message = "";
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to send message");
  } finally {
    submitting.value = false;
  }
};

onMounted(async () => {
  try {
    await siteStore.fetchContact();
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <SiteShell>
    <Breadcrumb :items="[{ label: 'Home', to: '/' }, { label: 'Contact' }]" />

    <Loader v-if="loading" :rows="6" />

    <div v-else class="grid gap-6 lg:grid-cols-2">
      <div class="panel p-5">
        <h1 class="mb-4 text-3xl font-black">Contact Us</h1>
        <div class="prose max-w-none" v-html="siteStore.contactPage?.content?.rendered" />

        <div class="mt-4 space-y-2 text-sm">
          <p><strong>Phone:</strong> {{ siteStore.contactSettings.phone || '-' }}</p>
          <p><strong>Email:</strong> {{ siteStore.contactSettings.email || '-' }}</p>
          <p><strong>Address:</strong> {{ siteStore.contactSettings.address || '-' }}</p>
        </div>

        <iframe
          v-if="siteStore.contactSettings.map_url"
          :src="siteStore.contactSettings.map_url"
          class="mt-4 h-72 w-full rounded border-0"
          loading="lazy"
        />
      </div>

      <div class="panel p-5">
        <h2 class="mb-4 text-xl font-black">Send Message</h2>
        <div class="space-y-3">
          <el-input v-model="form.name" placeholder="Name" />
          <el-input v-model="form.email" placeholder="Email" />
          <el-input v-model="form.mobile" placeholder="Mobile" />
          <el-input v-model="form.message" type="textarea" :rows="6" placeholder="Message" />
          <el-button type="primary" :loading="submitting" @click="submit">Send</el-button>
        </div>
      </div>
    </div>
  </SiteShell>
</template>
