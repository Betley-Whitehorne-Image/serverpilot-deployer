### Set up

Make a new folder and run

```
lando init

lando composer global require hirak/prestissimo

lando composer global require sllh/composer-versions-check

lando composer global require pyrech/composer-changelogs

// TODO https://github.com/franzliedke/studio -- package development

lando composer create-project --prefer-dist laravel/laravel
```

Move install files from /laravel to root

### git
`git init`

### Push to Bitbucket
Create repo on Bitbucket matching primary domain - somewebsite.com

```
git remote add origin https://bitbucket.org/betleywhitehorneimage/<reponame>.git
git push -u origin --all
```

### Pull down deployer bits

Add repository to composer.json

```
"repositories":[
	{
		"type": "vcs",
		"url" : "git@bitbucket.org:betleywhitehorneimage/serverpilot-deployer.git"
	}
],
```

Install packages as dev dependencies

```
lando composer require lorisleiva/laravel-deployer --dev
lando composer require riclep/serverpilot-deployer --dev
```

### update .env file

```apacheconfig
SERVERPILOT_CLIENT=
#SERVERPILOT_API_KEY=
```

Run command to make hosting - pass `--deployer` to create deployer config

```apacheconfig
lando artisan bwi:hosting {server_name} {domain} {--deployer}
// lando artisan bwi:hosting bwi-3 website.com --deployer
```

### Troubleshooting

500 error. the env file is missing or is cached. Upload and run artisan `config:cache to update` in ‘current’ folder