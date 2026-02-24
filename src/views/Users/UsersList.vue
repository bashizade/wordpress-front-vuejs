<script setup>
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import dayjs from "dayjs";
import PageHeader from "@/components/common/PageHeader.vue";
import UserCreate from "@/views/Users/UserCreate.vue";
import UserEdit from "@/views/Users/UserEdit.vue";
import UserDeleteDialog from "@/components/Users/UserDeleteDialog.vue";
import { useUserStore } from "@/stores/userStore";
import { useCustomFieldStore } from "@/stores/customFieldStore";

const { t } = useI18n();
const userStore = useUserStore();
const customFieldStore = useCustomFieldStore();

const createOpen = ref(false);
const editOpen = ref(false);
const deleteOpen = ref(false);
const deletingUser = ref(null);

const sortOptions = computed(() => [
  { label: t("users.sortDate"), value: "registered_date" },
  { label: t("users.sortEmail"), value: "email" }
]);

const schema = computed(() => customFieldStore.sortedSchema);

const onCreate = async (payload) => {
  await userStore.createUser(payload);
};

const onOpenEdit = async (row) => {
  await userStore.fetchUser(row.id);
  editOpen.value = true;
};

const onEdit = async (payload) => {
  await userStore.updateUser(payload.id, payload);
};

const onOpenDelete = (row) => {
  deletingUser.value = row;
  deleteOpen.value = true;
};

const onDelete = async () => {
  if (!deletingUser.value) return;
  await userStore.removeUser(deletingUser.value.id);
  deleteOpen.value = false;
  deletingUser.value = null;
};

onMounted(async () => {
  await customFieldStore.fetchSchema();
  await userStore.fetchUsers();
});
</script>

<template>
  <div>
    <PageHeader :title="t('users.title')" :subtitle="t('users.subtitle')">
      <div class="flex gap-2">
        <router-link :to="{ name: 'users.custom-fields' }">
          <el-button>{{ t("menu.customFields") }}</el-button>
        </router-link>
        <el-button type="primary" @click="createOpen = true">{{ t("users.showCreate") }}</el-button>
      </div>
    </PageHeader>

    <div class="panel mb-4 p-4">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
        <el-input
          v-model="userStore.filters.search"
          :placeholder="t('users.searchPlaceholder')"
          @keyup.enter="userStore.setFilters({ page: 1 })"
        />
        <el-select v-model="userStore.filters.orderby">
          <el-option v-for="item in sortOptions" :key="item.value" :label="item.label" :value="item.value" />
        </el-select>
        <el-select v-model="userStore.filters.order">
          <el-option label="DESC" value="desc" />
          <el-option label="ASC" value="asc" />
        </el-select>
        <el-button @click="userStore.setFilters({ page: 1 })">{{ t("common.search") }}</el-button>
      </div>
    </div>

    <div class="panel p-4">
      <el-table v-loading="userStore.loading" :data="userStore.users" stripe>
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="username" :label="t('users.username')" />
        <el-table-column prop="email" :label="t('users.email')" />
        <el-table-column :label="t('users.roles')" width="200">
          <template #default="{ row }">
            <div class="flex flex-wrap gap-1">
              <el-tag v-for="role in row.roles || []" :key="`${row.id}-${role}`" size="small">{{ role }}</el-tag>
            </div>
          </template>
        </el-table-column>
        <el-table-column :label="t('users.status')" width="120">
          <template #default="{ row }">{{ row.meta?._account_status || "active" }}</template>
        </el-table-column>
        <el-table-column :label="t('users.registeredDate')" width="170">
          <template #default="{ row }">
            {{ dayjs(row.registered_date || row.date).format("YYYY-MM-DD") }}
          </template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="220">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="onOpenEdit(row)">{{ t("common.edit") }}</el-button>
              <el-button size="small" type="danger" @click="onOpenDelete(row)">{{ t("common.delete") }}</el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
      <div class="mt-4 flex justify-end">
        <el-pagination
          background
          layout="prev, pager, next"
          :current-page="userStore.filters.page"
          :page-size="userStore.filters.per_page"
          :total="userStore.pagination.total"
          @current-change="(page) => userStore.setFilters({ page })"
        />
      </div>
    </div>

    <UserCreate v-model:open="createOpen" :schema="schema" @submit="onCreate" />
    <UserEdit v-model:open="editOpen" :schema="schema" :user="userStore.currentUser" :meta="userStore.currentMeta" @submit="onEdit" />
    <UserDeleteDialog
      v-model:open="deleteOpen"
      :username="deletingUser?.username || ''"
      @confirm="onDelete"
    />
  </div>
</template>
