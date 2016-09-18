# README #

Yii2 Parser by domain

### By this package you can: ###

* grab all links of domain
* store all *.pdf files (or all extensions which you need)

# Installation: #

### composer install ###

In console:

```
    composer require maxlen/yii2_parse_by_domain
```

or add to file composer.json:

```
    "require": {
       "maxlen/yii2_parse_by_domain" : "dev-master"
    },
    "repositories": [
       {
          "type": "git",
          "url": "https://github.com/maxlen/yii2_parse_by_domain.git"
       },
    ]...
```

### Configuration ###

1) Add string in file \common\config\aliases.php :

    Yii::setAlias('vendor', dirname(dirname(DIR)) .'/vendor');

2) Add in file \common\config\params.php :
 
    "yii.migrations"=> [ "@vendor/maxlen/yii2_parse_by_domain/migrations", ],

3) In console:
```
    php yii composer migrate
```



# How should I use it: #

etc.
