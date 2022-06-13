<template>
  <div class="d-flex justify-center align-center fill-height">
    <v-card min-width="550" v-if="status != null" elevation="2" outlined>
      <v-card-title class="overline" v-text="status.status" />
      <v-divider v-if="status.percentage != null" />
      <v-card-text v-if="status.percentage != null">
        <v-progress-linear
          :value="status.percentage"
          color="primary"
          height="25"
          class="rounded"
          :indeterminate="status.percentage === 'indeterminate'"
        >
          <template
            v-if="status.percentage !== 'indeterminate'"
            v-slot:default="{ value }"
          >
            <strong>{{ Math.ceil(value) }}%</strong>
          </template>
        </v-progress-linear>
      </v-card-text>
      <v-divider
        v-if="status.status === 'failed' || status.status === 'success'"
      />
      <v-card-actions>
        {{ status.message }}
        <v-spacer />
        <v-btn
          v-if="status.status === 'success' && status.download != null"
          :href="status.download"
          color="success"
        >
          <v-icon>mdi-download</v-icon>
          Download
        </v-btn>
      </v-card-actions>
    </v-card>
  </div>
</template>

<script>
export default {
  name: "report",

  data: () => ({
    loading: false,
    status: null,
    interval: null,
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

  destroyed() {
    clearInterval(this.interval);
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
        .then(({ data }) => {
          this.status = data;
          if (data.status === "success" || data.status === "failed") {
            clearInterval(this.interval);
          }
        });
    },
  },
};
</script>
