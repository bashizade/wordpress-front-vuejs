<script setup>
import { reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { ElMessage } from "element-plus";
import DynamicMetaFields from "@/components/Users/DynamicMetaFields.vue";

const { t } = useI18n();
const open = defineModel("open", { type: Boolean, default: false });

const props = defineProps({
  schema: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(["submit"]);

const form = reactive({
  username: "",
  first_name: "",
  last_name: "",
  email: "",
  mobile: "",
  password: "",
  roles: ["subscriber"],
  status: "active"
});
const meta = ref({});

const roleOptions = ["administrator", "editor", "author", "contributor", "subscriber", "shop_manager", "customer"];

const onSubmit = () => {
  for (const field of props.schema) {
    const value = meta.value?.[field.key];
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
  emit("submit", {
    user: {
      username: form.username,
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      password: form.password,
      roles: form.roles
    },
    meta: {
      ...meta.value,
      mobile: form.mobile,
      phone: form.mobile,
      billing_phone: form.mobile,
      _account_status: form.status
    }
  });
  open.value = false;
};
</script>

<template>
  <el-dialog v-model="open" :title="t('users.create')" width="960">
    <el-form label-position="top">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <el-form-item :label="t('users.username')">
          <el-input v-model="form.username" />
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
        <el-form-item :label="t('users.password')">
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
      <DynamicMetaFields v-model="meta" :schema="schema" />
    </el-form>
    <template #footer>
      <el-button @click="open = false">{{ t("common.cancel") }}</el-button>
      <el-button type="primary" @click="onSubmit">{{ t("common.create") }}</el-button>
    </template>
  </el-dialog>
</template>
