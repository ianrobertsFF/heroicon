<template>
  <i class="ffc-text-3xl" :class="field.value"></i>
</template>

<script>
export default {
  props: ['resourceName', 'field'],
};
</script>
<style>
.heroicon-list svg {
  width: 24px;
  height: 24px;
}
</style>
