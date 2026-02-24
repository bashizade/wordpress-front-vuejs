<script setup>
import { reactive, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { ElMessage } from "element-plus";
import DynamicMetaFields from "@/components/Users/DynamicMetaFields.vue";

const { t } = useI18n();
const open = defineModel("open", { type: Boolean, default: false });

const props = defineProps({
  user: {
    type: Object,
    default: null
  },
  meta: {
    type: Object,
    default: () => ({})
  },
  schema: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(["submit"]);

const form = reactive({
  id: null,
  username: "",
  first_name: "",
  last_name: "",
  email: "",
  mobile: "",
  password: "",
  roles: [],
  status: "active"
});
const metaForm = ref({});

const roleOptions = ["administrator", "editor", "author", "contributor", "subscriber", "shop_manager", "customer"];

watch(
  () => [props.user, props.meta, open.value],
  () => {
    if (!open.value || !props.user) return;
    form.id = props.user.id;
    form.username = props.user.username || props.user.slug || "";
    form.first_name = props.user.first_name || "";
    form.last_name = props.user.last_name || "";
    form.email = props.user.email || "";
    form.mobile = props.meta?.mobile || props.meta?.phone || props.meta?.billing_phone || "";
    form.password = "";
    form.roles = props.user.roles || [];
    form.status = props.meta?._account_status || "active";
    metaForm.value = { ...(props.meta || {}) };
  },
  { deep: true, immediate: true }
);

const onSubmit = () => {
  for (const field of props.schema) {
    const value = metaForm.value?.[field.key];
    if (field.required && (value === "" || value === null || value === undefined)) {
      ElMessage.error(`${field.label} is required`);
      return;
    }
    if (field.validation && value) {
      const regex = new RegExp(field.validation);
      if (!regex.test(String(value))) {
        ElMessage.error(`${field.label} is invalid`);
        return;
      }
    }
  }
  const metaPayload = {
    ...metaForm.value,
    mobile: form.mobile,
    phone: form.mobile,
    billing_phone: form.mobile,
    _account_status: form.status
  };
  emit("submit", {
    id: form.id,
    user: {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      roles: form.roles,
      ...(form.password ? { password: form.password } : {})
    },
    meta: metaPayload
  });
  open.value = false;
};
</script>

<template>
  <el-dialog v-model="open" :title="t('users.edit')" width="960">
    <el-form label-position="top">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <el-form-item :label="t('users.username')">
          <el-input :model-value="form.username" disabled />
        </el-form-item>
        <el-form-item :label="t('users.email')">
          <el-input v-model="form.email" />
        </el-form-item>
      </div>
      <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <el-form-item :label="t('users.firstName')">
          <el-input v-model="form.first_name" />
        </el-form-item>
        <el-form-item :label="t('users.lastName')">
          <el-input v-model="form.last_name" />
        </el-form-item>
        <el-form-item label="Mobile">
          <el-input v-model="form.mobile" placeholder="09xxxxxxxxx" />
        </el-form-item>
      </div>
      <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <el-form-item :label="t('users.passwordReset')">
          <el-input v-model="form.password" show-password />
        </el-form-item>
        <el-form-item :label="t('users.roles')">
          <el-select v-model="form.roles" multiple>
            <el-option v-for="role in roleOptions" :key="role" :label="role" :value="role" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('users.status')">
          <el-select v-model="form.status">
            <el-option label="active" value="active" />
            <el-option label="inactive" value="inactive" />
            <el-option label="pending" value="pending" />
          </el-select>
        </el-form-item>
      </div>

      <h4 class="mb-2 mt-4 text-sm font-semibold">{{ t("customFields.title") }}</h4>
      <DynamicMetaFields v-model="metaForm" :schema="schema" />
    </el-form>
    <template #footer>
      <el-button @click="open = false">{{ t("common.cancel") }}</el-button>
      <el-button type="primary" @click="onSubmit">{{ t("common.save") }}</el-button>
    </template>
  </el-dialog>
</template>
