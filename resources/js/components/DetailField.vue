<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <i class="ffc-text-5xl" :class="field.value"></i>
    </template>
  </PanelItem>
</template>

<script>
export default {
  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],
};
</script>
<style>
.heroicon-index svg {
  width: 60px;
  height: 60px;
}
</style>
