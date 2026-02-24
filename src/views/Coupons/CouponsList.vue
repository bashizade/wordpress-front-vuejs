<script setup>
import { onMounted, reactive, ref } from "vue";
import dayjs from "dayjs";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { couponApi } from "@/api/woocommerce";

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
  code: "",
  amount: "0",
  discount_type: "fixed_cart",
  date_expires: ""
});

const fetchCoupons = async () => {
  loading.value = true;
  try {
    list.value = await couponApi.list(filters);
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  editingId.value = null;
  form.code = "";
  form.amount = "0";
  form.discount_type = "fixed_cart";
  form.date_expires = "";
  dialogOpen.value = true;
};

const openEdit = (row) => {
  editingId.value = row.id;
  form.code = row.code;
  form.amount = row.amount;
  form.discount_type = row.discount_type;
  form.date_expires = row.date_expires?.slice(0, 10) || "";
  dialogOpen.value = true;
};

const save = async () => {
  const payload = {
    ...form,
    date_expires: form.date_expires || null
  };
  if (editingId.value) {
    await couponApi.update(editingId.value, payload);
    ElMessage.success("Coupon updated");
  } else {
    await couponApi.create(payload);
    ElMessage.success("Coupon created");
  }
  dialogOpen.value = false;
  await fetchCoupons();
};

const remove = async (id) => {
  await couponApi.remove(id, true);
  ElMessage.success("Coupon removed");
  await fetchCoupons();
};

onMounted(fetchCoupons);
</script>

<template>
  <div>
    <PageHeader title="Coupons" subtitle="Manage WooCommerce coupons">
      <el-button type="primary" @click="openCreate">Create Coupon</el-button>
    </PageHeader>

    <div class="panel mb-4 p-4">
      <div class="flex gap-3">
        <el-input v-model="filters.search" placeholder="Search code..." @keyup.enter="fetchCoupons" />
        <el-button @click="fetchCoupons">Search</el-button>
      </div>
    </div>

    <div class="panel p-4">
      <el-table v-loading="loading" :data="list" stripe>
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="code" label="Code" />
        <el-table-column prop="discount_type" label="Type" width="140" />
        <el-table-column prop="amount" label="Amount" width="120" />
        <el-table-column label="Expires" width="160">
          <template #default="{ row }">
            {{ row.date_expires ? dayjs(row.date_expires).format("YYYY-MM-DD") : "Never" }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="220">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="openEdit(row)">Edit</el-button>
              <el-popconfirm title="Delete coupon?" @confirm="remove(row.id)">
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
          @current-change="(page) => { filters.page = page; fetchCoupons(); }"
        />
      </div>
    </div>

    <el-dialog v-model="dialogOpen" :title="editingId ? 'Edit Coupon' : 'Create Coupon'" width="520">
      <el-form label-position="top">
        <el-form-item label="Code">
          <el-input v-model="form.code" />
        </el-form-item>
        <el-form-item label="Discount Type">
          <el-select v-model="form.discount_type">
            <el-option value="fixed_cart" label="Fixed cart" />
            <el-option value="percent" label="Percent" />
            <el-option value="fixed_product" label="Fixed product" />
          </el-select>
        </el-form-item>
        <el-form-item label="Amount">
          <el-input v-model="form.amount" />
        </el-form-item>
        <el-form-item label="Expiry Date">
          <el-date-picker v-model="form.date_expires" value-format="YYYY-MM-DD" type="date" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogOpen = false">Cancel</el-button>
        <el-button type="primary" @click="save">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>
