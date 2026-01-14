import Index from './pages/Index.vue';

Statamic.booting(() => {
    Statamic.$inertia.register('abra-redirects::Index', Index);
});