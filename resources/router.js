import Vue from "vue";
import VueRouter from 'vue-router';

import index from './pages/index';
import report from './pages/report';

let lang = ['fa', 'en'];

let map = [
    {path: '*', name: 'home', component: index},
    {path: '/report', name: 'report', component: report}
];

let routes = [];

for (let i = 0; i < map.length; i++) {
    routes.push(map[i]);
    for (let j = 0; j < lang.length; j++) {
        let path = map[i].path.startsWith('/') ? map[i].path : `/${map[i].path}`;
        routes.push({
            path: `/${lang[j]}${path}`,
            name: `/${lang[j]}_${map[i].name}`,
            component: map[i].component,
            props: map[i].props || false
        });
    }
}

Vue.use(VueRouter);

const router = new VueRouter({
    base: '/',
    mode: 'history',
    routes: routes,
});

export default router;
