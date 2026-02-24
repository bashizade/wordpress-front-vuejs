<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import dayjs from "dayjs";
import PageHeader from "@/components/common/PageHeader.vue";
import TableSkeleton from "@/components/common/TableSkeleton.vue";
import { usePostStore } from "@/stores/postStore";
import { postApi } from "@/api/wordpress";

const router = useRouter();
const postStore = usePostStore();
const quickEditVisible = ref(false);
const editingPostId = ref(null);
const quickEdit = reactive({
  title: "",
  status: "draft"
});

const load = async () => {
  await postStore.fetchPosts();
};

const onSearch = async () => {
  await postStore.setFilters({ page: 1 });
};

const openQuickEdit = (post) => {
  editingPostId.value = post.id;
  quickEdit.title = post.title?.rendered || "";
  quickEdit.status = post.status || "draft";
  quickEditVisible.value = true;
};

const saveQuickEdit = async () => {
  await postStore.updatePost(editingPostId.value, {
    title: quickEdit.title,
    status: quickEdit.status
  });
  quickEditVisible.value = false;
};

const onDelete = async (id) => {
  await postStore.deletePost(id);
};

const fetchSinglePostAndEdit = async (id) => {
  await postApi.get(id);
  router.push({ name: "posts.edit", params: { id } });
};

onMounted(load);
</script>

<template>
  <div>
    <PageHeader title="Posts" subtitle="Manage WordPress posts">
      <router-link :to="{ name: 'posts.create' }">
        <el-button type="primary">Create Post</el-button>
      </router-link>
    </PageHeader>

    <div class="panel mb-4 p-4">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <el-input
          v-model="postStore.filters.search"
          placeholder="Search posts..."
          clearable
          @keyup.enter="onSearch"
        />
        <el-select v-model="postStore.filters.status" @change="onSearch">
          <el-option label="Any Status" value="any" />
          <el-option label="Published" value="publish" />
          <el-option label="Draft" value="draft" />
          <el-option label="Pending" value="pending" />
        </el-select>
        <el-button @click="onSearch">Apply Filters</el-button>
      </div>
    </div>

    <TableSkeleton v-if="postStore.loading" />
    <div v-else class="panel p-4">
      <el-table :data="postStore.list" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="Title">
          <template #default="{ row }">
            <span v-html="row.title?.rendered" />
          </template>
        </el-table-column>
        <el-table-column label="Status" prop="status" width="120" />
        <el-table-column label="Date" width="180">
          <template #default="{ row }">
            {{ dayjs(row.date).format("YYYY-MM-DD HH:mm") }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="280">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="fetchSinglePostAndEdit(row.id)">Edit Page</el-button>
              <el-button size="small" @click="openQuickEdit(row)">Quick Edit</el-button>
              <el-popconfirm title="Delete this post?" @confirm="onDelete(row.id)">
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
          :current-page="postStore.filters.page"
          :page-size="postStore.filters.per_page"
          :total="postStore.total"
          @current-change="(page) => postStore.setFilters({ page })"
        />
      </div>
    </div>

    <el-dialog v-model="quickEditVisible" title="Quick Edit Post" width="520">
      <el-form label-position="top">
        <el-form-item label="Title">
          <el-input v-model="quickEdit.title" />
        </el-form-item>
        <el-form-item label="Status">
          <el-select v-model="quickEdit.status">
            <el-option label="Publish" value="publish" />
            <el-option label="Draft" value="draft" />
            <el-option label="Pending" value="pending" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="quickEditVisible = false">Cancel</el-button>
        <el-button type="primary" @click="saveQuickEdit">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>
