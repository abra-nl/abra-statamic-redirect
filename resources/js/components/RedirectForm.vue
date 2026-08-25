<script setup>
import { Button, Field, Input, Select} from '@statamic/cms/ui';

const props = defineProps({
  method: String,
  action: String,
  redirect: Object,
  submitText: String,
  statusCodes: Object,
});

import { useForm } from '@statamic/cms/inertia'

const form = useForm({
  host: props.redirect?.host ?? null,
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
    <Field :label="__('Host')" name="host" instructions-below :instructions="__('Leave blank to match this path on any domain. Set a host (e.g. abra.nl) to only match requests arriving on that domain.')" :error="form.errors.host">
      <Input type="text" name="host" v-model="form.host" />
    </Field>

    <Field required  :label="__('Source')" name="source" instructions-below :instructions="__('The URL path to redirect from. Do not include the domain.')" :error="form.errors.source">
      <Input type="text" name="source" v-model="form.source"  />
    </Field>

    <Field required :label="__('Destination')" name="destination" :instructions="__('The URL to redirect to. Can be a full URL or a relative path.')" instructions-below :error="form.errors.destination">
      <Input type="text" name="destination" v-model="form.destination" />
    </Field>

    <Field required :label="__('Status code')" name="status_code" :instructions="__('The HTTP status code to use for the redirect.')" instructions-below :error="form.errors.status_code">
      <Select :label="__('Status code')" name="status_code" :options="options" v-model="form.status_code" />
    </Field>

    <Button type="submit" variant="primary" :text="submitText" />
  </form>
</template>
