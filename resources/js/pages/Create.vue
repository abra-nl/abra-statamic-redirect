<script setup>
import { Button, Card, Header } from '@statamic/cms/ui';
import RedirectForm from "../components/RedirectForm.vue";

const props = defineProps({
  statusCodes: Object,
});

</script>

<template>
  <div>
    <Header title="Create redirect">
      <Button text="Back to list" variant="primary" :href="cp_url('redirects')" />
    </Header>
    <Card>
      <RedirectForm
        :action="cp_url('redirects/store')"
        :submit-text="'Create redirect'"
        :status-codes="statusCodes"
        :redirect="null"
      />
    </Card>
  </div>
</template>