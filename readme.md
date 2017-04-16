# JWT lumen authenticatin

### Config file
Create `axt-jwt.php` under `config`, see content of `config/axt-jwt.php`

### Enviornment
Add to `.env`

```
AUTH_GUARD=axt-jwt
AUTH_AXT_JWT_SECRET=<secret>
```

Optionally add:
`AUTH_AXT_JWT_SIGNATURE` with possible values `Hmac`, `Rsa`, `Ecdsa`
`AUTH_AXT_JWT_ALGO` with possible values `Sha256`, `Sha384`, `Sha512`