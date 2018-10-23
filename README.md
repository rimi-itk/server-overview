# server-overview

Get a list of servers:

```
bin/console app:website:update
```

Detect (guess) the type and version of each website:

```
bin/console app:website:detect
```

Get data for each website:

```
bin/console app:website:data
```

## Deployment

Create `hosts.yaml`:

```yaml
itksites.example.com:
  stage: production
  roles: app
  deploy_path: /data/www/{{application}}/htdocs
  env:
    APP_ENV: prod
    DATABASE_URL: mysql://itksites:password@127.0.0.1:3306/itksites
```

Deploy the application:

```sh
./vendor/bin/dep deploy production
```
