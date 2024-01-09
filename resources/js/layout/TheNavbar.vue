<template>
  <div>
    <div
      class="q-gutter-y-md"
    >
      <q-tabs
        v-model="tab"
        align="left"
        class="bg-primary text-white shadow-2"
      >
        <q-tab
          name="mails"
          label="Mails"
        />
        <q-tab
          name="alarms"
          label="Alarms"
        />
        <q-tab
          name="movies"
          label="Movies"
        />
        <q-tab
          name="photos"
          label="Photos"
        />
        <q-tab
          name="videos"
          label="Videos"
        />
        <q-tab
          name="addressbook"
          label="Address Book"
        />
      </q-tabs>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      tab: "mails",
    };
  },
};
</script>
