<script setup>
import { computed, ref } from "vue";
import { useAttributeStore } from "@/stores/attributeStore";

const props = defineProps({
  attributes: {
    type: Array,
    default: () => []
  },
  basePrice: {
    type: [Number, String],
    default: 0
  }
});

const attributeStore = useAttributeStore();
const previewBasePrice = ref(Number(props.basePrice || 0));

const buildCombos = (groups) => {
  if (!groups.length) return [];
  const recurse = (index, acc) => {
    if (index === groups.length) return [acc];
    const next = [];
    groups[index].options.forEach((option) => {
      next.push(...recurse(index + 1, [...acc, { name: groups[index].name, id: groups[index].id, option }]));
    });
    return next;
  };
  return recurse(0, []);
};

const previewRows = computed(() => {
  const variationAttributes = props.attributes.filter((item) => item.variation && item.options?.length > 0);
    const combos = buildCombos(variationAttributes);
    return combos.map((combo) => {
      const modifier = combo.reduce((sum, item) => {
        if (!item.id) return sum;
        const attributeTerms = attributeStore.termsByAttribute[item.id] || [];
        const term = attributeTerms.find((termItem) => termItem.name === item.option);
        if (!term) return sum;
        const meta = attributeStore.getTermMeta(item.id, term.id);
        return sum + Number(meta.price_modifier || 0);
      }, 0);
    const base = Number(previewBasePrice.value || 0);
    const finalPrice = base + (base * modifier) / 100;
    return {
      key: combo.map((item) => `${item.name}:${item.option}`).join(" | "),
      modifier,
      finalPrice: finalPrice.toFixed(2)
    };
  });
});
</script>

<template>
  <div class="panel p-4">
    <div class="mb-3 flex items-end gap-3">
      <h3 class="font-semibold">Variation Price Preview</h3>
      <el-input-number v-model="previewBasePrice" :min="0" :step="1" />
    </div>
    <el-table :data="previewRows" max-height="260" size="small">
      <el-table-column prop="key" label="Combination" />
      <el-table-column prop="modifier" label="Modifier %" width="120" />
      <el-table-column prop="finalPrice" label="Calculated Price" width="160" />
    </el-table>
  </div>
</template>
