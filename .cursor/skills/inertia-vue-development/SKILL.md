---
name: inertia-vue-development
description: >-
  Develops Inertia.js v2 Vue client-side applications. Activates when creating
  Vue pages, forms, or navigation; using <Link> or router; working with deferred
  props, prefetching, or polling; or when user mentions Vue with Inertia, Vue pages,
  Vue forms, or Vue navigation. Note: forms in this project use VeeValidate + Yup, not
  Inertia's form helpers.
---

# Inertia Vue Development

## When to Apply

Activate this skill when:

- Creating or modifying Vue page components for Inertia
- Working with Inertia client-side navigation using `<Link>` or `router`
- Using v2 features: deferred props, prefetching, or polling
- Building Vue-specific features with the Inertia protocol
- Note: form validation guidance is in the `inertia-vue-development` skill's Form Handling section; it uses VeeValidate + Yup, not Inertia's form helpers

## Documentation

Use `search-docs` for detailed Inertia v2 Vue patterns and documentation.

## Basic Usage

### Page Components Location

Vue page components should be placed in the `resources/js/Pages` directory.

### Page Component Structure

Important: Vue components must have a single root element.

<code-snippet name="Basic Vue Page Component" lang="vue">

<script setup>
defineProps({
    users: Array
})
</script>

<template>
    <div>
        <h1>Users</h1>
        <ul>
            <li v-for="user in users" :key="user.id">
                {{ user.name }}
            </li>
        </ul>
    </div>
</template>

</code-snippet>

## Client-Side Navigation

### Basic Link Component

Use `<Link>` for client-side navigation instead of traditional `<a>` tags:

<code-snippet name="Inertia Vue Navigation" lang="vue">

<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<script setup>
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
</script>

<template>
    <div>
        <Link :href="route('home')">Home</Link>
        <Link :href="route('users')">Users</Link>
        <Link :href="route('users.edit', user.id)">View User</Link>
    </div>
</template>

</code-snippet>

### Link with Method

<code-snippet name="Link with POST Method" lang="vue">

<script setup>
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
</script>

<template>
    <Link :href="route('logout')" method="post" as="button">
        Logout
    </Link>
</template>

</code-snippet>

### Prefetching

Prefetch pages to improve perceived performance:

<code-snippet name="Prefetch on Hover" lang="vue">

<script setup>
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
</script>

<template>
    <Link :href="route('users')" prefetch>
        Users
    </Link>
</template>

</code-snippet>

### Programmatic Navigation

<code-snippet name="Router Visit" lang="vue">

<script setup>
import { router, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

function handleClick() {
    router.visit(route('users'))
}

// Or with options
function createUser() {
    router.post(route('users.store'), { name: 'John' }, {
        onSuccess: () => console.log('Done'),
    })
}
</script>

<template>
    <Link :href="route('users')">Users</Link>
    <Link :href="route('logout')" method="post" as="button">Logout</Link>
</template>

</code-snippet>

## Form Handling

This project uses **VeeValidate + Yup** for form validation, not Inertia's `<Form>` component or `useForm` composable. Submit the validated payload with Inertia's `router`.

### VeeValidate + Yup

<code-snippet name="VeeValidate Form Example" lang="vue">

<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/yup'
import { object, string, number } from 'yup'
import { Button, InputText } from 'primevue'
import { useToast } from 'primevue/usetoast'
import { route } from 'ziggy-js'
import { useI18n } from 'vue-i18n'

const toast = useToast()
const { t } = useI18n()

const schema = toTypedSchema(
    object({
        name: string().required().max(50),
        email: string().required().email(),
        age: number().nullable().optional().min(0),
    })
)

const { handleSubmit, errors, defineField, setErrors, values } = useForm({
    validationSchema: schema,
    initialValues: {
        name: '',
        email: '',
        age: null,
    },
})

const [name, nameAttrs] = defineField('name')
const [email, emailAttrs] = defineField('email')
const [age, ageAttrs] = defineField('age')

const onSubmit = handleSubmit((values) => {
    router.post(route('users.store'), values, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: t('Saved'),
                detail: t('User created successfully'),
                life: 3000,
            })
        },
        onError: (serverErrors) => {
            setErrors(serverErrors)
        },
    })
})
</script>

