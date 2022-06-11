import Vue from 'vue'
import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'
import '@mdi/font/css/materialdesignicons.css'
import colors from 'vuetify/lib/util/colors'

Vue.use(Vuetify)

const opts = {
    icons: {
        iconfont: 'mdi'
    },
    breakpoint: {scrollbarWidth: 3},
    theme: {
        dark: true,
        themes: {
            dark: {
                primary: colors.deepOrange.darken2
            }
        }
    },
}

export default new Vuetify(opts)
