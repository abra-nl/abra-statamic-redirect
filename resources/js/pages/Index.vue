<script setup>
import { Button, DropdownItem, Header, Listing } from '@statamic/cms/ui';

defineProps({
  jsonUrl: String,
  actionUrl: String,
  statusCodes: Object,
});
</script>

<template>
  <div>
    <Header :title="__('Redirects')">
      <Button :text="__('Create redirect')" variant="primary" :href="cp_url('redirects/create')" />
    </Header>

    <Listing
        :url="jsonUrl"
        :action-url="actionUrl"
        push-query
    >
      <template #cell-host="{ value }">
        {{ value || __('Any') }}
      </template>

      <template #prepended-row-actions="{ row: redirect }">
        <DropdownItem :text="__('Edit')" icon="pencil" :href="cp_url(`redirects/${redirect.id}/edit`)" />
      </template>
    </Listing>
  </div>
</template>
