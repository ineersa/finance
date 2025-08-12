# Finance
Personal finance dashboard

## Create new user
```bash
./bin/console app:create-user admin@test.com admin --role=ROLE_ADMIN
```
## Fix Code style
```bash
./vendor/bin/php-cs-fixer fix src
```

To run tests need to ensure that `/var/www/finance/var/data/test.sqlite` exists.
