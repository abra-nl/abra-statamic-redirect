<script setup>
import { Button, Card, Header } from '@statamic/cms/ui';
import RedirectForm from "../components/RedirectForm.vue";

const props = defineProps({
  statusCodes: Object,
});

</script>

<template>
  <div>
    <Header :title="__('Create redirect')">
      <Button :text="__('Back to list')" variant="primary" :href="cp_url('redirects')" />
    </Header>
    <Card>
      <RedirectForm
        :action="cp_url('redirects/store')"
        :submit-text="__('Create redirect')"
        :status-codes="statusCodes"
        :redirect="null"
        method="post"
      />
    </Card>
  </div>
</template>