<script setup>
import { ref } from 'vue';
import { router } from '@statamic/cms/inertia';
import { Button, Card, ConfirmationModal, EmptyStateMenu, EmptyStateItem, Header, Table, TableColumns, TableColumn, TableRows,TableRow, TableCell } from '@statamic/cms/ui';

const props = defineProps({
  redirects: Array,
  statusCodes: Object,
});

const isConfirming = ref(false);
const redirectToDelete = ref(null);

const deleteRedirect = (id) => {
  redirectToDelete.value = id;
  isConfirming.value = true;
};

const confirmDelete = () => {
  if (redirectToDelete.value) {
    router.delete(cp_url(`redirects/${redirectToDelete.value}`));
    redirectToDelete.value = null;
  }
};

</script>

<template>
  <div>
    <Header title="Redirects">
      <Button text="Create redirect" variant="primary" :href="cp_url('redirects/create')" />
    </Header>
    <template v-if="redirects.length === 0">
      <EmptyStateMenu heading="Start by creating your first redirect" >
        <EmptyStateItem
            icon="link"
            heading="Create redirect"
            description="Create a redirect from one URL to another. Use wildcards to match multiple URLs."
        />
      </EmptyStateMenu>
    </template>
    <template v-else>
      <Card>
        <Table>
          <TableColumns>
            <TableColumn>Source</TableColumn>
            <TableColumn>Destination</TableColumn>
            <TableColumn>Status code</TableColumn>
            <TableColumn>Actions</TableColumn>
          </TableColumns>
          <TableRows>
            <TableRow v-for="redirect in redirects" :key="redirect.id">
              <TableCell>{{ redirect.source}}</TableCell>
              <TableCell>{{ redirect.destination }}</TableCell>
              <TableCell>{{redirect.status_code}}</TableCell>
              <TableCell class="space-x-4">
                <Button icon="pencil" variant="primary" text="Edit" :href="cp_url(`redirects/${redirect.id}/edit`)" />
                <Button icon="trash" variant="danger" text="Delete" @click="deleteRedirect(redirect.id)" />
              </TableCell>
            </TableRow>
          </TableRows>
        </Table>
      </Card>

      <ConfirmationModal
          body-text="Are you sure you want to delete this redirect?"
          v-model:open="isConfirming"
          @confirm="confirmDelete"
          danger="true"
      />
    </template>
  </div>
</template>