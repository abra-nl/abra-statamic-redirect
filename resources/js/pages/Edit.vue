<script setup>
import { Button, Card, Header } from '@statamic/cms/ui';
import RedirectForm from "../components/RedirectForm.vue";

const props = defineProps({
  statusCodes: Object,
  redirect: Array
});

</script>

<template>
  <div>
    <Header title="Edit redirect">
      <Button text="Back to list" variant="primary" :href="cp_url('redirects')" />
    </Header>
    <Card>
      <RedirectForm
          :action="cp_url(`redirects/${redirect['id']}`)"
          :submit-text="'Create redirect'"
          :status-codes="statusCodes"
          :redirect="redirect"
      />
    </Card>
  </div>
</template>