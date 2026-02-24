<script setup>
import { onMounted, reactive, ref } from "vue";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { customerApi } from "@/api/woocommerce";

const loading = ref(false);
const list = ref([]);
const filters = reactive({
  page: 1,
  per_page: 10,
  search: ""
});
const dialogOpen = ref(false);
const editingId = ref(null);
const form = reactive({
  email: "",
  first_name: "",
  last_name: "",
  username: ""
});

const fetchCustomers = async () => {
  loading.value = true;
  try {
    list.value = await customerApi.list(filters);
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  editingId.value = null;
  form.email = "";
  form.first_name = "";
  form.last_name = "";
  form.username = "";
  dialogOpen.value = true;
};

const openEdit = (row) => {
  editingId.value = row.id;
  form.email = row.email;
  form.first_name = row.first_name;
  form.last_name = row.last_name;
  form.username = row.username;
  dialogOpen.value = true;
};

const save = async () => {
  if (editingId.value) {
    await customerApi.update(editingId.value, form);
    ElMessage.success("Customer updated");
  } else {
    await customerApi.create(form);
    ElMessage.success("Customer created");
  }
  dialogOpen.value = false;
  await fetchCustomers();
};

const remove = async (id) => {
  await customerApi.remove(id, true);
  ElMessage.success("Customer removed");
  await fetchCustomers();
};

onMounted(fetchCustomers);
</script>

<template>
  <div>
    <PageHeader title="Customers" subtitle="Manage WooCommerce customers">
      <el-button type="primary" @click="openCreate">Create Customer</el-button>
    </PageHeader>

    <div class="panel mb-4 p-4">
      <div class="flex gap-3">
        <el-input v-model="filters.search" placeholder="Search customers..." @keyup.enter="fetchCustomers" />
        <el-button @click="fetchCustomers">Search</el-button>
      </div>
    </div>

    <div class="panel p-4">
      <el-table v-loading="loading" :data="list" stripe>
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="email" label="Email" />
        <el-table-column label="Name">
          <template #default="{ row }">{{ row.first_name }} {{ row.last_name }}</template>
        </el-table-column>
        <el-table-column prop="username" label="Username" width="150" />
        <el-table-column label="Actions" width="220">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="openEdit(row)">Edit</el-button>
              <el-popconfirm title="Delete customer?" @confirm="remove(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">Delete</el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>
      <div class="mt-4 flex justify-end">
        <el-pagination
          background
          layout="prev, pager, next"
          :current-page="filters.page"
          :page-size="filters.per_page"
          :total="filters.page * filters.per_page + (list.length === filters.per_page ? 1 : 0)"
          @current-change="(page) => { filters.page = page; fetchCustomers(); }"
        />
      </div>
    </div>

    <el-dialog v-model="dialogOpen" :title="editingId ? 'Edit Customer' : 'Create Customer'" width="520">
      <el-form label-position="top">
        <el-form-item label="Email">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="First Name">
          <el-input v-model="form.first_name" />
        </el-form-item>
        <el-form-item label="Last Name">
          <el-input v-model="form.last_name" />
        </el-form-item>
        <el-form-item label="Username">
          <el-input v-model="form.username" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogOpen = false">Cancel</el-button>
        <el-button type="primary" @click="save">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>
