<template>
  <div class="d-flex justify-center align-center fill-height">
    <v-card min-width="550" v-if="status != null" elevation="2" outlined>
      <v-card-title class="overline" v-text="status.status" />
      <v-divider />
      <v-card-text>
        <v-progress-linear
          :value="status.percentage"
          color="primary"
          height="25"
          class="rounded"
        >
          <template v-slot:default="{ value }">
            <strong>{{ Math.ceil(value) }}%</strong>
          </template>
        </v-progress-linear>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
export default {
  name: "report",

  data: () => ({
    loading: false,
    status: null,
    interval: null
  }),

  computed: {
    uniqueId() {
      return this.$route.query.unique;
    },
  },

  created() {
    this.fetch();
  },

  mounted() {
    this.interval = setInterval(() => {
      this.fetch();
    }, 1000);
  },

  methods: {
    fetch() {
      this.loading = true;
      this.$api
        .get(`report`, {
          params: {
            uniqueId: this.uniqueId,
          },
        })
        .then(({ data }) => (this.status = data));
    },
  },
};
</script>
