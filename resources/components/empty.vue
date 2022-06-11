<template>

    <div id="container" class="empty-container">
        <img src="/empty.svg" class="my-5" alt="empty" width="100" height="100"/>
        {{ text || $t('text.list_is_empty') }}
    </div>

</template>

<script>
    export default {
        name: "empty",

        props: ['text', 'height'],

        mounted() {
            let container = document.getElementById("container");
            container.style.height = `${(this.height || 300)}px`;
        },

        watch: {
            height: function (val) {
                let container = document.getElementById("container");
                container.style.height = `${(val || 300)}px`;
            }
        }
    }
</script>

<style scoped>
</style>
