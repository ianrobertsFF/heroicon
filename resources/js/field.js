/* eslint-disable global-require */
Nova.booting((Vue) => {
  Vue.component('index-fontawesome', require('./components/IndexField.vue').default);
  Vue.component('detail-fontawesome', require('./components/DetailField.vue').default);
  Vue.component('form-fontawesome', require('./components/FormField.vue').default);
});
