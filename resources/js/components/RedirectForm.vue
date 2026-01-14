<script setup>
import { Button, Field, Input, Select} from '@statamic/cms/ui';

const props = defineProps({
  method: String,
  action: String,
  redirect: Object,
  submitText: String,
  statusCodes: Object,
});

import { useForm } from '@inertiajs/vue3'

const form = useForm({
  source: props.redirect?.source ?? null,
  destination: props.redirect?.destination ?? null,
  status_code: props.redirect?.status_code ?? 301,
})

const options = Object.entries(props.statusCodes).map(([value, label]) => ({
  label,
  value
}));

</script>

<template>
  <form @submit.prevent="method == 'post' ? form.post(action) : form.patch(action)" class="space-y-4">
    <Field required  label="Source" name="source" instructions-below instructions="The URL path to redirect from. Do not include the domain.">
      <Input type="text" name="source" v-model="form.source"  />
    </Field>

    <Field required label="Destination" name="destination" instructions="The URL to redirect to. Can be a full URL or a relative path." instructions-below>
      <Input type="text" name="destination" v-model="form.destination" />
    </Field>

    <Field label="Status code" name="status_code" instructions="The HTTP status code to use for the redirect." instructions-below>
      <Select label="Status code" name="status_code" :options="options" v-model="form.status_code" />
    </Field>

    <Button type="submit" variant="primary" :text="submitText" />
  </form>
</template>
