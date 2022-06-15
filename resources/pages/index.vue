<template>
  <div class="d-flex justify-center align-center fill-height">
    <v-card
      v-if="!loading"
      class="my-10"
      min-width="600px"
      elevation="2"
      outlined
    >
      <v-card-title>Select files to start</v-card-title>
      <v-divider />
      <v-card-text>
        <v-file-input
          label="Video"
          v-model="object.video"
          outlined
          :error-messages="errors.video"
          accept=".mp4"
          prepend-icon="mdi-video"
        />

        <v-file-input
          label="Audio tracks"
          v-model="object.audios"
          outlined
          multiple
          @change="on_audios_change"
          messages="You can choose one or more audio track"
          accept=".mp4,.mp3"
          prepend-icon="mdi-volume-high"
        />

        <div class="d-table w-100" v-if="object.audios_list">
          <div
            class="d-table-row"
            v-for="(x, i) in object.audios_list"
            :key="i"
          >
            <div class="d-table-cell pa-1">
              <v-icon>mdi-music</v-icon>
            </div>
            <div class="d-table-cell pa-1">
              <v-text-field
                outlined
                dense
                label="Title"
                :error-messages="errors[`audios.${i}.title`]"
                v-model="x.name"
                :hide-details="errors[`audios.${i}.title`] == null"
              />
            </div>
            <div class="d-table-cell pa-1" style="max-width: 80px">
              <v-text-field
                outlined
                dense
                :error-messages="errors[`audios.${i}.language`]"
                label="Language code"
                v-model="x.language"
                :hide-details="errors[`audios.${i}.language`] == null"
              />
            </div>
          </div>
        </div>
      </v-card-text>
      <v-divider />
      <v-card-text v-if="object.menu === 'expert'">
        Select qualities to export
        <v-checkbox
          v-for="(x, i) in options.format"
          v-model="object.qualities"
          :label="`${x.bitrate} - (${x.width} * ${x.height})`"
          :key="i"
          :value="x"
          hide-details
        />

        <div v-if="errors.qualities" class="error--text mt-5 font-weight-bold">
          {{ errors.qualities.length > 0 ? errors.qualities[0] : "" }}
        </div>

        <v-text-field
          label="HLS Time (sec)"
          v-model="object.hls_time"
          dense
          outlined
          :error-messages="errors.hls_time"
          class="mt-10"
        />
        <v-text-field
          label="Audio type"
          v-model="object.audio_type"
          dense
          :error-messages="errors.audio_type"
          outlined
        />
        <v-text-field
          label="Audio quality"
          v-model="object.audio_quality"
          :error-messages="errors.audio_quality"
          dense
          outlined
        />
        <v-text-field
          label="Timeout"
          v-model="object.timeout"
          :error-messages="errors.timeout"
          dense
          outlined
        />
        <v-text-field
          label="Threads"
          v-model="object.threads"
          :error-messages="errors.threads"
          dense
          outlined
        />
      </v-card-text>

      <v-divider />
      <v-card-actions>
        <span>
          <v-select
            dense
            outlined
            hide-details
            :items="options.menu"
            v-model="object.menu"
            style="width: 150px"
        /></span>
        <v-spacer />
        <v-btn @click="start" color="primary">
          <v-icon>mdi-chevron-right</v-icon>
          convert
        </v-btn>
      </v-card-actions>
    </v-card>
    <v-card v-else class="my-10" min-width="600px" elevation="2" outlined>
      <v-card-title>Uploading files ...</v-card-title>
      <v-divider />
      <v-card-text>
        <div class="d-flex">
          {{ upload.percent }}%
          <v-spacer />
          {{ formatBytes(upload.loaded) }} / {{ formatBytes(upload.total) }}
        </div>
        <v-progress-linear :value="upload.percent" />
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
export default {
  name: "index",

  data: () => ({
    loading: false,
    upload: {
      percent: 0,
      loaded: 0,
      total: 0,
    },
    errors: {},
    object: {
      video: null,
      qualities: [
        { bitrate: 750, width: 854, height: 480 },
        { bitrate: 2048, width: 1280, height: 720 },
        { bitrate: 4096, width: 1920, height: 1080 },
        { bitrate: 150, width: 426, height: 240 },
      ],
      menu: "easy",
      hls_time: 10,
      audio_type: "aac",
      audio_quality: "90",
      timeout: 10547200,
      threads: 12,
      audios: [],
      audios_list: [],
    },
    options: {
      menu: ["expert", "easy"],
      format: [
        {
          bitrate: 95,
          width: 256,
          height: 144,
        },
        {
          bitrate: 150,
          width: 426,
          height: 240,
        },
        {
          bitrate: 276,
          width: 640,
          height: 360,
        },
        {
          bitrate: 750,
          width: 854,
          height: 480,
        },
        {
          bitrate: 2048,
          width: 1280,
          height: 720,
        },
        {
          bitrate: 4096,
          width: 1920,
          height: 1080,
        },
        {
          bitrate: 6144,
          width: 2560,
          height: 1440,
        },
        {
          bitrate: 17408,
          width: 3840,
          height: 2160,
        },
      ],
    },
  }),

  created() {
    this.$api.get("report").then(({ data }) => console.log(data));
  },

  methods: {
    formatBytes(bytes, decimals = 2) {
      if (bytes === 0) return "0 Bytes";

      const k = 1024;
      const dm = decimals < 0 ? 0 : decimals;
      const sizes = ["Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];

      const i = Math.floor(Math.log(bytes) / Math.log(k));

      return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
    },
    on_audios_change(e) {
      this.object.audios_list = [];
      for (let i = 0; i < this.object.audios.length; i++) {
        this.object.audios_list.push({
          name: this.object.audios[i].name,
          language: "en",
          default: false,
        });
      }
    },
    start() {
      this.loading = true;
      this.errors = [];

      let config = {
        timeout: 600000,
        headers: { "Content-Type": "multipart/form-data" },
        onUploadProgress: function (progressEvent) {
          this.upload.loaded = progressEvent.loaded;
          this.upload.total = progressEvent.total;

          this.upload.percent = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total
          );
          this.$forceUpdate();
        }.bind(this),
      };

      let form = new FormData();
      form.append("video", this.object.video);
      form.append("hls_time", this.object.hls_time);
      form.append("audio_type", this.object.audio_type);
      form.append("audio_quality", this.object.audio_quality);
      form.append("timeout", this.object.timeout);
      form.append("threads", this.object.threads);

      for (let i = 0; i < this.object.audios_list.length; i++) {
        form.append(`audios[${i}][title]`, this.object.audios_list[i].name);
        form.append(
          `audios[${i}][language]`,
          this.object.audios_list[i].language
        );
        form.append(`audios_${i}`, this.object.audios[i]);
      }

      for (let i = 0; i < this.object.qualities.length; i++) {
        form.append(
          `qualities[${i}][bitrate]`,
          this.object.qualities[i].bitrate
        );
        form.append(`qualities[${i}][width]`, this.object.qualities[i].width);
        form.append(`qualities[${i}][height]`, this.object.qualities[i].height);
      }

      this.$api
        .post("converter", form, config)
        .then(({ data }) => {
          if (data.status == "active") {
            this.$router.push({
              path: "report",
              query: { unique: data.uniqueId },
            });
          } else {
            console.error("error", data);
          }
        })
        .catch(({ response }) => {
          if (response.status == 422) {
            this.errors = response.data;
          }
        })
        .finally(() => (this.loading = false));
    },
  },
};
</script>

<style scoped>
</style>
