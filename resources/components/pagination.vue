<template>
    <div class="w-100 text-left dir-ltr">
        <v-btn class="mr-2 my-1" fab small outlined color="primary lighten-1"
               @click="fetch(data.current_page - 1)"
               v-if="data.prev_page_url != null">
            <v-icon>mdi-chevron-left</v-icon>
        </v-btn>
        <v-btn class="mr-2 my-1" v-if="firstVisible" @click="fetch(1)" fab small outlined color="primary lighten-1">
            1
        </v-btn>
        <div class="h-100 mr-2" style="display: inline" v-if="firstDotsVisible">...</div>
        <v-btn class="mr-2 my-1" v-for="x in computedPaginate" :key="x"
               @click="fetch(x)" fab small :outlined="x !== data.current_page" color="primary lighten-1">
            {{ x }}
        </v-btn>
        <div class="h-100 mr-2" style="display: inline" v-if="lastDotsVisible">...</div>
        <v-btn class="mr-2 my-1" v-if="lastVisible" @click="fetch(data.last_page)" fab small outlined
               color="primary lighten-1">
            {{ data.last_page }}
        </v-btn>
        <v-btn class="mr-2 my-1" fab small outlined color="primary lighten-1"
               @click="fetch(data.current_page + 1)"
               v-if="data.next_page_url != null">
            <v-icon>mdi-chevron-right</v-icon>
        </v-btn>
    </div>
</template>

<script>
    export default {
        name: "pagination",

        props: ['data', 'visible'],

        data: function () {
            return {
                computedPaginate: [],
                firstVisible: false,
                firstDotsVisible: false,
                lastVisible: false,
                lastDotsVisible: false,
                last_emit: 1
            }
        },

        watch: {
            data: function () {
                let pages = [];
                let current = this.data.current_page;
                let last = this.data.last_page;
                let visible = this.visible;

                if (this.data.total <= this.data.per_page) {
                    this.firstVisible = false;
                    this.firstDotsVisible = false;
                    this.lastVisible = false;
                    this.lastDotsVisible = false;
                    this.computedPaginate = [];
                    return;
                }

                try {
                    if (current <= visible) {
                        for (let i = 1; i <= current; i++) {
                            if (!this.isInArray(pages, i)) {
                                pages.push(i);
                            }
                        }
                    } else {
                        for (let i = (current - visible); i <= current; i++) {
                            if (!this.isInArray(pages, i)) {
                                pages.push(i);
                            }
                        }
                    }
                } catch (exception) {
                    console.log(JSON.stringify(exception));
                }

                try {
                    if ((current + visible) >= last) {
                        let count = last - visible;
                        for (let i = (count < 1 ? 1 : count); i <= last; i++) {
                            if (!this.isInArray(pages, i)) {
                                pages.push(i);
                            }
                        }
                    } else {
                        for (let i = current + 1; i <= (current + visible); i++) {
                            if (!this.isInArray(pages, i)) {
                                pages.push(i);
                            }
                        }
                    }
                } catch (exception) {
                    console.log(JSON.stringify(exception));
                }

                try {
                    if (!this.isInArray(pages, 2) && last > 2) {
                        this.firstVisible = true;
                        this.firstDotsVisible = true;
                    } else if (!this.isInArray(pages, 1)) {
                        this.firstVisible = true;
                        this.firstDotsVisible = false;
                    } else {
                        this.firstVisible = false;
                        this.firstDotsVisible = false;
                    }
                } catch (exception) {
                    console.log(JSON.stringify(exception));
                }

                try {
                    if (!this.isInArray(pages, (last - 1)) && (last > 2)) {
                        this.lastVisible = true;
                        this.lastDotsVisible = true
                    } else if (!this.isInArray(pages, last)) {
                        this.lastVisible = true;
                        this.lastDotsVisible = false;
                    } else {
                        this.lastVisible = false;
                        this.lastDotsVisible = false;
                    }
                } catch (exception) {
                    console.log(JSON.stringify(exception));
                }

                this.computedPaginate = pages;
            }
        },

        created: function () {
            this.$emit('page', 1);
        },

        methods: {
            fetch: function (page) {
                if (this.last_emit !== page) {
                    this.last_emit = page;
                    this.$emit('page', page);
                }
            }
        }
    }
</script>

<style scoped>

</style>
