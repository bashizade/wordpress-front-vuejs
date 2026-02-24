<script setup>
import { reactive, watch } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const open = defineModel("open", { type: Boolean, default: false });

const props = defineProps({
  editingField: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(["save"]);

const form = reactive({
  id: null,
  label: "",
  key: "",
  type: "text",
  required: false,
  unique: false,
  optionsText: "",
  placeholder: "",
  validation: ""
});

watch(
  () => [props.editingField, open.value],
  () => {
    if (!open.value) return;
    if (props.editingField) {
      form.id = props.editingField.id;
      form.label = props.editingField.label;
      form.key = props.editingField.key;
      form.type = props.editingField.type;
      form.required = props.editingField.required;
      form.unique = Boolean(props.editingField.unique);
      form.optionsText = (props.editingField.options || []).join(",");
      form.placeholder = props.editingField.placeholder || "";
      form.validation = props.editingField.validation || "";
      return;
    }
    form.id = null;
    form.label = "";
    form.key = "";
    form.type = "text";
    form.required = false;
    form.unique = false;
    form.optionsText = "";
    form.placeholder = "";
    form.validation = "";
  },
  { immediate: true, deep: true }
);

const onSave = () => {
  emit("save", {
    id: form.id,
    label: form.label,
    key: form.key,
    type: form.type,
    required: form.required,
    unique: form.unique,
    options: form.optionsText
      .split(",")
      .map((item) => item.trim())
      .filter(Boolean),
    placeholder: form.placeholder,
    validation: form.validation
  });
  open.value = false;
};
</script>

<template>
  <el-dialog v-model="open" :title="editingField ? t('customFields.editField') : t('customFields.addField')" width="620">
    <el-form label-position="top">
      <el-form-item :label="t('customFields.fieldLabel')">
        <el-input v-model="form.label" />
      </el-form-item>
      <el-form-item :label="t('customFields.fieldKey')">
        <el-input v-model="form.key" />
      </el-form-item>
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <el-form-item :label="t('customFields.fieldType')">
          <el-select v-model="form.type">
            <el-option :label="t('customFields.types.text')" value="text" />
            <el-option :label="t('customFields.types.number')" value="number" />
            <el-option :label="t('customFields.types.email')" value="email" />
            <el-option :label="t('customFields.types.textarea')" value="textarea" />
            <el-option :label="t('customFields.types.select')" value="select" />
            <el-option :label="t('customFields.types.checkbox')" value="checkbox" />
            <el-option :label="t('customFields.types.date')" value="date" />
            <el-option :label="t('customFields.types.file')" value="file" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('customFields.required')">
          <el-switch v-model="form.required" />
        </el-form-item>
        <el-form-item label="Unique">
          <el-switch v-model="form.unique" />
        </el-form-item>
      </div>
      <el-form-item v-if="form.type === 'select'" :label="t('customFields.options')">
        <el-input v-model="form.optionsText" placeholder="A,B,C" />
      </el-form-item>
      <el-form-item :label="t('customFields.placeholder')">
        <el-input v-model="form.placeholder" />
      </el-form-item>
      <el-form-item :label="t('customFields.validation')">
        <el-input v-model="form.validation" placeholder="regex optional" />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="open = false">{{ t("common.cancel") }}</el-button>
      <el-button type="primary" @click="onSave">{{ t("common.save") }}</el-button>
    </template>
  </el-dialog>
</template>
