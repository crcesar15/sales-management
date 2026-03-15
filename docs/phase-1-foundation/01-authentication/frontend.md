# Authentication — Frontend

## Pages & Components

### Pages
```
resources/js/Pages/Auth/
├── Login.vue
├── ForgotPassword.vue
└── ResetPassword.vue
```

### Layout
Authentication pages use a minimal `GuestLayout.vue` (no sidebar/navbar):
```
resources/js/Layouts/GuestLayout.vue
```

---

## Login Page (`Login.vue`)

### Inertia Form Setup
```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  login: '',       // email or username
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
```

### PrimeVue Components to Use
| Component | Usage |
|---|---|
| `InputText` | Email/username and password fields |
| `Password` | Password field with show/hide toggle |
| `Checkbox` | Remember me |
| `Button` | Submit button with loading state |
| `Message` | Display validation errors |

### UX Requirements
- Show/hide password toggle on the password field
- Submit button shows a spinner while the form is submitting (`form.processing`)
- Display `form.errors.login` beneath the input field
- Redirect handled server-side (Inertia)

---

## Forgot Password Page (`ForgotPassword.vue`)

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({ email: '' })

const submit = () => form.post(route('password.email'))
</script>
```

- Show success message when email is sent (use Inertia shared flash)
- Always show success to prevent email enumeration

---

## Reset Password Page (`ResetPassword.vue`)

```vue
<script setup>
const props = defineProps({ token: String, email: String })

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => form.post(route('password.update'))
</script>
```

---

## Pinia Store
No Pinia store needed for authentication state — use Inertia's shared `auth` prop instead:

```js
// Access authenticated user anywhere
const page = usePage()
const user = computed(() => page.props.auth.user)
const isAdmin = computed(() => page.props.auth.user?.roles?.includes('admin'))
```

---

## Route Definitions (Ziggy)
```js
route('login')            // GET/POST /login
route('logout')           // POST /logout
route('password.request') // GET /forgot-password
route('password.email')   // POST /forgot-password
route('password.reset')   // GET /reset-password/{token}
route('password.update')  // POST /reset-password
```

---

## Good Practices
- Use `<Head title="Login" />` from Inertia for page titles
- Disable submit button while `form.processing` is true
- Use `autocomplete="current-password"` on password fields for browser autofill support
- Keep auth pages lightweight — no heavy imports or global state
- Use `GuestLayout` to prevent authenticated layout from flashing before redirect
