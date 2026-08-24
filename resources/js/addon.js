import Index from './pages/Index.vue';
import Create from "./pages/Create.vue";
import Edit from "./pages/Edit.vue";

Statamic.booting(() => {
    Statamic.$inertia.register('abra-redirects::Index', Index);
    Statamic.$inertia.register('abra-redirects::Create', Create);
    Statamic.$inertia.register('abra-redirects::Edit', Edit);
});