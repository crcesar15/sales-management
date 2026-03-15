# Authentication — Testing

## Test File Location
```
tests/Feature/Auth/AuthenticationTest.php
tests/Feature/Auth/PasswordResetTest.php
```

## Test Cases

### Login
```php
it('renders the login page', function () {
    $this->get(route('login'))->assertOk()->assertInertia(fn ($page) =>
        $page->component('Auth/Login')
    );
});

it('authenticates a user with valid email and password', function () {
    $user = User::factory()->create(['status' => 'active']);

    $this->post(route('login'), [
        'login' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('pos.index')); // Sales Rep default

    $this->assertAuthenticatedAs($user);
});

it('authenticates a user with username', function () {
    $user = User::factory()->create(['status' => 'active']);

    $this->post(route('login'), [
        'login' => $user->username,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

it('redirects admin to dashboard after login', function () {
    $admin = User::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');

    $this->post(route('login'), [
        'login' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'login' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('blocks inactive users from logging in', function () {
    $user = User::factory()->create(['status' => 'inactive']);

    $this->post(route('login'), [
        'login' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('blocks archived users from logging in', function () {
    $user = User::factory()->create(['status' => 'archived']);

    $this->post(route('login'), [
        'login' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('rate limits login after 5 failed attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'wrong',
        ]);
    }

    $this->post(route('login'), [
        'login' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(429);
});
```

### Logout
```php
it('logs out an authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
```

### Password Reset
```php
it('sends a password reset email', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHas('status');
});

it('always returns success on forgot password to prevent enumeration', function () {
    $this->post(route('password.email'), ['email' => 'nonexistent@example.com'])
        ->assertSessionHas('status');
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ])->assertRedirect(route('login'));

    $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
});

it('rejects an expired or invalid reset token', function () {
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ])->assertSessionHasErrors('email');
});
```

### Guest Middleware
```php
it('redirects authenticated users away from login page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('login'))->assertRedirect();
});
```

## Factories Needed
- `User::factory()` with `status`, `email`, `username`, `password` (already hashed via factory)

## Coverage Goals
- All happy paths covered
- All error paths covered (invalid creds, inactive user, bad token)
- Rate limiting tested
- Role-based redirects tested
