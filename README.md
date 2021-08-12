# Auth Server


config/auth.php
```php
return [
       ...
    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver'   => 'oauth',
            'provider' => 'users',
        ],

        'client' => [
            'driver'   => 'oauth',
            'provider' => 'client',
        ],

        'server' => [
            'driver'   => 'oauth',
            'provider' => 'server',
        ],
    ],

       ...

    'providers' => [
        'users'  => [
            'driver' => 'eloquent',
            'model'  => App\User::class,
        ],
        'client' => [
            'driver' => 'client',
        ],
        'server' => [
            'driver' => 'server',
        ]
    ],
       ...
];
```

and run console

```
php artisan vendor:publish --provider="Adideas\OauthServer\Providers\OauthServiceProvider" --tag=oauth-files
```
