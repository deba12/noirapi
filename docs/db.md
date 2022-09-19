### Using db

dsn is configured in app/config/*.neon

```neon
db: [
    mysql: [
        dsn: 'host=localhost;dbname=db_name'
        user: 'db_user'
        pass: 'db_pass'
    ]
]
```