<template>
    <form @submit="onSubmit">
        <div class="field">
            <label for="name">{{ t('Name') }}</label>
            <InputText id="name" v-model="name" v-bind="nameAttrs" :invalid="!!errors.name" />
            <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
        </div>

        <div class="field">
            <label for="email">{{ t('Email') }}</label>
            <InputText id="email" v-model="email" v-bind="emailAttrs" :invalid="!!errors.email" />
            <small v-if="errors.email" class="p-error">{{ errors.email }}</small>
        </div>

        <div class="field">
            <label for="age">{{ t('Age') }}</label>
            <InputText id="age" v-model="age" v-bind="ageAttrs" :invalid="!!errors.age" type="number" />
            <small v-if="errors.age" class="p-error">{{ errors.age }}</small>
        </div>

        <Button type="submit" :label="t('Create User')" />
    </form>
</template>

</code-snippet>

### Important Form Notes

- Import `useForm` from `vee-validate`, not `@inertiajs/vue3`.
- Build the schema with `toTypedSchema()` from `@vee-validate/yup`.
- Use `defineField()` to bind PrimeVue inputs.
- Pass `:invalid="!!errors.fieldName"` to PrimeVue inputs.
- Submit inside `handleSubmit()` using `router.post()` / `router.put()` and `route()` from Ziggy.
- Always handle `onError` by calling `setErrors(serverErrors)` and showing a toast.
- Localize all user-visible strings with `t()` from `vue-i18n`.

## Inertia v2 Features

### Deferred Props

Use deferred props to load data after initial page render:

<code-snippet name="Deferred Props with Empty State" lang="vue">

<script setup>
defineProps({
    users: Array
})
</script>

<template>
    <div>
        <h1>Users</h1>
        <div v-if="!users" class="animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
        </div>
        <ul v-else>
            <li v-for="user in users" :key="user.id">
                {{ user.name }}
            </li>
        </ul>
    </div>
</template>

</code-snippet>

### Polling

Automatically refresh data at intervals:

<code-snippet name="Polling Example" lang="vue">

<script setup>
import { router } from '@inertiajs/vue3'
import { onMounted, onUnmounted } from 'vue'

defineProps({
    stats: Object
})

let interval

onMounted(() => {
    interval = setInterval(() => {
        router.reload({ only: ['stats'] })
    }, 5000) // Poll every 5 seconds
})

onUnmounted(() => {
    clearInterval(interval)
})
</script>

<template>
    <div>
        <h1>Dashboard</h1>
        <div>Active Users: {{ stats.activeUsers }}</div>
    </div>
</template>

</code-snippet>

### WhenVisible (Infinite Scroll)

Load more data when user scrolls to a specific element:

<code-snippet name="Infinite Scroll with WhenVisible" lang="vue">

<script setup>
import { WhenVisible } from '@inertiajs/vue3'

defineProps({
    users: Object
})
</script>

<template>
    <div>
        <div v-for="user in users.data" :key="user.id">
            {{ user.name }}
        </div>

        <WhenVisible
            v-if="users.next_page_url"
            data="users"
            :params="{ page: users.current_page + 1 }"
        >
            <template #fallback>
                <div>Loading more...</div>
            </template>
        </WhenVisible>
    </div>
</template>

</code-snippet>

## Server-Side Patterns

Server-side patterns (Inertia::render, props, middleware) are covered in inertia-laravel guidelines.

## Common Pitfalls

- Using traditional `<a>` links instead of Inertia's `<Link>` component (breaks SPA behavior)
- Forgetting that Vue components must have a single root element
- Forgetting to add loading states (skeleton screens) when using deferred props
- Not handling the `undefined` state of deferred props before data loads
- Using `<form>` without preventing default submission (use `<Form>` component or `@submit.prevent`)
- Forgetting to check if `<Form>` component is available in your Inertia version