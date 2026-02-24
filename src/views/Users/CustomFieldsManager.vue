<script setup>
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import PageHeader from "@/components/common/PageHeader.vue";
import AddCustomField from "@/components/Users/AddCustomField.vue";
import { useCustomFieldStore } from "@/stores/customFieldStore";

const { t } = useI18n();
const customFieldStore = useCustomFieldStore();
const dialogOpen = ref(false);
const editing = ref(null);
const dragging = ref(null);

const rows = computed(() => customFieldStore.sortedSchema);

const openCreate = () => {
  editing.value = null;
  dialogOpen.value = true;
};

const openEdit = (row) => {
  editing.value = row;
  dialogOpen.value = true;
};

const saveField = (payload) => {
  if (payload.id) {
    customFieldStore.updateField(payload.id, payload);
  } else {
    customFieldStore.addField(payload);
  }
};

const onDragStart = (id) => {
  dragging.value = id;
};

const onDrop = (targetId) => {
  if (!dragging.value || dragging.value === targetId) return;
  const ids = rows.value.map((item) => item.id);
  const from = ids.indexOf(dragging.value);
  const to = ids.indexOf(targetId);
  if (from < 0 || to < 0) return;
  const [moved] = ids.splice(from, 1);
  ids.splice(to, 0, moved);
  customFieldStore.reorderFields(ids);
  dragging.value = null;
};

onMounted(() => {
  customFieldStore.fetchSchema();
});
</script>

<template>
  <div>
    <PageHeader :title="t('customFields.title')" :subtitle="t('customFields.subtitle')">
      <el-button type="primary" @click="openCreate">{{ t("customFields.addField") }}</el-button>
    </PageHeader>

    <div class="panel p-4">
      <el-table :data="rows" row-key="id" stripe>
        <el-table-column label="#" width="80">
          <template #default="{ row }">
            <div
              class="cursor-move rounded border border-slate-300 px-2 py-1 text-center text-xs"
              draggable="true"
              @dragstart="onDragStart(row.id)"
              @dragover.prevent
              @drop.prevent="onDrop(row.id)"
            >
              Drag
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="label" :label="t('customFields.fieldLabel')" />
        <el-table-column prop="key" :label="t('customFields.fieldKey')" />
        <el-table-column prop="type" :label="t('customFields.fieldType')" width="130" />
        <el-table-column :label="t('customFields.required')" width="110">
          <template #default="{ row }">{{ row.required ? t("common.yes") : t("common.no") }}</template>
        </el-table-column>
        <el-table-column label="Unique" width="110">
          <template #default="{ row }">{{ row.unique ? t("common.yes") : t("common.no") }}</template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="220">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="openEdit(row)">{{ t("common.edit") }}</el-button>
              <el-popconfirm :title="t('customFields.removeField')" @confirm="customFieldStore.removeField(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">{{ t("common.delete") }}</el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <AddCustomField v-model:open="dialogOpen" :editing-field="editing" @save="saveField" />
  </div>
</template>
