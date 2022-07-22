### Sending mails

dsn
```
remote: smtp://user:pass@smtp.example.com:port
local: sendmail://default
no transport: null://null
```
In config.neon you should place the following:
```neon
mail:
  dsn: sendmail://default
  from: "Account Name <account@domain.com>"
```

compose email
```php
$mail = new Mail($dsn);
$mail->from(array|string $from, array|string $to, string $subject);
$mail->setTemplate('recovery-password', ['user' => 'John Doe']); //template file must be in app/templates folder
$mail->send();
```