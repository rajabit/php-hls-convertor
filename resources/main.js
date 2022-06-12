import Vue from 'vue';
import api from "./plugin/api";
import router from "./router";
import mixins from "./mixin";
import vuetify from './vuetify';

Vue.component('doc', require('./layout/app.vue').default);

Vue.mixin(mixins);

new Vue({
    api,
    router,
    vuetify,
}).$mount('#app');
